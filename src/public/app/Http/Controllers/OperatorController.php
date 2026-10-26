<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Events\TicketStatusChanged;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Counter;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function __construct(
        protected BookingSlotService $slotService,
        protected BookingService $bookingService
    ) {
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $location = $user->location;
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $permissions = $user->getPermissions();
        $isFotografer = $user->isFotografer();

        if (!$location) {
            if ($user->isAdmin()) {
                return redirect()->route('operator.choose-location');
            }

            abort(403, 'Anda belum ditugaskan ke lokasi manapun.');
        }

        $stages = $user->stagesForRole();

        if (empty($stages)) {
            abort(403, 'Role Anda belum dikonfigurasi untuk melayani antrian.');
        }

        [$counter, $counterMessage] = $this->resolveCounterForUser($user, $location, $stages);

        $currentTicket = $counter?->currentTicket();

        $isAdmin = $user->isAdmin();
        $location = $user->location;
        $allLocationIds = Location::pluck('id')->toArray();
        $scopeLocationIds = $allLocationIds;
        if (!$isAdmin && !$user->isOperator() && $location) {
            $scopeLocationIds = [$location->id];
        }

        $filteredLocationId = $request->query('location_id');
        if ($filteredLocationId && in_array((int) $filteredLocationId, $allLocationIds, true)) {
            $scopeLocationIds = [(int) $filteredLocationId];
        }

        // Statistik agregat lintas semua lokasi aktif.
        $todayStats = [
            'total_locations' => Location::where('is_active', true)->count(),
            'total' => Ticket::whereIn('location_id', $scopeLocationIds)->whereDate('created_at', today())->count(),
            'serving' => Ticket::whereIn('location_id', $scopeLocationIds)->whereIn('status', ['serving', 'editing'])->count(),
            'served' => Ticket::whereIn('location_id', $scopeLocationIds)->whereDate('created_at', today())->where('status', 'completed')->count(),
            'waiting' => Ticket::whereIn('location_id', $scopeLocationIds)->where('status', 'waiting')->count(),
            'skipped' => Ticket::whereIn('location_id', $scopeLocationIds)->whereDate('created_at', today())->where('skip_count', '>', 0)->count(),
        ];

        // Antrian menunggu, dikelompokkan per tahap sesuai scope.
        $waitingByStage = [];
        foreach (Ticket::STAGE_ORDER as $stage) {
            $waitingByStage[$stage] = Ticket::whereIn('location_id', $scopeLocationIds)
                ->where('stage', $stage)
                ->where('status', 'waiting')
                ->orderByRaw('COALESCE(last_skipped_at, created_at)')
                ->get();
        }

        // Semua loket aktif sesuai scope.
        $counters = Counter::whereIn('location_id', $scopeLocationIds)
            ->where('is_active', true)
            ->with(['location', 'currentOperator'])
            ->get();

        // Data Pelanggan — sesuai scope. Tabel Reservasi ditampilkan untuk
        // semua staf yang punya akses "Data Pelanggan" (termasuk Fotografer),
        // supaya konsisten dengan tampilan CS & Kasir — tapi tombol aksi
        // (Panggil/Batalkan/dst.) tetap cuma untuk CS & Kasir/Admin lewat
        // $canManageBookings di bawah.
        [$custTickets, $custSearch, $custStatus, $custStage, $custDate] = $this->filteredCustomerTickets($request, $location, $scopeLocationIds);
        $canManageBookings = $user->isOperator() || $user->isAdmin();
        $bookingSearch = '';
        $bookingDate = now()->toDateString();
        $custBookings = null;
        if (in_array('customers', $permissions, true)) {
            [$custBookings, $bookingSearch, $bookingDate] = $this->filteredCustomerBookings($request, $location, $scopeLocationIds);
        }

        // Semua reservasi yang belum selesai sesuai scope.
        $todayBookings = Booking::whereIn('location_id', $scopeLocationIds)
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('booking_date')
            ->orderBy('booking_time')
            ->get();

        // Ambil tiket yang siap diedit (ready_for_edit) untuk fotografer ini
        $readyForEditTickets = Ticket::where('served_by', $user->id)
            ->where('stage', 'photo_session')
            ->where('status', Ticket::STATUS_READY_FOR_EDIT)
            ->with(['counter'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Ambil tiket yang SEDANG diedit untuk fotografer ini -- baik yang
        // masuk lewat "Edit Sekarang" (langsung) maupun lewat batch edit.
        // Ini gak lagi terikat ke currentTicket() satu loket (lihat
        // Counter::currentTicket()), jadi kalau ada beberapa tiket diedit
        // sekaligus, semuanya tetap kelihatan & bisa diselesaikan di sini.
        $editingTickets = Ticket::where('served_by', $user->id)
            ->where('stage', 'photo_session')
            ->where('status', Ticket::STATUS_EDITING)
            ->with(['counter'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $isFotografer = $user->isFotografer();
        $isCsKasir = $user->isOperator() || $user->isAdmin();
        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->orderBy('name')->get();
        $bookingLocations = Location::where('is_active', true)
            ->get()
            ->filter(fn ($l) => $l->offersBooking())
            ->values();

        return view('operator.dashboard', compact(
            'location', 'locations', 'counter', 'counters', 'waitingByStage',
            'todayStats', 'todayBookings', 'custTickets', 'custBookings',
            'custSearch', 'custStatus', 'custStage', 'custDate',
            'bookingSearch', 'bookingDate',
            'currentTicket', 'counterMessage', 'isFotografer', 'isCsKasir',
            'permissions', 'canManageBookings', 'serviceCategories', 'bookingLocations',
            'readyForEditTickets', 'editingTickets'
        ));
    }

    /**
     * "Istirahat" / "Aktif Lagi" — self-service, dipencet operator/
     * fotografer sendiri dari dashboard-nya, BEDA dengan is_active yang
     * cuma bisa diubah Admin dari Kelola Loket. Loket TETAP terikat ke
     * operator ini (current_operator_id gak dilepas) — cuma sementara gak
     * ditawarin buat dipanggil pelanggan baru / siap-siap otomatis (lihat
     * callNext(), callSpecific(), autoCallNextToPrepare()), sampai
     * operatornya sendiri nyalain lagi. Cocok buat istirahat sebentar
     * tanpa perlu logout & mungkin kehilangan studio ke orang lain.
     */
    public function togglePause(Request $request, Counter $counter): RedirectResponse
    {
        $user = auth()->user();

        if ($counter->current_operator_id !== $user->id && !$user->isAdmin()) {
            return back()->with('error', 'Loket ini bukan yang sedang Anda pegang.');
        }

        $paused = $request->boolean('is_paused');

        $counter->update(['is_paused' => $paused]);

        return back()->with('success', $paused
            ? "Loket \"{$counter->name}\" ditandai istirahat — gak akan dipanggilin pelanggan baru sampai Anda aktifkan lagi."
            : "Loket \"{$counter->name}\" aktif lagi, siap menerima pelanggan.");
    }

    /**
     * Cari loket yang sedang dipegang user ini di lokasi saat ini,
     * sesuaikan dengan tahap (stages) role-nya.
     *
     * @return array{0: ?\App\Models\Counter, 1: ?string}
     */
    private function resolveCounterForUser(User $user, Location $location, array $stages): array
    {
        $stageList = is_array($stages) ? $stages : [$stages];

        // Loket yang memang sudah dipegang user ini (current_operator_id = user)
        // dan masih aktif serta menangani tahap role-nya.
        $assigned = Counter::where('location_id', $location->id)
            ->where('is_active', true)
            ->where(function ($q) use ($stageList) {
                foreach ($stageList as $stage) {
                    $q->orWhereJsonContains('stages', $stage);
                }
            })
            ->where('current_operator_id', $user->id)
            ->first();

        if ($assigned) {
            return [$assigned, null];
        }

        $baseQuery = fn () => Counter::where('location_id', $location->id)
            ->where('is_active', true)
            ->where(function ($q) use ($stageList) {
                foreach ($stageList as $stage) {
                    $q->orWhereJsonContains('stages', $stage);
                }
            });

        // Loket favorit (diatur Admin -> Kelola Pengguna) diprioritaskan
        // dulu, supaya pengelompokan fotografer <-> studio konsisten tiap
        // hari, lepas dari siapa yang login duluan.
        if ($user->preferred_counter_id) {
            $preferred = $baseQuery()
                ->where('id', $user->preferred_counter_id)
                ->whereNull('current_operator_id')
                ->first();

            if ($preferred) {
                $preferred->update(['current_operator_id' => $user->id]);

                return [$preferred, null];
            }
        }

        // Belum ada loket yang dipegang: ambil loket aktif yang menangani
        // tahap role ini dan belum ada operator-nya, lalu ikat ke user ini.
        $available = $baseQuery()->whereNull('current_operator_id')->first();

        if ($available) {
            $available->update(['current_operator_id' => $user->id]);

            return [$available, null];
        }

        $stageLabels = collect($stages)
            ->map(fn ($s) => \App\Models\Counter::STAGE_LABELS[$s] ?? $s)
            ->implode(' / ');

        // Pesan diagnostik: biar ketauan persis apa yang kurang, bukan cuma
        // "loket belum siap" doang — beda kasus antara "belum ada loket
        // dikonfigurasi buat tahap ini" vs "semua loket lagi dipegang orang
        // lain" butuh tindakan yang beda dari Admin.
        $matchingCounters = $baseQuery()->get();

        if ($matchingCounters->isEmpty()) {
            return [null, "Belum ada loket aktif yang dikonfigurasi untuk tahap: {$stageLabels}. Minta Admin buka Admin → Lokasi → Loket, lalu tambah loket baru dengan tahap {$stageLabels} dicentang."];
        }

        $occupiedBy = $matchingCounters
            ->filter(fn ($c) => $c->current_operator_id)
            ->map(fn ($c) => $c->name . ' (' . ($c->currentOperator?->name ?? 'user lain') . ')')
            ->implode(', ');

        return [null, "Semua loket untuk tahap {$stageLabels} sedang dipegang operator lain: {$occupiedBy}. Minta operator itu logout dulu, atau minta Admin tambah loket baru."];
    }

    public function createBooking(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin() && !$user->isFotografer()) {
            abort(403, 'Hanya CS, Kasir, atau Fotografer yang bisa menambahkan reservasi manual.');
        }

        $location = $user->location;
        $counter = $user->operatingCounter;

        if (!$location) {
            return back()->with('error', 'Anda belum ditugaskan ke lokasi manapun.');
        }

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        $locations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        // Lokasi tujuan reservasi bisa beda dari lokasi tempat CS/Kasir ini
        // login (mis. ada lokasi khusus reservasi terpisah dari lokasi
        // tiket walk-in) — jadi jangan block form ini cuma karena lokasi
        // ASAL staff tidak menerima reservasi. Yang penting minimal ada
        // 1 lokasi aktif yang menerima reservasi buat dipilih di form.
        if (!$locations->contains(fn ($l) => $l->offersBooking())) {
            return back()->with('error', 'Belum ada lokasi yang menerima reservasi. Hubungi Admin untuk mengaktifkannya.');
        }

        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->orderBy('name')->get();

        return view('operator.bookings.create', [
            'location' => $location,
            'serviceCategories' => $serviceCategories,
            'locations' => $locations,
        ]);
    }

    public function storeManualBooking(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin() && !$user->isFotografer()) {
            abort(403, 'Hanya CS, Kasir, atau Fotografer yang bisa menambahkan reservasi manual.');
        }

        $location = $user->location;
        $counter = $user->operatingCounter;

        if (!$location) {
            return back()->with('error', 'Anda belum ditugaskan ke lokasi manapun.');
        }

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $targetLocation = $location;
        if (!empty($validated['location_id'])) {
            $targetLocation = Location::findOrFail($validated['location_id']);
        }

        // Baru di sini kita validasi lokasi TUJUAN-nya (bukan lokasi asal
        // staff) yang menerima reservasi.
        if (!$targetLocation->offersBooking()) {
            return back()->withInput()->with('error', 'Lokasi "' . $targetLocation->name . '" tidak menerima reservasi. Pilih lokasi lain.');
        }

        try {
            $this->bookingService->create($targetLocation, $validated);
        } catch (\App\Exceptions\BookingCreationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reservasi manual berhasil ditambahkan.');
    }

    public function editBooking(Booking $booking): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat mengedit reservasi.');
        }

        if (in_array($booking->status, ['checked_in', 'completed', 'cancelled'])) {
            return back()->with('error', 'Reservasi ini sudah tidak bisa diedit.');
        }

        $location = $booking->location;
        $serviceCategories = $location?->serviceCategories()->where('is_active', true)->get() ?? collect();

        return view('operator.bookings.edit', compact('booking', 'location', 'serviceCategories'));
    }

    public function updateBooking(Request $request, Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat mengedit reservasi.');
        }

        if (in_array($booking->status, ['checked_in', 'completed', 'cancelled'])) {
            return back()->with('error', 'Reservasi ini sudah tidak bisa diubah.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $location = $booking->location;
        $openTime = $location->open_time->format('H:i');
        $closeTime = $location->close_time->format('H:i');

        if ($validated['booking_time'] < $openTime || $validated['booking_time'] > $closeTime) {
            return back()->withInput()->with('error', "Jam harus di antara {$openTime} - {$closeTime} (jam operasional lokasi).");
        }

        $booking->update($validated);

        return back()->with('success', "Reservasi {$booking->booking_code} berhasil diperbarui.");
    }

    public function destroyBooking(Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat menghapus reservasi.');
        }

        if (in_array($booking->status, ['checked_in', 'completed'])) {
            return back()->with('error', 'Reservasi ini sudah diproses dan tidak bisa dihapus.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', "Reservasi {$booking->booking_code} berhasil dibatalkan.");
    }

    /**
     * "Panggil" untuk reservasi — satu tombol untuk check-in (reservasi jadi
     * tiket antrian) SEKALIGUS langsung dipanggil ke loket CS, persis seperti
     * panggil manual di tahap Pembayaran & Cetak (bukan FIFO otomatis, karena
     * CS yang menentukan reservasi mana yang gilirannya sekarang).
     */
    public function callBooking(Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir yang bisa memanggil reservasi.');
        }

        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if (!$counter->handlesStage('registration')) {
            return back()->with('error', 'Hanya loket CS & Kasir yang bisa memanggil reservasi.');
        }

        if ($counter->currentTicket()) {
            return back()->with('error', 'Selesaikan tiket yang sedang dilayani terlebih dahulu.');
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Reservasi ini sudah diproses sebelumnya.');
        }

        if (!$booking->booking_date->isToday()) {
            return back()->with('error', 'Reservasi ini bukan untuk hari ini.');
        }

        $ticket = $booking->checkIn();

        if (!$ticket) {
            return back()->with('error', 'Gagal check-in reservasi.');
        }

        if (!in_array($ticket->stage, $counter->stages ?? [])) {
            return back()->with('error', 'Tiket hasil check-in tidak sesuai tahap loket Anda.');
        }

        $ticket->call($counter, $user);
        broadcast(new QueueUpdated($booking->location))->toOthers();

        return back()->with('success', "Reservasi {$booking->booking_code} dipanggil sebagai tiket {$ticket->ticket_number}.");
    }

    public function callNext(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;
        $stage = $request->input('stage');
        $location = $user->location;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if ($counter->is_paused) {
            return back()->with('error', 'Loket Anda sedang istirahat. Aktifkan lagi dulu sebelum memanggil antrian.');
        }

        if ($counter->currentTicket()) {
            return back()->with('error', 'Selesaikan tiket yang sedang dilayani terlebih dahulu.');
        }

        $query = Ticket::query()
            ->where('status', 'waiting')
            ->orderByRaw('COALESCE(last_skipped_at, created_at)');

        $allLocationIds = Location::pluck('id')->toArray();
        $scopeLocationIds = $allLocationIds;
        if (!$user->isAdmin() && !$user->isOperator() && $location) {
            $scopeLocationIds = [$location->id];
        }

        $filteredLocationId = $request->input('location_id');
        if ($filteredLocationId && in_array((int) $filteredLocationId, $allLocationIds, true)) {
            $scopeLocationIds = [(int) $filteredLocationId];
        }

        $query->whereIn('location_id', $scopeLocationIds);

        if ($stage && in_array($stage, Ticket::STAGE_ORDER, true)) {
            $query->where('stage', $stage);
        } elseif (!empty($counter->stages)) {
            $query->whereIn('stage', $counter->stages);
        }

        // Dikunci dalam transaction supaya 2 loket/fotografer yang klik
        // "Panggil Selanjutnya" hampir bersamaan tidak bisa mendapat tiket
        // yang sama (tanpa ini, race condition bisa membuat 1 pelanggan
        // dipanggil ke 2 loket sekaligus).
        $nextTicket = null;
        $stageMismatch = false;

        DB::transaction(function () use ($query, $counter, $user, &$nextTicket, &$stageMismatch) {
            $ticket = (clone $query)->lockForUpdate()->first();

            if (!$ticket) {
                return;
            }

            // Re-check di dalam lock: bisa saja sudah diambil operator lain
            // tepat sebelum lock ini didapat.
            $ticket->refresh();
            if ($ticket->status !== 'waiting') {
                return;
            }

            if (!in_array($ticket->stage, $counter->stages ?? [])) {
                $stageMismatch = true;

                return;
            }

            $ticket->call($counter, $user);
            $nextTicket = $ticket;
        });

        if ($stageMismatch) {
            return back()->with('error', 'Antrian berikutnya tidak sesuai tahap loket Anda.');
        }

        if (!$nextTicket) {
            return back()->with('info', 'Tidak ada antrian menunggu saat ini.');
        }

        broadcast(new QueueUpdated($nextTicket->location))->toOthers();

        return back()->with('success', "Antrian {$nextTicket->ticket_number} berhasil dipanggil ke {$counter->name}.");
    }

    public function callSpecific(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if ($counter->is_paused) {
            return back()->with('error', 'Loket Anda sedang istirahat. Aktifkan lagi dulu sebelum memanggil antrian.');
        }

        if (!$user->isAdmin() && !$user->isOperator() && $ticket->location_id !== $user->location_id) {
            return back()->with('error', 'Tiket ini bukan di lokasi Anda.');
        }

        if (!in_array($ticket->stage, $counter->stages ?? [])) {
            return back()->with('error', 'Tiket ini tidak sesuai tahap loket Anda.');
        }

        if ($counter->currentTicket()) {
            return back()->with('error', 'Selesaikan tiket yang sedang dilayani terlebih dahulu.');
        }

        $alreadyTaken = false;

        DB::transaction(function () use ($ticket, $counter, $user, &$alreadyTaken) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== 'waiting') {
                $alreadyTaken = true;

                return;
            }

            $locked->call($counter, $user);
        });

        if ($alreadyTaken) {
            return back()->with('error', 'Tiket ini sudah tidak dalam status menunggu.');
        }

        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', "Antrian {$ticket->ticket_number} berhasil dipanggil ke {$counter->name}.");
    }

    public function startServing(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->status !== 'calling') {
            return back()->with('error', 'Status tiket tidak valid.');
        }

        $ticket->startServing();

        // Hybrid: begitu fotografer mulai sesi foto, sistem otomatis coba
        // panggil pelanggan BERIKUTNYA jadi "Siap-siap" di loket yang sama
        // (kalau ada antrian & belum ada yang siap-siap) — supaya fotografer
        // gak perlu inget klik tombol terpisah. Kalau ternyata sesi ini bakal
        // lama, fotografer tetap bisa klik "Batalkan Siap-siap" buat
        // menunda/membatalkannya manual.
        $preparedTicket = null;
        if ($ticket->stage === 'photo_session' && $ticket->counter) {
            $preparedTicket = $this->autoCallNextToPrepare($ticket->counter, $user);
        }

        $message = 'Mulai melayani tiket ' . $ticket->ticket_number . '.';
        if ($preparedTicket) {
            $message .= " Tiket {$preparedTicket->ticket_number} otomatis dipanggil untuk Siap-siap.";
        }

        return back()->with('success', $message);
    }

    /**
     * "Foto Selesai, Mulai Edit" — dipakai fotografer begitu pengambilan
     * foto kelar dan dia LANGSUNG mulai proses edit saat itu juga (beda
     * dari finishPhotoSession() yang menaruh tiket ke antrian
     * "Siap Diedit" dulu buat diproses batch belakangan).
     */
    public function startEditing(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if (!in_array($ticket->status, ['serving', Ticket::STATUS_READY_FOR_EDIT], true)) {
            return back()->with('error', 'Status tiket tidak valid untuk mulai edit.');
        }

        $ticket->startEditing();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' sedang diedit.');
    }

    /**
     * "Sesi Foto Selesai" — dipakai fotografer begitu pengambilan foto kelar
     * dan dia siap untuk proses edit. Status tiket berubah jadi `ready_for_edit`.
     *
     * Tombol ini terpisah dari "Mulai Edit" agar fotografer bisa mengumpulkan
     * beberapa tiket yang siap diedit sekaligus, lalu memprosesnya secara batch
     * dari panel editing terpisah.
     */
    public function finishPhotoSession(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->status !== 'serving') {
            return back()->with('error', 'Status tiket tidak valid untuk menandai sesi foto selesai.');
        }

        // Pindahkan status ke "ready_for_edit"
        $ticket->update([
            'status' => Ticket::STATUS_READY_FOR_EDIT,
            'photo_completed_at' => now(),
            'ready_for_edit_at' => now(),
        ]);

        // Broadcast perubahan status — TicketStatusChanged buat halaman
        // status pelanggan itu sendiri (channel ticket.{id}), QueueUpdated
        // buat dashboard operator (channel location.{id}).
        broadcast(new TicketStatusChanged($ticket))->toOthers();
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Sesi foto tiket ' . $ticket->ticket_number . ' selesai. Menunggu diproses edit.');
    }

    public function completeTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if (!in_array($ticket->status, ['calling', 'serving', 'editing'])) {
            return back()->with('error', 'Status tiket tidak valid.');
        }

        if ($ticket->stage === 'photo_session' && $ticket->status === 'serving') {
            return back()->with('error', 'Klik "Foto Selesai, Mulai Edit" dulu sebelum lanjut ke Pembayaran.');
        }

        if ($ticket->stage === 'registration') {
            return back()->with('error', 'Selesaikan tahap registrasi terlebih dahulu (gunakan tombol pada tahap Registrasi).');
        }

        $wasLastStage = $ticket->stage === 'payment';
        $ticket->advanceStage();
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', $wasLastStage
            ? 'Tiket ' . $ticket->ticket_number . ' selesai — pembayaran & cetak foto tuntas.'
            : 'Tiket ' . $ticket->ticket_number . ' lanjut ke tahap Pembayaran & Cetak.');
    }

    /**
     * CS mencatat catatan singkat pelanggan (kalau ada) lalu tiket lanjut
     * ke antrian "Sesi Foto". Tidak ada rincian order/harga di tahap ini.
     */
    public function completeRegistration(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if (!in_array($ticket->status, ['calling', 'serving'])) {
            return back()->with('error', 'Status tiket tidak valid.');
        }

        if ($ticket->stage !== 'registration') {
            return back()->with('error', 'Tiket ini sudah melewati tahap registrasi.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->filled('notes')) {
            $ticket->update(['notes' => $validated['notes']]);
        }

        if (!$ticket->completeRegistration()) {
            return back()->with('error', 'Konfirmasi pesanan dulu (tombol "✅ Konfirmasi Pesanan") sebelum lanjut ke Sesi Foto.');
        }

        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' lanjut ke antrian Sesi Foto.');
    }

    public function skipTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if (!in_array($ticket->stage, $counter->stages ?? [])) {
            return back()->with('error', 'Tiket ini tidak sesuai tahap loket Anda.');
        }

        if (!in_array($ticket->status, ['calling', 'serving', 'waiting'])) {
            return back()->with('error', 'Status tiket tidak valid.');
        }

        if ($ticket->status === 'waiting') {
            if ($ticket->served_by !== $user->id) {
                return back()->with('error', 'Tiket ini bukan milik Anda.');
            }

            $ticket->update([
                'served_by' => $user->id,
                'counter_id' => $counter->id,
            ]);
        } elseif ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        $wasAutoCancelled = $ticket->skip_count + 1 >= \App\Models\Ticket::MAX_SKIPS;

        $ticket->skip();
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        if ($wasAutoCancelled) {
            return back()->with('error', 'Tiket ' . $ticket->ticket_number . ' dibatalkan otomatis — sudah dilewati ' . \App\Models\Ticket::MAX_SKIPS . 'x, pelanggan tidak ada di tempat.');
        }

        return back()->with('info', 'Tiket ' . $ticket->ticket_number . ' dilewati — dikembalikan ke belakang antrian ' . $ticket->stageLabel() . ', bisa dipanggil lagi kapan saja.');
    }

    public function recallTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Anda belum memilih loket.');
        }

        if ($ticket->status !== 'calling' || $ticket->served_by !== $user->id) {
            return back()->with('error', 'Tidak dapat memanggil ulang tiket ini.');
        }

        $ticket->call($counter, $user);

        return back()->with('success', 'Memanggil ulang tiket ' . $ticket->ticket_number);
    }

    /**
     * Catatan bersama: bisa diisi/diubah dari tahap manapun (CS, kasir, fotografer)
     * dan langsung terlihat di semua tahap lain untuk tiket yang sama.
     *
     * Begitu sesi foto sudah mulai (notes_locked_at terisi), catatan ini
     * terkunci — cuma Admin yang bisa override, biar gak ada yang diam-diam
     * mengubah pesanan di tengah sesi foto (lihat docs/BUG-CHECKLIST.md #5).
     */
    public function updateNotes(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->notes_locked_at && !$user->isAdmin()) {
            return back()->with('error', 'Catatan pesanan ini sudah terkunci sejak sesi foto dimulai. Hubungi Admin kalau perlu diubah.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update(['notes' => $validated['notes'] ?? null]);

        return back()->with('success', 'Catatan tiket ' . $ticket->ticket_number . ' diperbarui.');
    }

    /**
     * CS klik "✅ Konfirmasi Pesanan" setelah pelanggan setuju dengan paket
     * & catatan final. Wajib dipanggil sebelum tiket boleh lanjut ke
     * antrian Sesi Foto (lihat Ticket::completeRegistration()).
     */
    public function confirmOrder(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->stage !== 'registration') {
            return back()->with('error', 'Tiket ini sudah melewati tahap registrasi.');
        }

        $ticket->confirmOrder();

        return back()->with('success', 'Pesanan tiket ' . $ticket->ticket_number . ' dikonfirmasi (' . $ticket->order_code . '). Sekarang bisa lanjut ke Sesi Foto.');
    }

    /**
     * "Batalkan Pemanggilan" — dipakai kalau STAFF yang salah (salah
     * pencet, ternyata gak jadi motret/layani, dll). Beda dari skip():
     * tidak menambah skip_count, karena kesalahan staff tidak boleh
     * berujung tiket pelanggan ke-auto-cancel. Lihat Ticket::uncall().
     */
    public function uncallTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if (!in_array($ticket->status, ['calling', 'preparing', 'serving'])) {
            return back()->with('error', 'Status tiket tidak valid untuk dibatalkan pemanggilannya.');
        }

        $ticket->uncall();
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('info', 'Pemanggilan tiket ' . $ticket->ticket_number . ' dibatalkan, dikembalikan ke antrian.');
    }

    /**
     * "Proses Batch Edit Foto" — dipakai fotografer untuk memilih beberapa tiket
     * yang sudah "Siap Diedit" (`ready_for_edit`) lalu diproses editnya
     * secara bersamaan.
     *
     * Ini berguna agar fotografer bisa efisien tanpa harus klik "Mulai Edit"
     * satu per satu untuk setiap foto.
     */
    public function batchStartEditing(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        $validated = $request->validate([
            'tickets_to_edit' => ['required', 'array', 'min:1'],
            'tickets_to_edit.*' => ['integer', 'exists:tickets,id'],
        ]);

        $editedCount = 0;
        $ticketIds = $validated['tickets_to_edit'];

        $tickets = Ticket::whereIn('id', $ticketIds)
            ->where('served_by', $user->id)
            ->where('stage', 'photo_session')
            ->where('status', Ticket::STATUS_READY_FOR_EDIT)
            ->lockForUpdate()
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->startEditing();
            $editedCount++;
        }

        return back()->with('success', "{$editedCount} tiket siap diedit telah diproses.");
    }

    public function callNextToPrepare(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if ($counter->is_paused) {
            return back()->with('error', 'Loket Anda sedang istirahat. Aktifkan lagi dulu sebelum memanggil Siap-siap.');
        }

        $currentTicket = $counter->currentTicket();

        if (!$currentTicket || $currentTicket->status !== 'serving') {
            return back()->with('error', 'Panggil "Siap-siap" cuma bisa dilakukan selagi sedang memproses foto pelanggan saat ini.');
        }

        if ($counter->preparingTicket()) {
            return back()->with('error', 'Sudah ada pelanggan lain yang sedang Siap-siap untuk loket ini.');
        }

        $nextTicket = $this->autoCallNextToPrepare($counter, $user);

        if (!$nextTicket) {
            return back()->with('info', 'Tidak ada antrian menunggu untuk dipanggil siap-siap.');
        }

        return back()->with('success', "Tiket {$nextTicket->ticket_number} dipanggil untuk Siap-siap.");
    }

    /**
     * Cari & panggil tiket berikutnya jadi "Siap-siap" untuk loket ini.
     * Dipakai baik dari tombol manual (callNextToPrepare) MAUPUN otomatis
     * begitu fotografer mulai sesi foto (lihat startServing()) — supaya
     * pelanggan berikutnya gak perlu nunggu fotografer inget klik tombol.
     * Return null diam-diam kalau gak ada antrian/sudah ada yang siap-siap
     * (dipanggil otomatis, jadi gak boleh nampilin error ke user).
     */
    private function autoCallNextToPrepare(Counter $counter, User $user): ?Ticket
    {
        if ($counter->preparingTicket() || $counter->is_paused) {
            return null;
        }

        $scopeLocationIds = $user->isAdmin()
            ? Location::pluck('id')
            : collect([$counter->location_id]);

        $nextTicket = null;

        DB::transaction(function () use ($counter, $user, $scopeLocationIds, &$nextTicket) {
            $ticket = Ticket::where('status', 'waiting')
                ->whereIn('location_id', $scopeLocationIds)
                ->whereIn('stage', $counter->stages ?? [])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return;
            }

            $ticket->refresh();
            if ($ticket->status !== 'waiting') {
                return;
            }

            $ticket->callToPrepare($counter, $user);
            $nextTicket = $ticket;
        });

        return $nextTicket;
    }

    /**
     * Daftar semua tiket pelanggan di lokasi operator ini, dengan status
     * lengkap (menunggu / dipanggil / dilayani / selesai / dibatalkan) dan
     * filter tahap, status, tanggal, serta pencarian nama/telepon/email/no. tiket.
     *
     * Catatan: halaman ini sekarang juga digabung langsung ke dalam
     * operator.dashboard (lihat dashboard()) supaya operator tidak perlu
     * pindah-pindah halaman. Route/halaman ini tetap ada untuk kompatibilitas
     * bila ada yang membuka/menyimpan link-nya langsung.
     */
    public function customers(Request $request): View
    {
        $user = auth()->user();
        $location = $user->location;

        if (!$location) {
            abort(403, 'Anda belum ditugaskan ke lokasi manapun.');
        }

        [$tickets, $search, $status, $stage, $date] = $this->filteredCustomerTickets($request, $location);

        return view('operator.customers', [
            'location' => $location,
            'tickets' => $tickets,
            'search' => $search,
            'status' => $status,
            'stage' => $stage,
            'date' => $date,
        ]);
    }

    /**
     * @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator, 1: string, 2: string, 3: string, 4: string}
     */
    private function filteredCustomerTickets(Request $request, Location $location, ?array $locationIds = null): array
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $stage = $request->query('stage', '');
        $date = $request->query('date', now()->toDateString());

        $locationIds ??= [$location->id];

        $query = Ticket::whereIn('location_id', $locationIds)
            ->with(['counter', 'servedBy'])
            ->orderByDesc('created_at');

        if ($date !== 'all') {
            $query->whereDate('created_at', $date);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($stage !== '') {
            $query->where('stage', $stage);
        }

        if ($search !== '') {
            // Kode Order permanen formatnya "ORD-000123" (lihat
            // Ticket::getOrderCodeAttribute()) — kalau yang diketik cocok pola
            // itu atau murni angka, ikut cocokkan ke kolom id juga, supaya
            // pelanggan/tiket lama tetap ketemu walau ticket_number-nya sudah
            // dipakai ulang di hari lain.
            $orderIdSearch = null;
            if (preg_match('/^ORD-0*(\d+)$/i', $search, $m)) {
                $orderIdSearch = (int) $m[1];
            } elseif (ctype_digit($search)) {
                $orderIdSearch = (int) $search;
            }

            $query->where(function ($q) use ($search, $orderIdSearch) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");

                if ($orderIdSearch !== null) {
                    $q->orWhere('id', $orderIdSearch);
                }
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        return [$tickets, $search, $status, $stage, $date];
    }

    /**
     * Reservasi (Booking) untuk lokasi ini, dengan filter pencarian & tanggal
     * yang sama seperti tiket — supaya satu kotak pencarian di Data Pelanggan
     * bisa nyari keduanya sekaligus. Status booking (pending/confirmed/
     * checked_in/cancelled) beda konsep dari status tiket, jadi sengaja
     * ditampilkan di tabel terpisah, bukan dipaksa gabung satu tabel.
     */
    private function filteredCustomerBookings(Request $request, Location $location, ?array $locationIds = null)
    {
        // Sengaja pakai nama parameter beda dari filter Tiket (q/date) —
        // sebelumnya sama-sama pakai 'q' & 'date', jadi filter di satu form
        // (mis. Tiket) diam-diam ikut mengubah filter form yang lain
        // (Reservasi), karena dua-duanya baca query string yang sama persis.
        $search = trim((string) $request->query('bq', ''));
        $date = $request->query('bdate', now()->toDateString());

        $locationIds ??= [$location->id];

        $query = Booking::whereIn('location_id', $locationIds)
            ->with(['serviceCategory', 'ticket'])
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time');

        if ($date !== 'all') {
            $query->whereDate('booking_date', $date);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('booking_code', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate(10, ['*'], 'booking_page')->withQueryString();

        return [$bookings, $search, $date];
    }

    /**
     * Jadwal Reservasi — CS & Kasir boleh atur sendiri jam reservasi untuk
     * lokasi tempat mereka bertugas (tidak bisa mengubah lokasi lain).
     * Fotografer tidak diberi akses karena jadwal reservasi urusan CS/kasir.
     */
    private function authorizeBookingSlotManager(): Location
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir yang dapat mengatur jadwal reservasi.');
        }

        if (!$user->location) {
            abort(403, 'Anda belum ditugaskan ke lokasi manapun.');
        }

        return $user->location;
    }

    /**
     * Pastikan slot yang mau diubah/dihapus memang milik lokasi CS ini,
     * bukan lokasi lain (dicek manual karena route tidak membawa {location}).
     */
    private function authorizeBookingSlotOwnership(BookingSlot $bookingSlot, Location $location): void
    {
        if ($bookingSlot->location_id !== $location->id) {
            abort(403, 'Slot ini bukan milik lokasi Anda.');
        }
    }

    public function bookingSlots(): View
    {
        $location = $this->authorizeBookingSlotManager();

        [$slots, $bookedTodayByTime] = $this->slotService->getSlotsWithBookedCounts($location);

        return view('admin.booking-slots.index', [
            'location' => $location,
            'slots' => $slots,
            'bookedTodayByTime' => $bookedTodayByTime,
            'backRoute' => route('operator.dashboard'),
            'backLabel' => 'Kembali ke Dashboard',
            'routePrefix' => 'operator',
        ]);
    }

    public function updateBookingMode(Request $request): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();

        $validated = $request->validate([
            'booking_mode' => ['required', 'in:slots,free'],
        ]);

        $this->slotService->updateMode($location, $validated['booking_mode']);

        return back()->with('success', 'Sistem reservasi berhasil diubah.');
    }

    public function bulkActionBookingSlots(Request $request): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();

        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->slotService->bulkAction($location, $validated['action'], $validated['ids']);

        $messages = [
            'activate' => "{$count} slot berhasil diaktifkan.",
            'deactivate' => "{$count} slot berhasil dinonaktifkan.",
            'delete' => "{$count} slot berhasil dihapus.",
        ];

        return back()->with('success', $messages[$validated['action']]);
    }

    public function storeBookingSlot(Request $request): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();

        $validated = $request->validate([
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        [$created, $skipped] = $this->slotService->store($location, $validated);

        $message = "{$created} slot berhasil ditambahkan.";
        if ($skipped > 0) {
            $message .= " {$skipped} slot dilewati karena jam itu sudah ada di hari yang sama.";
        }

        return back()->with('success', $message);
    }

    public function generateBookingSlots(Request $request): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();

        $validated = $request->validate([
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'interval_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        [$created, $skipped] = $this->slotService->generate($location, $validated);

        $message = "{$created} slot berhasil digenerate otomatis.";
        if ($skipped > 0) {
            $message .= " {$skipped} slot dilewati karena sudah ada.";
        }

        return back()->with('success', $message);
    }

    public function updateBookingSlot(Request $request, BookingSlot $bookingSlot): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();
        $this->authorizeBookingSlotOwnership($bookingSlot, $location);

        $validated = $request->validate([
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->slotService->update($bookingSlot, $validated['capacity'], $request->boolean('is_active'));

        return back()->with('success', 'Slot berhasil diperbarui.');
    }

    public function toggleBookingSlot(BookingSlot $bookingSlot): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();
        $this->authorizeBookingSlotOwnership($bookingSlot, $location);

        $this->slotService->toggle($bookingSlot);

        $status = $bookingSlot->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Slot berhasil {$status}.");
    }

    public function deleteBookingSlot(BookingSlot $bookingSlot): RedirectResponse
    {
        $location = $this->authorizeBookingSlotManager();
        $this->authorizeBookingSlotOwnership($bookingSlot, $location);

        $this->slotService->delete($bookingSlot);
        return back()->with('success', 'Slot berhasil dihapus.');
    }

    /**
     * Admin tidak terikat ke satu lokasi secara permanen (beda dari CS/Kasir/
     * Fotografer), jadi sebelum masuk Panel CS, admin pilih dulu mau
     * "bertugas" di lokasi mana. Setelah dipilih, admin dapat akses yang
     * sama persis seperti CS & Kasir (termasuk tambah reservasi manual) di
     * lokasi tersebut, dan bisa berpindah lokasi kapan saja.
     */
    public function chooseLocation(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('operator.choose-location', compact('locations'));
    }

    public function setLocation(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
        ]);

        auth()->user()->update(['location_id' => $validated['location_id']]);

        return redirect()->route('operator.dashboard')
            ->with('success', 'Panel CS aktif untuk lokasi terpilih.');
    }

    public function editTicket(Ticket $ticket): View|RedirectResponse
    {
        $user = auth()->user();

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        if (!in_array($ticket->status, ['waiting', 'calling'], true)) {
            return back()->with('error', 'Tiket ini sudah tidak bisa diubah.');
        }

        if (!in_array($ticket->stage, $user->stagesForRole())) {
            abort(403, 'Tiket ini bukan di tahap yang Anda tangani.');
        }

        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->orderBy('name')->get();

        return view('operator.tickets.edit', [
            'ticket' => $ticket,
            'serviceCategories' => $serviceCategories,
        ]);
    }

    public function updateTicket(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        if (!in_array($ticket->status, ['waiting', 'calling'], true)) {
            return back()->with('error', 'Tiket ini sudah tidak bisa diubah.');
        }

        if (!in_array($ticket->stage, $user->stagesForRole())) {
            abort(403, 'Tiket ini bukan di tahap yang Anda tangani.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update($validated);

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' berhasil diperbarui.');
    }

    public function destroyTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        if (!in_array($ticket->status, ['waiting', 'calling'], true)) {
            return back()->with('error', 'Tiket ini sudah tidak bisa dihapus.');
        }

        if (!in_array($ticket->stage, $user->stagesForRole())) {
            abort(403, 'Tiket ini bukan di tahap yang Anda tangani.');
        }

        $ticket->update(['status' => 'cancelled']);

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' berhasil dibatalkan.');
    }
}