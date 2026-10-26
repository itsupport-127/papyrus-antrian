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
        $location = $this->resolveWorkLocation($user);
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $permissions = $user->getPermissions();
        $isFotografer = $user->isFotografer();

        if (!$location) {
            if ($user->isAdmin()) {
                return redirect()->route('operator.choose-location');
            }

            abort(403, 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
        }

        $stages = $user->stagesForRole();

        if (empty($stages)) {
            abort(403, 'Role Anda belum dikonfigurasi untuk melayani antrian.');
        }

        // PENTING: dulu loket/"studio" langsung ke-assign OTOMATIS diam-diam
        // begitu login (ambil yang pertama nganggur). Sekarang WAJIB dipilih
        // manual sekali per sesi login lewat halaman operator.choose-counter
        // -- begitu dipilih, nempel ke user ini sampai logout
        // (AuthController::logout yang ngelepasnya), abis itu harus pilih
        // lagi pas login berikutnya.
        $counter = $this->heldCounterForUser($user, $location, $stages);

        if (!$counter) {
            return redirect()->route('operator.choose-counter');
        }

        $counterMessage = null;

        $currentTicket = $counter?->currentTicket();

        $isAdmin = $user->isAdmin();
        $allLocationIds = Location::pluck('id')->toArray();
        $scopeLocationIds = $allLocationIds;
        // Fotografer JUGA lintas lokasi (bukan cuma CS & Kasir/Admin) --
        // lihat catatan di User::manageableLocationIds(). Tiket dari lokasi
        // BK (hasil check-in reservasi) yang sudah masuk tahap Sesi Foto
        // wajib tetap kelihatan di panel Fotografer walau lokasi "rumah"-nya
        // di-set ke PP.
        if (!$isAdmin && !$user->isCounterStaff() && $location) {
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

        // Tiket yang lagi "Siap-siap" (status preparing) sesuai scope --
        // dipakai buat card "Antrian Siap-siap" di panel Daftar Tunggu,
        // sama seperti di /locations/{location}/display. Lihat
        // docs/ALUR-ANTRIAN-STUDIO.md bagian "Siap-siap (pipelining)".
        $preparingTickets = Ticket::whereIn('location_id', $scopeLocationIds)
            ->where('stage', 'photo_session')
            ->where('status', 'preparing')
            ->with('counter')
            ->orderBy('called_at')
            ->get();

        // Tiket yang LAGI DIPANGGIL ke studio (status calling, stage
        // photo_session) -- dipakai buat card "Panggil Ulang" khusus
        // CS/Kasir, biar CS bisa bantu "nyodok" fotografer yang mungkin
        // belum ngeh tiketnya udah dipanggil (fotografer di dalam studio
        // gak bisa mantau kondisi ruang tunggu kayak CS di depan).
        $studioCallingTickets = Ticket::whereIn('location_id', $scopeLocationIds)
            ->where('stage', 'photo_session')
            ->where('status', 'calling')
            ->with('counter')
            ->orderBy('called_at')
            ->get();

        // Tiket yang "Sesi Foto Selesai" tapi FOTONYA MASIH DIEDIT
        // (ready_for_edit / editing) -- dipakai buat card "Antrian
        // Pembayaran" CS, biar CS tau ada tiket yang "lagi diproses,
        // bakal nyusul", bukan tiba-tiba muncul pas edit udah kelar.
        // READ-ONLY buat CS -- cuma info, belum bisa diapa-apain sampai
        // editing_completed beneran ditandai selesai sama fotografer.
        $editingInProgressTickets = Ticket::whereIn('location_id', $scopeLocationIds)
            ->where('stage', 'photo_session')
            ->whereIn('status', [Ticket::STATUS_READY_FOR_EDIT, Ticket::STATUS_EDITING])
            ->orderBy('updated_at')
            ->get();

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
            ->notSelectingPhotos()
            ->with(['counter'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Tiket reservasi yang SEDANG "Pilih Foto" (ada timer) -- tampil di panel sendiri,
        // belum masuk antrian "Siap Diedit" sampai ditandai selesai.
        $selectingTickets = Ticket::whereIn('location_id', $scopeLocationIds)
            ->where('stage', 'photo_session')
            ->selectingPhotos()
            ->with('counter')
            ->orderBy('select_queued_at')
            ->get();
        $photoSelectMinutes = (int) (Location::whereIn('id', $scopeLocationIds)->value('photo_select_minutes') ?: 15);

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

        // "Ambil Alih" — tiket ready_for_edit/editing di studio yang lagi
        // dipegang user ini, tapi `served_by`-nya BUKAN dia (kerjaan
        // fotografer sebelumnya yang belum kelar, mis. ditinggal semalam).
        // Sengaja BUKAN otomatis kelihatan di panel biasa (readyForEditTickets/
        // editingTickets di atas, yang tetap di-scope ke served_by = dia) --
        // ditampilkan terpisah sebagai tawaran eksplisit "mau lanjutin atau
        // biarin dulu", lihat OperatorController::takeoverTicket().
        $handoffTickets = $counter
            ? Ticket::where('counter_id', $counter->id)
                ->where('stage', 'photo_session')
                ->whereIn('status', [Ticket::STATUS_READY_FOR_EDIT, Ticket::STATUS_EDITING])
                ->notSelectingPhotos()
                ->where('served_by', '!=', $user->id)
                ->with(['counter', 'servedBy'])
                ->orderBy('updated_at')
                ->get()
            : collect();

        // Tiket yang sudah kadung lanjut ke kasir/selesai lewat "Lanjut ke
        // Kasir — Edit Menyusul" (skipToPaymentPendingEdit()), tapi editnya
        // masih belum ditandai selesai. Di-scope ke `editing_counter_id`
        // (BUKAN counter_id/served_by biasa -- keduanya sudah di-reset null
        // begitu tiket lanjut ke stage payment, lihat migrasi 2026_08_18_000001)
        // supaya tetap kelihatan oleh studio yang tanggung jawab, siapapun
        // yang lagi pegang studio itu sekarang.
        $pendingEditTickets = $counter
            ? Ticket::where('editing_counter_id', $counter->id)
                ->whereNull('edit_completed_at')
                ->whereIn('status', ['waiting', 'calling', 'serving', 'completed'])
                ->with(['editedBy'])
                ->orderBy('ready_for_edit_at')
                ->get()
            : collect();

        $isFotografer = $user->isFotografer();
        $isCsKasir = $user->isOperator() || $user->isAdmin();
        // Bug: sebelumnya gak di-scope ke lokasi -- padahal service_categories
        // WAJIB punya location_id (1 Layanan = milik 1 lokasi tertentu).
        // Kalau suatu saat ada lokasi ke-2 dengan Layanan beda, dropdown
        // "Layanan" di modal Tambah Reservasi bisa nampilin campuran dari
        // semua lokasi. Sekarang di-scope ke lokasi operator yang login.
        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->where('location_id', $location->id)->orderBy('name')->get();
        $bookingLocations = Location::where('is_active', true)
            ->get()
            ->filter(fn ($l) => $l->offersBooking())
            ->values();

        $activityKeys = $this->buildActivityKeys($locations->pluck('id')->all());
        $reminderPayload = $this->buildReminderPayload(
            $user, $scopeLocationIds, $isFotografer, $isCsKasir,
            $preparingTickets, $editingTickets, $readyForEditTickets, $pendingEditTickets
        );

        return view('operator.dashboard', compact(
            'activityKeys', 'reminderPayload',
            'location', 'locations', 'counter', 'counters', 'waitingByStage', 'preparingTickets', 'studioCallingTickets', 'editingInProgressTickets',
            'todayStats', 'todayBookings', 'custTickets', 'custBookings',
            'custSearch', 'custStatus', 'custStage', 'custDate',
            'bookingSearch', 'bookingDate',
            'currentTicket', 'counterMessage', 'isFotografer', 'isCsKasir', 'selectingTickets', 'photoSelectMinutes',
            'permissions', 'canManageBookings', 'serviceCategories', 'bookingLocations',
            'readyForEditTickets', 'editingTickets', 'handoffTickets', 'pendingEditTickets'
        ));
    }

    /**
     * Data PENGINGAT untuk dashboard user ini (dibaca JavaScript lewat
     * data-reminders di #stats-section, ikut ter-refresh tiap polling).
     * HANYA 3 jenis -- yang sering terlupa:
     *  - ready   : (CS & Kasir) tiket Siap-siap yang belum di-Ready
     *  - payment : (CS & Kasir) tiket yang sedang DILAYANI di Pembayaran oleh user ini
     *              -- mengingatkan kalau pembayaran sudah selesai tapi belum ditekan "Selesai"
     *  - edit    : (Fotografer) foto yang belum ditandai selesai diedit milik user ini
     *              (siap diedit, sedang diedit, dan "edit menyusul" setelah bayar duluan)
     * Kapan mulai & tiap berapa menit diulang diatur Admin (ReminderSetting).
     *
     * @param  list<int>  $scopeLocationIds
     */
    private function buildReminderPayload(User $user, array $scopeLocationIds, bool $isFotografer, bool $isCsKasir, $preparingTickets, $editingTickets, $readyForEditTickets, $pendingEditTickets): array
    {
        $items = [];
        $startOfToday = now()->startOfDay();
        $add = function (string $type, Ticket $t, $since) use (&$items, $startOfToday) {
            // Pengingat cuma untuk tiket HARI INI. Daftar "edit belum selesai" bisa memuat
            // tiket lama yang tidak pernah ditandai selesai (atau "edit menyusul" yang
            // memang dikerjakan belakangan) -- jangan diingatkan terus tiap beberapa menit.
            if ($t->created_at && $t->created_at->lt($startOfToday)) {
                return;
            }

            $items[] = [
                't' => $type,
                'id' => $t->id,
                'n' => $t->ticket_number,
                'c' => $t->customer_name,
                'since' => ($since ?? $t->updated_at ?? now())->getTimestamp(),
            ];
        };

        if ($isCsKasir) {
            foreach ($preparingTickets as $t) {
                if (!$t->ready_at) {
                    $add('ready', $t, $t->called_at);
                }
            }

            Ticket::whereIn('location_id', $scopeLocationIds)
                ->where('stage', 'payment')
                ->where('status', 'serving')
                ->where('served_by', $user->id)
                ->get()
                ->each(fn (Ticket $t) => $add('payment', $t, $t->payment_served_at ?? $t->served_at));
        }

        if ($isFotografer) {
            $seen = [];
            $addEdit = function (Ticket $t, $since) use (&$seen, $add) {
                if (isset($seen[$t->id])) {
                    return;
                }
                $seen[$t->id] = true;
                $add('edit', $t, $since);
            };
            foreach ($editingTickets as $t) {
                $addEdit($t, $t->editing_started_at ?? $t->ready_for_edit_at);
            }
            foreach ($readyForEditTickets as $t) {
                $addEdit($t, $t->ready_for_edit_at);
            }
            foreach ($pendingEditTickets as $t) {
                $addEdit($t, $t->ready_for_edit_at ?? $t->payment_skipped_pending_edit_at);
            }
        }

        return [
            'now' => now()->getTimestamp(),
            'settings' => \App\Models\ReminderSetting::forDashboard(),
            'items' => $items,
        ];
    }

    /**
     * "Sidik jari" aktivitas hari ini, dipakai dashboard untuk membunyikan bel
     * "ting" kapan pun ada yang berubah (tiket baru masuk daftar tunggu,
     * Siap-siap, Ready, masuk sesi foto, edit, pembayaran, selesai, batal,
     * reservasi baru/check-in). Tiap tiket/reservasi jadi satu kunci yang
     * memuat tahap + statusnya; dashboard membandingkan kunci antar-refresh --
     * kunci baru = ada aktivitas. Bukan bergantung pada WebSocket, jadi tetap
     * jalan lewat polling kalau Reverb mati.
     *
     * @param  list<int>  $locationIds
     */
    private function buildActivityKeys(array $locationIds): string
    {
        $today = now()->startOfDay();

        $tickets = Ticket::query()
            ->whereIn('location_id', $locationIds)
            ->where('created_at', '>=', $today)
            ->get(['id', 'stage', 'status', 'ready_at'])
            ->map(fn ($t) => 't' . $t->id . ':' . $t->stage . ':' . $t->status . ':' . ($t->ready_at ? 'r' : '-'));

        $bookings = Booking::query()
            ->whereIn('location_id', $locationIds)
            ->whereDate('booking_date', $today->toDateString())
            ->get(['id', 'status'])
            ->map(fn ($b) => 'b' . $b->id . ':' . $b->status);

        return $tickets->concat($bookings)->implode(',');
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
     * Loket/studio yang MEMANG udah dipegang user ini sekarang (masih aktif,
     * masih menangani tahap role-nya). null kalau belum pegang apa-apa --
     * berarti harus diarahkan ke halaman pilih studio dulu.
     */
    private function heldCounterForUser(User $user, Location $location, array $stages): ?Counter
    {
        return Counter::where('location_id', $location->id)
            ->where('is_active', true)
            ->where(function ($q) use ($stages) {
                foreach ($stages as $stage) {
                    $q->orWhereJsonContains('stages', $stage);
                }
            })
            ->where('current_operator_id', $user->id)
            ->first();
    }

    /**
     * Tentuin lokasi kerja user ini buat halaman-halaman operator.
     *
     * Admin sengaja TIDAK di-fallback di sini -- biar tetap eksplisit pilih
     * lokasi dulu lewat operator.choose-location (lihat pemanggil di
     * dashboard()), soalnya admin memang gak terikat ke satu lokasi (bisa
     * gonta-ganti "bertugas" di lokasi mana saja kapan saja).
     *
     * Role lain (operator/fotografer/viewer):
     * - Kalau PUNYA location_id tetap (di-set Admin lewat /admin/users,
     *   kolom "Loket Tetap" yang keisi) -> pakai itu.
     * - Kalau location_id-nya KOSONG (Admin sengaja kosongin "Loket Tetap"
     *   di /admin/users, opsi "-- Pilihan (tanya tiap login) --" -- lihat
     *   komentar di AdminController::storeUser()): sebelumnya ini bikin 403
     *   ("Anda belum ditugaskan ke lokasi manapun") LANGSUNG di sini,
     *   SEBELUM sempat sampai ke halaman pilih studio
     *   (operator.choose-counter) yang sebenarnya SUDAH nanya "studio mana"
     *   tiap sesi login -- jadi alur "gantian" yang dijanjikan komentar itu
     *   gak pernah beneran kepake, staf yang locket-nya sengaja dikosongin
     *   malah gak bisa masuk sama sekali. Sekarang di-fallback otomatis ke
     *   lokasi aktif pertama yang beneran punya loket buat tahap-tahap role
     *   ini (praktiknya cuma ada 1 lokasi yang punya loket sama sekali --
     *   lokasi reservasi/"BK" murni virtual buat penomoran, gak punya loket
     *   fisik), lalu dia lanjut seperti biasa ke alur pilih studio yang
     *   sudah ada (termasuk peringatan "studio ini lagi dipegang operator
     *   lain" di OperatorController::setCounter() kalau studio itu masih
     *   aktif dipakai orang lain).
     */
    private function resolveWorkLocation(User $user): ?Location
    {
        if ($user->location_id) {
            return $user->location;
        }

        $stages = $user->stagesForRole();

        if (empty($stages)) {
            return null;
        }

        return Location::where('is_active', true)
            ->whereHas('counters', function ($q) use ($stages) {
                $q->where('is_active', true)->where(function ($qq) use ($stages) {
                    foreach ($stages as $stage) {
                        $qq->orWhereJsonContains('stages', $stage);
                    }
                });
            })
            ->orderBy('name')
            ->first();
    }

    /**
     * Halaman "Pilih Studio" -- CS & Kasir maupun Fotografer mendarat di
     * sini kalau belum pegang loket apapun sesi ini (abis login pertama
     * kali, atau abis klik "Ganti Studio").
     */
    public function chooseCounter(): View|RedirectResponse
    {
        $user = auth()->user();
        $location = $this->resolveWorkLocation($user);

        if (!$location) {
            if ($user->isAdmin()) {
                return redirect()->route('operator.choose-location');
            }

            abort(403, 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
        }

        $stages = $user->stagesForRole();

        if (empty($stages)) {
            abort(403, 'Role Anda belum dikonfigurasi untuk melayani antrian.');
        }

        // Kalau ternyata udah pegang loket yang cocok (mis. balik lewat
        // tombol back browser), langsung lempar ke dashboard aja, gak usah
        // milih ulang.
        if ($this->heldCounterForUser($user, $location, $stages)) {
            return redirect()->route('operator.dashboard');
        }

        $counters = Counter::where('location_id', $location->id)
            ->where('is_active', true)
            ->where(function ($q) use ($stages) {
                foreach ($stages as $stage) {
                    $q->orWhereJsonContains('stages', $stage);
                }
            })
            ->with('currentOperator')
            ->orderBy('name')
            ->get();

        $stageLabels = collect($stages)
            ->map(fn ($s) => Counter::STAGE_LABELS[$s] ?? $s)
            ->implode(' / ');

        return view('operator.choose-counter', [
            'counters' => $counters,
            'location' => $location,
            'preferredCounterId' => $user->preferred_counter_id,
            'stageLabels' => $stageLabels,
        ]);
    }

    /**
     * Simpan pilihan studio dari halaman di atas.
     */
    public function setCounter(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $location = $this->resolveWorkLocation($user);

        // Bug: sebelumnya gak ada guard ini -- kalau $location null
        // (mis. lokasi user somehow kehapus/keubah antara buka halaman &
        // submit), baris di bawah ($location->id) bakal fatal error 500,
        // bukan pesan yang bisa dimengerti user.
        if (!$location) {
            abort(403, 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
        }

        $stages = $user->stagesForRole();

        if (empty($stages)) {
            abort(403, 'Role Anda belum dikonfigurasi untuk melayani antrian.');
        }

        $validated = $request->validate([
            'counter_id' => ['required', 'exists:counters,id'],
        ], [
            'counter_id.required' => 'Pilih salah satu studio dulu sebelum lanjut.',
            'counter_id.exists' => 'Studio itu gak ditemukan.',
        ]);

        // Bug: sebelumnya cek "loket masih kosong?" dan
        // update(['current_operator_id' => ...]) itu 2 langkah TERPISAH,
        // gak atomik -- kalau 2 orang mencet "Simpan" buat studio yang sama
        // persis di detik yang sama, keduanya bisa lolos cek "masih kosong"
        // sebelum salah satu sempat nyimpen, terus operator yang "kalah"
        // gak dikasih tahu apa-apa (silent overwrite, ngerasa berhasil
        // padahal studio itu sebenernya dipegang orang lain). Dibungkus
        // transaction + lockForUpdate() supaya proses cek-lalu-kunci ini
        // jadi 1 operasi atomik.
        $result = DB::transaction(function () use ($validated, $location, $stages, $user) {
            $counter = Counter::where('id', $validated['counter_id'])
                ->where('location_id', $location->id)
                ->where('is_active', true)
                ->where(function ($q) use ($stages) {
                    foreach ($stages as $stage) {
                        $q->orWhereJsonContains('stages', $stage);
                    }
                })
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                return ['ok' => false, 'message' => 'Studio itu gak valid buat role Anda.'];
            }

            if ($counter->current_operator_id && $counter->current_operator_id !== $user->id) {
                return ['ok' => false, 'message' => 'Studio itu baru saja dipegang operator lain. Pilih studio lain, atau minta operator itu logout dulu.'];
            }

            // Jaga-jaga: lepas dulu loket lain yang mungkin masih nempel ke
            // user ini, baru pegang yang baru dipilih -- satu user cuma
            // boleh pegang 1 loket dalam satu waktu.
            Counter::where('current_operator_id', $user->id)
                ->where('id', '!=', $counter->id)
                ->update(['current_operator_id' => null, 'is_paused' => false]);

            $counter->update(['current_operator_id' => $user->id]);

            return ['ok' => true];
        });

        if (!$result['ok']) {
            return back()->withErrors(['counter_id' => $result['message']]);
        }

        return redirect()->route('operator.dashboard');
    }

    /**
     * Lepas loket yang lagi dipegang, lalu balik ke halaman pilih studio --
     * ini yang dipanggil tombol "Ganti Studio" di Pintasan.
     */
    public function releaseCounter(): RedirectResponse
    {
        $user = auth()->user();

        $heldCounters = Counter::where('current_operator_id', $user->id)->get();

        // Bug: sebelumnya loket langsung dilepas tanpa cek apa-apa. Kalau
        // pas dilepas ada tiket yang lagi "Dipanggil"/"Sedang Dilayani" di
        // loket itu, tiket itu jadi gak kepegang siapa-siapa -- gak
        // kelihatan di panel operator manapun (dashboard cuma nampilin
        // tiket loket yang lagi DIPEGANG operator yang login), sampai ada
        // yang kebetulan milih loket yang sama lagi. Sekarang diblokir dulu
        // kalau masih ada tiket aktif, suruh selesaikan/lewati dulu.
        foreach ($heldCounters as $counter) {
            if ($counter->currentTicket()) {
                return redirect()->route('operator.dashboard')->withErrors([
                    'counter_id' => "Selesaikan atau lewati dulu pelanggan yang sedang dilayani di {$counter->name} sebelum ganti studio.",
                ]);
            }
        }

        Counter::where('current_operator_id', $user->id)
            ->update(['current_operator_id' => null, 'is_paused' => false]);

        return redirect()->route('operator.choose-counter');
    }

    public function createBooking(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin() && !$user->isFotografer()) {
            abort(403, 'Hanya CS, Kasir, atau Fotografer yang bisa menambahkan reservasi manual.');
        }

        $location = $this->resolveWorkLocation($user);
        $counter = $user->operatingCounter;

        if (!$location) {
            return back()->with('error', 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
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

        // Bug: sebelumnya gak di-scope ke lokasi -- padahal service_categories
        // WAJIB punya location_id. Di-scope ke $location (lokasi operator
        // sendiri) -- ini match 1:1 selama cuma ada 1 lokasi yang nerima
        // reservasi seperti sekarang. CATATAN kalau nanti nambah lokasi ke-2
        // yang juga nerima reservasi: dropdown "Layanan" di halaman ini
        // masih perlu diupdate biar ikut berubah waktu "Lokasi" tujuan
        // reservasi diganti (sekarang belum reaktif, filternya statis ke
        // lokasi operator login).
        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->where('location_id', $location->id)->orderBy('name')->get();

        return view('operator.bookings.create', [
            'location' => $location,
            'serviceCategories' => $serviceCategories,
            'locations' => $locations,
        ]);
    }

    /**
     * Rapikan input reservasi yang biasanya di-copy dari chat WA sebelum divalidasi:
     * - telepon: buang spasi, strip, titik, kurung ("0812-3456 7890" -> "081234567890")
     * - DP: "50.000", "Rp 50.000", "50rb", "50 ribu" -> 50000
     * - jumlah orang / kostum: ambil angkanya saja ("6 orang" -> 6)
     */
    /**
     * Form reservasi punya SATU kolom "Nama Pelanggan & Kode Order". Di sini
     * dipisah: token kode order (huruf + angka, mis. JG035474 / PJG-000615)
     * disimpan ke order_code, sisanya jadi nama. Kalau tidak ada token kode,
     * seluruh isian dipakai sebagai nama.
     */
    private function splitNameAndOrderCode(Request $request): void
    {
        if (!$request->has('customer_name')) {
            return;
        }

        $raw = trim(preg_replace('/\s+/', ' ', (string) $request->input('customer_name')));
        $code = null;
        $name = $raw;

        if (preg_match('/(?<![A-Za-z0-9])([A-Za-z]{1,5}-?\d{4,})(?![A-Za-z0-9])/', $raw, $m)) {
            $code = strtoupper($m[1]);
            $rest = trim(str_replace($m[0], ' ', $raw), " -–,/|·");
            $rest = trim(preg_replace('/\s+/', ' ', $rest));
            if ($rest !== '') {
                $name = $rest;
            } else {
                $code = null; // cuma kode tanpa nama -> biarkan utuh sebagai nama
            }
        }

        $request->merge(['customer_name' => $name, 'order_code' => $code]);
    }

    private function normalizeBookingInput(Request $request): void
    {
        $merge = [];

        if ($request->has('customer_phone')) {
            $merge['customer_phone'] = preg_replace('/[\s\-\.\(\)]+/', '', (string) $request->input('customer_phone'));
        }

        // Angka negatif (diawali "-") sengaja TIDAK dibersihkan -- dibiarkan apa
        // adanya supaya ditolak validasi, bukan diam-diam berubah jadi positif.
        if ($request->filled('dp_amount')) {
            $raw = strtolower(trim((string) $request->input('dp_amount')));
            if (str_starts_with($raw, '-')) {
                $merge['dp_amount'] = $raw;
            } elseif (preg_match('/^(?:rp\.?\s*)?(\d+)\s*(?:rb|ribu|k)$/', $raw, $m)) {
                $merge['dp_amount'] = (int) $m[1] * 1000;
            } else {
                $digits = preg_replace('/\D+/', '', $raw);
                $merge['dp_amount'] = $digits === '' ? null : $digits;
            }
        }

        foreach (['people_count', 'costume_changes'] as $field) {
            if ($request->filled($field)) {
                $raw = trim((string) $request->input($field));
                if (str_starts_with($raw, '-')) {
                    $merge[$field] = $raw;
                } else {
                    $digits = preg_replace('/\D+/', '', $raw);
                    $merge[$field] = $digits === '' ? null : $digits;
                }
            }
        }

        if ($request->has('order_code')) {
            $merge['order_code'] = strtoupper(trim((string) $request->input('order_code'))) ?: null;
        }

        if ($merge) {
            $request->merge($merge);
        }
    }

    /** Pesan error (Indonesia) untuk kolom detail reservasi. */
    private function bookingDetailMessages(): array
    {
        return [
            'people_count.integer' => 'Jumlah orang harus berupa angka.',
            'people_count.min' => 'Jumlah orang minimal 1.',
            'people_count.max' => 'Jumlah orang maksimal ' . config('booking.max_people', 500) . '.',
            'costume_changes.integer' => 'Tambahan kostum harus berupa angka.',
            'costume_changes.min' => 'Tambahan kostum tidak boleh negatif.',
            'costume_changes.max' => 'Tambahan kostum maksimal ' . config('booking.max_costume_changes', 20) . '.',
            'dp_amount.integer' => 'DP harus berupa angka (rupiah).',
            'dp_amount.min' => 'DP tidak boleh negatif.',
            'dp_amount.max' => 'DP terlalu besar, cek lagi nominalnya.',
            'booking_date.after_or_equal' => 'Tanggal reservasi harus hari ini atau sesudahnya.',
            'package_name.max' => 'Nama paket terlalu panjang.',
            'order_code.max' => 'Kode order terlalu panjang.',
        ];
    }

    /** Aturan validasi untuk kolom detail reservasi (dipakai tambah & edit). */
    private function bookingDetailRules(): array
    {
        return [
            'package_name' => ['nullable', 'string', 'max:100'],
            'people_count' => ['nullable', 'integer', 'min:1', 'max:' . config('booking.max_people', 500)],
            'costume_changes' => ['nullable', 'integer', 'min:0', 'max:' . config('booking.max_costume_changes', 20)],
            'dp_amount' => ['nullable', 'integer', 'min:0', 'max:' . config('booking.max_dp_amount', 100000000)],
            'order_code' => ['nullable', 'string', 'max:50'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['string', 'max:100'],
        ];
    }

    public function storeManualBooking(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin() && !$user->isFotografer()) {
            abort(403, 'Hanya CS, Kasir, atau Fotografer yang bisa menambahkan reservasi manual.');
        }

        $location = $this->resolveWorkLocation($user);
        $counter = $user->operatingCounter;

        if (!$location) {
            return back()->with('error', 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
        }

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        $this->splitNameAndOrderCode($request);
        $this->normalizeBookingInput($request);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            // Email dibuat opsional: format booking WA pelanggan memang tidak
            // menyertakan email, jadi CS tidak perlu mengarang email.
            'customer_email' => ['nullable', 'email', 'max:255'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            // Sebelumnya "notes" gak ada di sini -- padahal Booking model,
            // BookingService, dan migrasinya udah lama siap nerima ini.
            // Field "Order" di form-nya juga belum ada -- ditambah bareng
            // (lihat resources/views/operator/dashboard.blade.php).
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $this->bookingDetailRules(), [
            'customer_phone.regex' => 'Format nomor telepon tidak valid. Hanya angka (boleh diawali +), 8-15 digit.',
            'customer_name.required' => 'Isi nama pelanggan dan kode order.',
        ] + $this->bookingDetailMessages());

        $targetLocation = $location;
        if (!empty($validated['location_id'])) {
            $targetLocation = Location::findOrFail($validated['location_id']);
        }

        // Baru di sini kita validasi lokasi TUJUAN-nya (bukan lokasi asal
        // staff) yang menerima reservasi.
        if (!$targetLocation->offersBooking()) {
            return back()->withInput()->with('error', 'Lokasi "' . $targetLocation->name . '" tidak menerima reservasi. Pilih lokasi lain.');
        }

        // Jaga-jaga: Layanan yang dipilih HARUS beneran milik lokasi
        // tujuannya (bukan cuma aktif) -- form-nya sendiri sudah nge-filter
        // sesuai lokasi operator, tapi validasi ini tetap perlu di server
        // buat nutup celah kalau ada yang otak-atik value lewat DevTools.
        if (!empty($validated['service_category_id'])) {
            $categoryBelongsToLocation = \App\Models\ServiceCategory::where('id', $validated['service_category_id'])
                ->where('location_id', $targetLocation->id)
                ->exists();

            if (!$categoryBelongsToLocation) {
                return back()->withInput()->with('error', 'Layanan yang dipilih bukan milik lokasi "' . $targetLocation->name . '".');
            }
        }

        try {
            $this->bookingService->createManual($targetLocation, $validated);
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

        // Reservasi yang masih aktif (pending/confirmed) tanggalnya harus hari ini
        // atau sesudahnya. Reservasi yang sudah diproses (check-in/selesai/batal)
        // tetap bisa diedit dari Data Pelanggan untuk membetulkan data (nama,
        // telepon, paket, DP, dst) -- tanggal lampau boleh karena memang sudah lewat.
        $isActive = in_array($booking->status, ['pending', 'confirmed'], true);

        $this->splitNameAndOrderCode($request);
        $this->normalizeBookingInput($request);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            // Tanggal & jam tidak ada lagi di form -- kalau tidak dikirim, nilai lama dipertahankan.
            'booking_date' => $isActive ? ['sometimes', 'date', 'after_or_equal:today'] : ['sometimes', 'date'],
            'booking_time' => ['sometimes', 'date_format:H:i'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $this->bookingDetailRules(), $this->bookingDetailMessages());

        // Kolom ini NOT NULL (default 0) -- input kosong berarti 0, bukan null.
        // Hanya diisi kalau memang dikirim (form baru tidak punya kolom ini; nilai lama dipertahankan).
        foreach (['costume_changes', 'dp_amount'] as $numField) {
            if (array_key_exists($numField, $validated)) {
                $validated[$numField] = (int) ($validated[$numField] ?? 0);
            }
        }

        // Checkbox "Tambahan": kalau tidak ada yang dicentang browser tidak mengirim apa-apa,
        // jadi penanda extras_submitted dipakai supaya pilihan lama bisa dikosongkan.
        if ($request->has('extras_submitted')) {
            $validated['extras'] = array_values($validated['extras'] ?? []);
        }

        $location = $booking->location;
        $openTime = $location->open_time->format('H:i');
        $closeTime = $location->close_time->format('H:i');

        if ($isActive && isset($validated['booking_time']) && ($validated['booking_time'] < $openTime || $validated['booking_time'] > $closeTime)) {
            return back()->withInput()->with('error', "Jam harus di antara {$openTime} - {$closeTime} (jam operasional lokasi).");
        }

        $booking->update($validated);

        // Reservasi yang sudah jadi tiket: samakan data kontak di tiketnya supaya
        // Data Pelanggan tidak menampilkan dua versi nama/telepon yang berbeda.
        // (Order tiket sengaja TIDAK ditimpa -- diedit lewat Edit Tiket sendiri.)
        if ($booking->ticket_id && $booking->ticket) {
            $booking->ticket->update([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
            ]);
        }

        return back()->with('success', "Reservasi {$booking->customer_name} berhasil diperbarui.");
    }

    /**
     * HAPUS PERMANEN data reservasi -- bisa kapan saja, apa pun statusnya (aktif, sudah check-in,
     * selesai, batal). Tiket yang sudah terbit TIDAK ikut dihapus (riwayat & antrian tetap utuh),
     * hanya tidak lagi tercatat sebagai reservasi. Beda dengan destroyBooking() yang cuma membatalkan.
     */
    public function removeBooking(Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat menghapus reservasi.');
        }

        if (!in_array($booking->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Reservasi ini bukan milik lokasi Anda.');
        }

        $code = $booking->customer_name;
        $ticket = $booking->ticket;

        $booking->delete();

        $msg = "Reservasi {$code} dihapus.";
        if ($ticket) {
            $msg .= " Tiket {$ticket->ticket_number} tetap ada, tapi tidak lagi tercatat sebagai reservasi.";
        }

        return back()->with('success', $msg);
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

        $booking->cancel();

        $msg = "Reservasi {$booking->customer_name} berhasil dibatalkan.";
        if ($booking->dp_forfeited) {
            $msg .= ' ' . Booking::formatRupiah($booking->dp_amount) . ' DP ditandai hangus sesuai ketentuan.';
        }

        return back()->with('success', $msg);
    }

    /**
     * "Panggil" untuk reservasi — satu tombol untuk check-in (reservasi jadi
     * tiket antrian) SEKALIGUS langsung dipanggil ke loket CS, persis seperti
     * panggil manual di tahap Pembayaran & Cetak (bukan FIFO otomatis, karena
     * CS yang menentukan reservasi mana yang gilirannya sekarang).
     */
    /**
     * Check-in reservasi (tombol "Check-in" di dashboard CS). Membuat tiket yang
     * LANGSUNG masuk antrian Studio -- tidak lewat CS/Registrasi karena data & order
     * sudah ada di reservasi. Fotografer yang memanggil ke Siap-siap / Studio.
     */
    public function callBooking(Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir yang bisa melakukan check-in reservasi.');
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

        broadcast(new QueueUpdated($booking->location))->toOthers();

        return back()->with('success', "Reservasi {$booking->customer_name} check-in sebagai tiket {$ticket->ticket_number}, langsung masuk antrian Studio.");
    }

    public function callNext(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;
        $stage = $request->input('stage');
        $location = $this->resolveWorkLocation($user);

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
            ->where('status', 'waiting');

        $allLocationIds = Location::pluck('id')->toArray();
        $scopeLocationIds = $allLocationIds;
        // Samakan dengan dashboard() -- Fotografer juga lintas lokasi.
        if (!$user->isAdmin() && !$user->isCounterStaff() && $location) {
            $scopeLocationIds = [$location->id];
        }

        $filteredLocationId = $request->input('location_id');
        if ($filteredLocationId && in_array((int) $filteredLocationId, $allLocationIds, true)) {
            $scopeLocationIds = [(int) $filteredLocationId];
        }

        $query->whereIn('location_id', $scopeLocationIds);

        // Kalau tombolnya minta tahap spesifik (tiap kotak antrian per-lokasi
        // di dashboard sekarang selalu kirim `stage`), gak perlu prioritas
        // apa-apa -- tinggal ambil dari tahap itu saja.
        //
        // Tapi kalau TIDAK ada `stage` (tombol umum "Panggil Antrian
        // Berikutnya" di panel loket CS/Kasir yang pegang >1 tahap sekaligus,
        // mis. registration + payment), prioritaskan tahap SELAIN Pembayaran
        // & Cetak Foto lebih dulu. Alasan: proses cetak foto durasinya gak
        // pasti (bisa ada yang kelar duluan drpd pelanggan yang lebih lama
        // nunggu di Registrasi), jadi kalau cuma diurut "tertua duluan"
        // lintas tahap, antrian Registrasi bisa keduluan sama antrian
        // Pembayaran yang kebetulan lebih tua -- padahal Pembayaran sudah
        // biasa dipanggil manual per tiket lewat tombol "Panggil" di
        // kartunya sendiri (lihat callSpecific()), jadi gak masalah kalau
        // gak keambil duluan di sini.
        $multiStageAutoCall = false;

        if ($stage && in_array($stage, Ticket::STAGE_ORDER, true)) {
            $query->where('stage', $stage);
        } elseif (!empty($counter->stages)) {
            $query->whereIn('stage', $counter->stages);
            $multiStageAutoCall = true;
        }

        if ($multiStageAutoCall) {
            $query->orderByRaw("CASE WHEN stage = 'payment' THEN 1 ELSE 0 END");
        }

        $query->orderByRaw('COALESCE(last_skipped_at, created_at)');

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

        if (!$user->isAdmin() && !$user->isCounterStaff() && $ticket->location_id !== $user->location_id) {
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

        // Otomatis panggil "Siap-siap" berikutnya DIHAPUS atas permintaan --
        // sekarang SEMUA pemanggilan "Siap-siap" 100% manual lewat tombol
        // ("Panggil Siap-siap" di card Antrian Siap-siap/Sesi Foto, atau
        // klik tiket tertentu langsung di Daftar Tunggu). Fotografer yang
        // pegang kendali penuh kapan & siapa yang mau di-siap-siapin.

        return back()->with('success', 'Mulai melayani tiket ' . $ticket->ticket_number . '.');
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

        if ($ticket->isSelectingPhotos()) {
            return back()->with('error', 'Tiket ' . $ticket->ticket_number . ' masih di tahap Pilih Foto. Klik "Selesai Pilih Foto" dulu.');
        }

        if ($ticket->status === 'serving' && $ticket->isReservation()) {
            return back()->with('error', 'Tiket reservasi harus lewat Pilih Foto dulu. Klik "Sesi Foto Selesai" supaya masuk tahap Pilih Foto.');
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

        // Pindahkan status ke "ready_for_edit". editing_counter_id diisi di
        // sini juga (bukan cuma di startEditing()) karena tiket bisa aja
        // langsung di-skip ke kasir (skipToPaymentPendingEdit()) dari status
        // ready_for_edit ini tanpa pernah lewat startEditing() -- lihat
        // migrasi 2026_08_18_000001 kenapa kolom ini perlu ada sejak awal.
        $ticket->update([
            'status' => Ticket::STATUS_READY_FOR_EDIT,
            'photo_completed_at' => now(),
            'ready_for_edit_at' => now(),
            'editing_counter_id' => $ticket->editing_counter_id ?? $ticket->counter_id,
        ]);

        // Broadcast perubahan status — TicketStatusChanged buat halaman
        // status pelanggan itu sendiri (channel ticket.{id}), QueueUpdated
        // buat dashboard operator (channel location.{id}).
        broadcast(new TicketStatusChanged($ticket))->toOthers();

        // Tiket RESERVASI: setelah sesi foto masuk tahap "Pilih Foto" dulu (ada timer),
        // baru boleh ke proses edit. Tiket biasa langsung ke antrian "Siap Diedit".
        if ($ticket->isReservation()) {
            $ticket->queuePhotoSelection();
            broadcast(new QueueUpdated($ticket->location))->toOthers();

            return back()->with('success', 'Sesi foto tiket ' . $ticket->ticket_number . ' selesai. Dilempar ke CS untuk Pilih Foto.');
        }

        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Sesi foto tiket ' . $ticket->ticket_number . ' selesai. Menunggu diproses edit.');
    }

    /** Petugas yang boleh mengurus Pilih Foto: Admin, CS & Kasir, Fotografer. */
    private function canHandlePhotoSelection(Ticket $ticket): bool
    {
        $user = auth()->user();

        return ($user->isAdmin() || $user->isOperator() || $user->isFotografer())
            && in_array($ticket->location_id, $user->manageableLocationIds(), true);
    }

    /** CS memanggil pelanggan ke Pilih Foto (juga dipakai buat Panggil Ulang). */
    public function callPhotoSelection(Ticket $ticket): RedirectResponse
    {
        if (!$this->canHandlePhotoSelection($ticket)) {
            abort(403, 'Anda tidak punya akses ke tiket ini.');
        }

        if (!$ticket->isSelectingPhotos() || $ticket->select_started_at) {
            return back()->with('error', 'Tiket ini tidak sedang menunggu dipanggil untuk Pilih Foto.');
        }

        $ticket->callPhotoSelection();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' dipanggil ke Pilih Foto.');
    }

    /** "Mulai Pilih Foto": pelanggan sudah duduk di komputer preview, timer jalan. */
    public function beginPhotoSelection(Ticket $ticket): RedirectResponse
    {
        if (!$this->canHandlePhotoSelection($ticket)) {
            abort(403, 'Anda tidak punya akses ke tiket ini.');
        }

        if (!$ticket->isSelectingPhotos() || $ticket->select_started_at) {
            return back()->with('error', 'Tiket ini tidak bisa dimulai Pilih Foto-nya.');
        }

        $ticket->beginPhotoSelection();

        return back()->with('success', 'Pilih Foto tiket ' . $ticket->ticket_number . ' dimulai, timer ' . max(1, (int) $ticket->location->photo_select_minutes) . ' menit.');
    }

    /** "Selesai Pilih Foto": langsung ke Pembayaran, edit menyusul (estimasi dari paket). */
    public function finishPhotoSelection(Ticket $ticket): RedirectResponse
    {
        if (!$this->canHandlePhotoSelection($ticket)) {
            abort(403, 'Anda tidak punya akses ke tiket ini.');
        }

        if (!$ticket->isSelectingPhotos()) {
            return back()->with('error', 'Tiket ini tidak sedang di tahap Pilih Foto.');
        }

        $ticket->finishPhotoSelection($ticket->served_by ?? auth()->id());
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Pilih Foto tiket ' . $ticket->ticket_number . ' selesai. Lanjut ke Pembayaran, hasil edit menyusul.');
    }

    /** Estimasi selesai + catatan progres edit (dilihat pelanggan di tiketnya). */
    public function updateEditProgress(Request $request, Ticket $ticket): RedirectResponse
    {
        if (!$this->canHandlePhotoSelection($ticket)) {
            abort(403, 'Anda tidak punya akses ke tiket ini.');
        }

        if (!$ticket->payment_skipped_pending_edit_at || $ticket->edit_completed_at) {
            return back()->with('error', 'Tiket ini tidak sedang menunggu hasil edit.');
        }

        $validated = $request->validate([
            'edit_eta_date' => ['nullable', 'date'],
            'edit_note' => ['nullable', 'string', 'max:255'],
        ], [
            'edit_eta_date.date' => 'Format tanggal estimasi tidak valid.',
            'edit_note.max' => 'Catatan progres maksimal 255 karakter.',
        ]);

        $ticket->update([
            'edit_eta_date' => $validated['edit_eta_date'] ?? null,
            'edit_note' => filled($validated['edit_note'] ?? null) ? trim($validated['edit_note']) : null,
        ]);

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        return back()->with('success', 'Progres edit tiket ' . $ticket->ticket_number . ' diperbarui.');
    }

    /** "+ menit": tambah waktu Pilih Foto untuk satu tiket (CS, Admin, Fotografer). */
    public function extendPhotoSelection(Request $request, Ticket $ticket): RedirectResponse
    {
        if (!$this->canHandlePhotoSelection($ticket)) {
            abort(403, 'Anda tidak punya akses ke tiket ini.');
        }

        if (!$ticket->isSelectingPhotos()) {
            return back()->with('error', 'Tiket ini tidak sedang di tahap Pilih Foto.');
        }

        $minutes = (int) $request->validate(['minutes' => ['required', 'integer', 'min:1', 'max:60']])['minutes'];
        $ticket->extendPhotoSelection($minutes);

        return back()->with('success', "Waktu Pilih Foto tiket {$ticket->ticket_number} ditambah {$minutes} menit.");
    }

    /** Atur durasi default Pilih Foto (menit) -- khusus CS & Kasir dan Admin. */
    public function updatePhotoSelectSetting(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isOperator()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang bisa mengatur timer Pilih Foto.');
        }

        $minutes = (int) $request->validate([
            'photo_select_minutes' => ['required', 'integer', 'min:1', 'max:120'],
        ], [
            'photo_select_minutes.required' => 'Isi durasi Pilih Foto (menit).',
            'photo_select_minutes.min' => 'Durasi minimal 1 menit.',
            'photo_select_minutes.max' => 'Durasi maksimal 120 menit.',
        ])['photo_select_minutes'];

        Location::query()->update(['photo_select_minutes' => $minutes]);

        return back()->with('success', "Durasi Pilih Foto diatur {$minutes} menit untuk tiket berikutnya.");
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

        // Kalau ini diklik dari status 'editing' (jalur normal, BUKAN lewat
        // "Lanjut ke Kasir — Edit Menyusul"), berarti editnya beneran udah
        // kelar sekarang -- tandai edit_completed_at supaya tiket ini gak
        // nyangkut di panel "Sudah Dibayar, Edit Belum Selesai".
        if ($ticket->status === Ticket::STATUS_EDITING && !$ticket->edit_completed_at) {
            $ticket->update(['edit_completed_at' => now()]);
        }

        $wasLastStage = $ticket->stage === 'payment';
        $ticket->advanceStage();
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', $wasLastStage
            ? 'Tiket ' . $ticket->ticket_number . ' selesai — pembayaran & cetak foto tuntas.'
            : 'Tiket ' . $ticket->ticket_number . ' lanjut ke tahap Pembayaran & Cetak.');
    }

    /**
     * "Ambil Alih" — fotografer yang lagi pegang studio ini klaim 1 tiket
     * ready_for_edit/editing yang ditinggal fotografer sebelumnya (beda
     * `served_by`), supaya bisa dia lanjutin. Lihat panel "Kerjaan dari
     * Shift Sebelumnya" di dashboard & $handoffTickets di atas.
     */
    public function takeoverTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter || $ticket->counter_id !== $counter->id) {
            return back()->with('error', 'Tiket ini bukan dari studio yang sedang Anda pegang.');
        }

        if ($ticket->stage !== 'photo_session' || !in_array($ticket->status, [Ticket::STATUS_READY_FOR_EDIT, Ticket::STATUS_EDITING], true)) {
            return back()->with('error', 'Tiket ini sudah tidak bisa diambil alih.');
        }

        $ticket->update(['served_by' => $user->id]);

        broadcast(new TicketStatusChanged($ticket))->toOthers();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' berhasil diambil alih.');
    }

    /**
     * Versi sekaligus dari takeoverTicket() -- ambil semua tiket "nganggur"
     * di studio yang lagi dipegang user ini dalam satu klik.
     */
    public function takeoverAllTickets(): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        $tickets = Ticket::where('counter_id', $counter->id)
            ->where('stage', 'photo_session')
            ->whereIn('status', [Ticket::STATUS_READY_FOR_EDIT, Ticket::STATUS_EDITING])
            ->where('served_by', '!=', $user->id)
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->update(['served_by' => $user->id]);
            broadcast(new TicketStatusChanged($ticket))->toOthers();
        }

        return back()->with('success', $tickets->isEmpty()
            ? 'Tidak ada tiket yang perlu diambil alih.'
            : $tickets->count() . ' tiket berhasil diambil alih.');
    }

    /**
     * "Lanjut ke Kasir — Edit Menyusul" -- lihat Ticket::skipToPaymentPendingEdit().
     */
    public function skipToPaymentPendingEdit(Ticket $ticket, Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Fotografer pemilik tiket ATAU CS/Kasir/Admin (CS bisa nolong
        // pelanggan yang mau bayar duluan tanpa nunggu fotografer sempat
        // mencet). Role lain (viewer) tetap ditolak.
        $isOwner = $ticket->served_by === $user->id;
        $isCsKasir = $user->isOperator() || $user->isAdmin();
        if (!$isOwner && !$isCsKasir) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->stage !== 'photo_session' || !in_array($ticket->status, [Ticket::STATUS_READY_FOR_EDIT, Ticket::STATUS_EDITING], true)) {
            return back()->with('error', 'Status tiket tidak valid.');
        }

        if ($ticket->isSelectingPhotos()) {
            return back()->with('error', 'Tiket ' . $ticket->ticket_number . ' masih di tahap Pilih Foto. Selesaikan Pilih Foto dulu.');
        }

        // `edited_by` = fotografer yang pegang tiket, BUKAN CS yang kebetulan
        // mencet tombolnya (dipakai buat laporan kerja per staf).
        $ticket->skipToPaymentPendingEdit($ticket->served_by ?? $user->id, $request->input('wait_preference'));
        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' lanjut ke kasir untuk bayar duluan. Hasil edit menyusul.');
    }

    /**
     * "Edit Selesai" -- dipencet dari panel "Sudah Dibayar, Edit Belum
     * Selesai" untuk tiket yang tadinya lewat skipToPaymentPendingEdit().
     * Sengaja dicek lewat `editing_counter_id` (bukan served_by, yang udah
     * ke-reset null begitu tiket lanjut ke stage payment).
     */
    public function markEditCompleted(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter || $ticket->editing_counter_id !== $counter->id) {
            return back()->with('error', 'Tiket ini bukan tanggung jawab studio yang sedang Anda pegang.');
        }

        if ($ticket->edit_completed_at) {
            return back()->with('error', 'Edit tiket ini sudah ditandai selesai sebelumnya.');
        }

        $ticket->markEditCompleted();

        return back()->with('success', 'Edit tiket ' . $ticket->ticket_number . ' ditandai selesai. Pelanggan sudah diberi tahu.');
    }

    /**
     * Kasir/CS klik "Sudah Diambil" begitu hasil cetak beneran diserahkan
     * ke pelanggan -- khusus tiket yang lewat "Lanjut ke Kasir, Edit
     * Menyusul" (editing_counter_id terisi). Tiket normal (gak pernah lewat
     * jalur itu) gak butuh tombol ini -- serah-terimanya udah tersirat
     * selesai begitu completeTicket() diklik dalam 1x kunjungan yang sama.
     */
    public function markPickedUp(Ticket $ticket): RedirectResponse
    {
        if (!$ticket->isReadyForPickup()) {
            return back()->with('error', 'Tiket ini belum siap ditandai diambil (editnya belum selesai, atau sudah pernah ditandai diambil sebelumnya).');
        }

        $ticket->markPickedUp();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' (' . $ticket->order_code . ') ditandai sudah diambil pelanggan.');
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
            return back()->with('error', 'Konfirmasi pesanan dulu (tombol "Konfirmasi Pesanan") sebelum lanjut ke Sesi Foto.');
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

        // PERUBAHAN: sebelumnya cuma boleh manggil ulang tiket MILIK SENDIRI
        // (served_by === user ini). Sekarang Admin & CS/Kasir (operator)
        // juga boleh manggil ulang tiket siapa pun yang lagi "calling" --
        // dipakai buat bantu "nyodok" fotografer yang kayaknya lupa/nggak
        // ngeh tiketnya udah dipanggil, karena CS yang di depan lebih bisa
        // mantau kondisi ruang tunggu ketimbang fotografer di dalam studio.
        // Fotografer/staf lain TETAP cuma boleh manggil ulang tiket sendiri.
        $canRecallAnyTicket = $user->isAdmin() || $user->isOperator();

        if ($ticket->status !== 'calling') {
            return back()->with('error', 'Tidak dapat memanggil ulang tiket ini.');
        }

        if (!$canRecallAnyTicket && $ticket->served_by !== $user->id) {
            return back()->with('error', 'Tidak dapat memanggil ulang tiket ini.');
        }

        // Kalau yang manggil ulang BUKAN loket asal tiket ini (mis. CS
        // manggilin punya fotografer), tetap pakai loket ASLI tiket itu
        // (bukan loket CS) -- biar pengumuman & counter_id-nya tetap benar
        // nunjuk ke studio yang bersangkutan, CS cuma "membantu memicu".
        $counter = $ticket->counter ?? $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket tiket ini tidak diketahui.');
        }

        $ticket->call($counter, $ticket->servedBy ?: $user);

        return back()->with('success', 'Memanggil ulang tiket ' . $ticket->ticket_number);
    }

    /**
     * "Panggil Ulang" versi CS/Kasir KHUSUS buat tiket yang MASIH
     * "Siap-siap" (status `preparing`, BELUM dinaikkan fotografer jadi
     * "Dipanggil"). Beda dari recallTicket() di atas (yang buat status
     * `calling`) -- ini gak mengubah status/counter_id, cuma nge-nudge
     * ulang notifikasi "bersiap-siap" ke pelanggan. Dipakai CS di depan
     * yang bisa lihat langsung kondisi ruang tunggu (mis. pelanggan
     * kelihatan belum ke ruang ganti), baik buat tiket "Siap-siap" yang
     * CS sendiri yang mulai (counter_id null) maupun yang sudah dikunci
     * fotografer ke studio tertentu (counter_id terisi) -- fotografer
     * pemilik studio itu juga tetap boleh, biar gak perlu batalkan dulu.
     */
    public function recallPreparingTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if ($ticket->status !== 'preparing') {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus Siap-siap.');
        }

        $isCsKasir = $user->isOperator() || $user->isAdmin();
        $ownsStudio = $counter && ($ticket->counter_id === null || $ticket->counter_id === $counter->id);

        if (!$isCsKasir && !$ownsStudio) {
            return back()->with('error', 'Tiket ini bukan Siap-siap di loket Anda.');
        }

        $ticket->recallPrepare($user);

        return back()->with('info', 'Tiket ' . $ticket->ticket_number . ' dipanggil ulang untuk bersiap-siap.');
    }

    /**
     * CS/Kasir menandai tiket Siap-siap sebagai "Ready" -- artinya pelanggan
     * SUDAH ada di depan & siap masuk studio. Cuma CS (yang melihat ruang
     * depan) yang menentukan ini; fotografer baru boleh "Panggil Sekarang"
     * setelah tiketnya Ready (lihat callPreparingTicket()).
     */
    public function markReady(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            return back()->with('error', 'Hanya CS & Kasir yang bisa menandai Ready.');
        }

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        if ($ticket->status !== 'preparing') {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus Siap-siap.');
        }

        if ($ticket->ready_at) {
            return back()->with('info', 'Tiket ' . $ticket->ticket_number . ' sudah Ready.');
        }

        $ticket->update(['ready_at' => now(), 'ready_by' => $user->id]);

        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' Ready -- siap dipanggil fotografer ke studio.');
    }

    /** Batalkan tanda Ready (mis. pelanggannya ternyata pergi/belum siap). */
    public function unmarkReady(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isOperator() && !$user->isAdmin()) {
            return back()->with('error', 'Hanya CS & Kasir yang bisa membatalkan Ready.');
        }

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        if ($ticket->status !== 'preparing') {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus Siap-siap.');
        }

        $ticket->update(['ready_at' => null, 'ready_by' => null]);

        broadcast(new QueueUpdated($ticket->location))->toOthers();

        return back()->with('info', 'Tanda Ready tiket ' . $ticket->ticket_number . ' dibatalkan.');
    }

    /**
     * Catatan bersama: bisa diisi/diubah dari tahap manapun (CS, kasir, fotografer)
     * dan langsung terlihat di semua tahap lain untuk tiket yang sama.
     *
     * Begitu sesi foto sudah mulai (notes_locked_at terisi), catatan ini
     * terkunci -- gembok ini buat nyegah ke-edit/ke-klik GAK SENGAJA di
     * tengah sesi foto, BUKAN buat ngebatasin siapa yang boleh -- operator
     * mana pun yang lagi megang tiket ini boleh buka kuncinya sendiri
     * (lihat unlockNotes() di bawah), gak perlu Admin.
     */
    public function updateNotes(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->served_by !== $user->id) {
            return back()->with('error', 'Tiket ini bukan milik Anda.');
        }

        if ($ticket->notes_locked_at) {
            return back()->with('error', 'Catatan ini masih terkunci. Klik "Buka Kunci" dulu buat edit.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->update(['notes' => $validated['notes'] ?? null]);

        return back()->with('success', 'Catatan tiket ' . $ticket->ticket_number . ' diperbarui.');
    }

    /**
     * Buka gembok catatan -- operator mana pun (bukan cuma Admin) yang lagi
     * megang tiket ini boleh klik ini. Gemboknya sendiri gak dihapus
     * konsepnya, cuma dibuka -- kalau tiket lanjut lagi (mis. balik ke sesi
     * foto), tetap kekunci ulang otomatis kayak biasa (lihat Ticket::call()).
     */
    public function unlockNotes(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        // Pemegang tiket, atau (kalau tiketnya tidak sedang dipegang orang lain
        // di tengah sesi) operator/admin lokasi ini -- lihat Ticket::canUnlockNotesBy().
        if (!$ticket->canUnlockNotesBy($user)) {
            return back()->with('error', 'Tiket ini sedang dipegang staf lain. Tunggu sampai selesai, atau minta staf itu yang membuka kuncinya.');
        }

        $ticket->update(['notes_locked_at' => null]);

        return back()->with('success', 'Kunci catatan tiket ' . $ticket->ticket_number . ' dibuka.');
    }

    /**
     * CS klik "Konfirmasi Pesanan" setelah pelanggan setuju dengan paket
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

        // Sengaja ditolak kalau kolom "Order" masih kosong -- tanpa ini,
        // CS bisa kepencet "Konfirmasi Pesanan" sebelum sempat isi
        // catatan sama sekali, dan tiket lanjut ke Sesi Foto tanpa
        // fotografer tau paket/permintaan apa yang dipesan.
        if (blank($ticket->notes)) {
            return back()->with('error', 'Isi dulu kolom "Order" sebelum konfirmasi pesanan.');
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
        $counter = $user->operatingCounter;

        // Tiket yang lagi "preparing" (Siap-siap) dikunci per STUDIO
        // (counter_id) yang manggil dia ke Siap-siap, BUKAN per staf yang
        // mencetnya -- biar tetap bisa dibatalkan kalau ada pergantian
        // staf/shift di studio yang sama, tapi tetap gak bisa diutak-atik
        // studio lain. Status lain (calling/serving) tetap dicek per-staf
        // (served_by) seperti semula, karena itu tengah sesi aktif satu
        // orang yang sama.
        if ($ticket->status === 'preparing') {
            // Siap-siap TIDAK DIKUNCI ke studio manapun (lihat catatan
            // panjang di callPreparingTicket()) -- jadi Batalkan Siap-siap
            // juga konsisten: studio APA PUN (bukan cuma yang tadinya
            // "punya" tiket ini), dan CS/Kasir, boleh membatalkan. Yang
            // penting cuma harus punya loket aktif (biar ada auth yang
            // jelas), tiket-nya sendiri gak perlu dicek "punya siapa".
            if (!$counter) {
                return back()->with('error', 'Tiket ini bukan Siap-siap di loket Anda.');
            }
        } elseif ($ticket->served_by !== $user->id) {
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
            ->notSelectingPhotos()
            ->lockForUpdate()
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->startEditing();
            $editedCount++;
        }

        return back()->with('success', "{$editedCount} tiket siap diedit telah diproses.");
    }

    /**
     * Panggil "Siap-siap" TAPI dari sisi CS/Kasir (bukan fotografer) --
     * SENGAJA GAK milih studio tujuan sama sekali (beda dari draft
     * sebelumnya yang sempat auto-pilih "studio paling kosong") -- tiket
     * cuma ditandai "Siap-siap" secara GENERIK ("ke ruang ganti" doang,
     * gak nyebut nama studio ke pelanggan), karena fotografer BELUM ada
     * yang manggil dia ke studio manapun. Nanti begitu ada studio yang
     * kosong & manggil dia jadi "Dipanggil", BARU studio itu resmi
     * ditentukan (lihat callPreparingTicket()).
     *
     * Alasan fitur ini ada: CS/front-desk yang bisa lihat kondisi ruang
     * tunggu asli, sedangkan fotografer di dalam studio nggak bisa. Jadi
     * CS bisa bantu "dorong" antrian biar nggak macet nunggu 1 fotografer
     * yang mungkin belum sempat manggil.
     */
    public function callNextToPrepareAuto(): RedirectResponse
    {
        $user = auth()->user();
        $nextTicket = $this->autoCallNextToPrepareGeneric($user);

        if (!$nextTicket) {
            return back()->with('info', 'Tidak ada antrian menunggu untuk dipanggil siap-siap.');
        }

        return back()->with('success', "Tiket {$nextTicket->ticket_number} dipanggil untuk Siap-siap.");
    }

    /**
     * Sama seperti callNextToPrepareAuto(), tapi buat TIKET TERTENTU
     * (dipilih manual dari daftar tunggu), bukan otomatis FIFO.
     */
    public function callSpecificToPrepareAuto(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if ($ticket->stage !== 'photo_session') {
            return back()->with('error', 'Tiket ini bukan untuk tahap Sesi Foto.');
        }

        $called = false;
        DB::transaction(function () use ($ticket, $user, &$called) {
            $fresh = Ticket::whereKey($ticket->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status !== 'waiting') {
                return;
            }
            $fresh->callToPrepare(null, $user);
            $called = true;
        });

        if (!$called) {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus menunggu (mungkin baru saja dipanggil operator lain).');
        }

        return back()->with('success', "Tiket {$ticket->ticket_number} dipanggil untuk Siap-siap.");
    }

    /**
     * Versi generik dari autoCallNextToPrepare() -- dipakai CS, gak terikat
     * ke 1 counter/studio tertentu (CS emang gak nentuin studio, lihat
     * catatan di callNextToPrepareAuto()).
     */
    private function autoCallNextToPrepareGeneric(User $user): ?Ticket
    {
        $scopeLocationIds = ($user->isAdmin() || $user->isCounterStaff())
            ? Location::pluck('id')
            : collect([$user->location_id]);

        $nextTicket = null;

        DB::transaction(function () use ($user, $scopeLocationIds, &$nextTicket) {
            $ticket = Ticket::where('status', 'waiting')
                ->whereIn('location_id', $scopeLocationIds)
                ->where('stage', 'photo_session')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return;
            }

            $ticket->callToPrepare(null, $user);
            $nextTicket = $ticket;
        });

        return $nextTicket;
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

        // Sengaja TIDAK mensyaratkan ada tiket aktif (calling/serving) lagi
        // di loket ini -- "Siap-siap" bukan cuma buat pipelining selagi
        // fotografer sibuk, tapi tahap WAJIB buat pelanggan bersiap (ganti
        // baju, dsb) sebelum masuk Sesi Foto. Jadi tetap boleh dipanggil
        // walau loket lagi kosong sama sekali (mis. tiket PERTAMA hari itu).
        // Sengaja TIDAK mensyaratkan ada tiket aktif (calling/serving) lagi
        // di loket ini -- "Siap-siap" bukan cuma buat pipelining selagi
        // fotografer sibuk, tapi tahap WAJIB buat pelanggan bersiap (ganti
        // baju, dsb) sebelum masuk Sesi Foto. Jadi tetap boleh dipanggil
        // walau loket lagi kosong sama sekali (mis. tiket PERTAMA hari itu).
        //
        // Batasan "1 Siap-siap per loket dalam satu waktu" SENGAJA DICABUT
        // (dulu ada di sini) -- operator lapangan yang paling tau kondisi
        // ruang ganti/nyata di lokasi masing-masing (bisa jadi ada 2-3
        // ruang ganti per studio), jadi keputusan berapa banyak yang boleh
        // "Siap-siap" bersamaan diserahkan penuh ke operator, bukan
        // dipaksa sistem.
        $nextTicket = $this->autoCallNextToPrepare($counter, $user);

        if (!$nextTicket) {
            return back()->with('info', 'Tidak ada antrian menunggu untuk dipanggil siap-siap.');
        }

        return back()->with('success', "Tiket {$nextTicket->ticket_number} dipanggil untuk Siap-siap.");
    }

    /**
     * Panggil TIKET TERTENTU (bukan otomatis FIFO) untuk jadi "Siap-siap" --
     * dipakai dari tombol per-tiket di card "Daftar Tunggu" pas fotografer
     * mau pilih bebas siapa yang mau di-siap-siapin duluan, gak harus
     * ngikutin urutan antrian paling depan.
     */
    public function callSpecificToPrepare(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if ($counter->is_paused) {
            return back()->with('error', 'Loket Anda sedang istirahat. Aktifkan lagi dulu sebelum memanggil Siap-siap.');
        }

        if (!in_array('photo_session', $counter->stages ?? [], true)) {
            return back()->with('error', 'Loket Anda tidak menangani tahap Sesi Foto.');
        }

        // Sama seperti callNextToPrepare() -- sengaja TIDAK mensyaratkan ada
        // tiket aktif (calling/serving) lagi di loket ini, biar tiket
        // PERTAMA hari itu pun tetap lewat "Siap-siap" dulu, bukan langsung
        // ke Sesi Foto. Batasan "1 Siap-siap per loket" juga SENGAJA
        // DICABUT -- lihat catatan lengkap di callNextToPrepare().
        if ($ticket->stage !== 'photo_session') {
            return back()->with('error', 'Tiket ini bukan untuk tahap Sesi Foto.');
        }

        $called = false;
        DB::transaction(function () use ($ticket, $counter, $user, &$called) {
            $fresh = Ticket::whereKey($ticket->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status !== 'waiting') {
                return;
            }
            $fresh->callToPrepare($counter, $user);
            $called = true;
        });

        if (!$called) {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus menunggu (mungkin baru saja dipanggil operator lain).');
        }

        return back()->with('success', "Tiket {$ticket->ticket_number} dipanggil untuk Siap-siap.");
    }

    /**
     * Naikkan tiket yang sudah "Siap-siap" (`preparing`) jadi "Dipanggil"
     * (`calling`) di loket ini -- sepenuhnya manual, dipencet fotografer
     * sendiri kapan pun dia siap (biasanya begitu loket kosong abis
     * "Foto Selesai, Mulai Edit"). Sengaja TIDAK otomatis nempel ke event
     * apa pun (dulu sempat nempel di Ticket::advanceStage(), yang baru
     * jalan SETELAH proses edit foto kelar -- ternyata bikin tiket ini
     * nyangkut lama padahal loketnya sendiri sudah kosong dari awal).
     */
    public function callPreparingTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();
        $counter = $user->operatingCounter;

        if (!$counter) {
            return back()->with('error', 'Loket Anda belum siap. Muat ulang halaman ini.');
        }

        if ($counter->is_paused) {
            return back()->with('error', 'Loket Anda sedang istirahat. Aktifkan lagi dulu sebelum memanggil antrian.');
        }

        if ($counter->currentTicket()) {
            return back()->with('error', 'Selesaikan tiket yang sedang dilayani terlebih dahulu.');
        }

        $called = false;
        $notReady = false;
        DB::transaction(function () use ($ticket, $counter, $user, &$called, &$notReady) {
            $fresh = Ticket::whereKey($ticket->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status !== 'preparing' || $fresh->stage !== 'photo_session') {
                return;
            }
            // CS di depan yang tahu pelanggannya sudah siap atau belum -- tiket
            // Siap-siap baru boleh dibawa masuk studio kalau CS sudah klik "Ready".
            if (!$fresh->ready_at) {
                $notReady = true;

                return;
            }
            // TIDAK ADA lock per studio di sini SAMA SEKALI -- baik tiket
            // "Siap-siap" generik (counter_id kosong, CS yang mulai) MAUPUN
            // yang tadinya dipanggil salah satu studio (counter_id terisi),
            // studio MANA PUN yang lebih dulu klik "Panggil Sekarang" yang
            // dapat. Sengaja begini: fotografer di Studio 1 sempat manggil
            // "Siap-siap" tapi ternyata masih sibuk motret yang lain --
            // Studio 2 yang lebih dulu kosong tetap bisa langsung comot
            // pelanggan itu buat difoto, gak perlu nunggu Studio 1 atau
            // pakai "Panggil Ulang" dulu. `lockForUpdate()` + cek status di
            // atas tetap menjaga cuma SATU studio yang berhasil kalau
            // beberapa studio klik nyaris bersamaan -- yang kalah dapat
            // pesan "sudah tidak lagi berstatus Siap-siap" di bawah.
            //
            // BUG YANG DIBENERIN: sebelumnya di sini cuma
            // `$fresh->update(['status' => 'calling', ...])` manual, gak
            // lewat model Ticket::call() -- akibatnya beberapa hal ikut
            // KELEWAT tanpa disadari: event TicketCalled gak pernah
            // ke-broadcast (makanya bel/suara/notifikasi push gak bunyi
            // sama sekali di titik ini, beda dari "Panggil Ulang" yang
            // memang lewat call() normal), photo_called_at/payment_called_at
            // gak keisi (widget "Tunggu Foto/Bayar" jadi salah hitung), dan
            // photographed_by/paid_by juga gak tercatat. Panggil call()
            // yang sama biar semuanya konsisten kayak alur panggil biasa.
            // call() juga yang ngurus mindahin counter_id ke studio yang
            // BENERAN manggil sekarang -- jadi walau tadinya "milik" Studio
            // 1, begitu Studio 2 yang berhasil, tiket ini resmi pindah ke
            // Studio 2 (Batalkan/Panggil Ulang berikutnya jadi hak Studio 2).
            $fresh->call($counter, $user);
            $called = true;
        });

        if ($notReady) {
            return back()->with('error', 'Tiket ' . $ticket->ticket_number . ' belum di-Ready oleh CS. Minta CS klik "Ready" dulu kalau pelanggannya sudah siap.');
        }

        if (!$called) {
            return back()->with('error', 'Tiket ini sudah tidak lagi berstatus Siap-siap untuk loket Anda.');
        }

        return back()->with('success', "Tiket {$ticket->ticket_number} dipanggil ke {$counter->name}.");
    }

    /**
     * Cari & panggil tiket berikutnya jadi "Siap-siap" untuk loket ini.
     * Dipakai dari tombol manual (callNextToPrepare/callSpecificToPrepare)
     * -- SEMUA pemanggilan "Siap-siap" 100% manual lewat tombol, gak ada
     * yang otomatis (lihat catatan di startServing() kenapa auto-call
     * sempat ada lalu dicabut). Return null diam-diam kalau gak ada antrian
     * yang bisa dipanggil. TIDAK ADA batasan jumlah "Siap-siap" bersamaan
     * per loket -- itu keputusan operator, bukan sistem.
     */
    private function autoCallNextToPrepare(Counter $counter, User $user): ?Ticket
    {
        if ($counter->is_paused) {
            return null;
        }

        // Sama seperti scope di dashboard()/callNext()/callSpecific() --
        // Fotografer & CS/Kasir (bukan cuma Admin) juga lintas lokasi,
        // supaya tiket hasil check-in reservasi (lokasi BK) yang lagi
        // nunggu di tahap yang sama ikut ke-auto-panggil "Siap-siap".
        $scopeLocationIds = ($user->isAdmin() || $user->isCounterStaff())
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
        $location = $this->resolveWorkLocation($user);

        if (!$location) {
            abort(403, 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
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
     * Filter tanggal Data Pelanggan: 'all' (semua tanggal) atau Y-m-d valid.
     * SEBELUMNYA nilai apa pun dipakai mentah -- tanggal dikosongkan lalu
     * "Terapkan Filter" (date=) atau nilai ngaco bikin whereDate('') = 0
     * hasil, padahal kotak tanggalnya kelihatan kosong (operator ngira
     * datanya hilang). Sekarang kosong/tidak valid -> balik ke hari ini.
     */
    private function normalizeFilterDate($value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === 'all') {
            return 'all';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $value;
        }
        return now()->toDateString();
    }

    /**
     * @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator, 1: string, 2: string, 3: string, 4: string}
     */
    private function filteredCustomerTickets(Request $request, Location $location, ?array $locationIds = null): array
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $stage = (string) $request->query('stage', '');
        $date = $this->normalizeFilterDate($request->query('date'));

        $locationIds ??= [$location->id];

        // Sengaja HANYA tiket dari lokasi "walk-in" (kode PP) yang muncul di
        // sini. Tiket dari lokasi reservasi (kode BK, service_type=booking)
        // sudah ada tabelnya sendiri di atas ("Reservasi Hari Ini" /
        // $custBookings) -- kalau dobel ditampilkan lagi di sini jadi
        // membingungkan buat CS/Kasir (satu reservasi kelihatan 2x di 2
        // tabel beda). Begitu reservasi di-check-in, tiket hasilnya
        // (ticket_number "BK-xxx") tetap TIDAK muncul di tabel Tiket ini --
        // itu memang sudah tercatat sebagai reservasi.
        $ticketLocationIds = Location::whereIn('id', $locationIds)
            ->where('service_type', '!=', 'booking')
            ->pluck('id')
            ->toArray();

        $query = Ticket::whereIn('location_id', $ticketLocationIds)
            // registeredBy/photographedBy/editedBy/paidBy: buat kolom
            // "Dilayani Oleh" per tahap (lihat Ticket::stageStaff()).
            ->with(['counter', 'servedBy', 'registeredBy', 'photographedBy', 'editedBy', 'paidBy'])
            // PENTING: 'id' sebagai tie-breaker kedua. created_at presisinya
            // cuma per detik -- kalau ada 2 tiket yang lahir di detik yang
            // sama (lumrah pas antrian lagi rame), urutan di antara mereka
            // BUKAN dijamin stabil tiap query, jadi baris tabel bisa
            // "ketuker" antara 2 tiket itu tiap kali auto-refresh 2 detik
            // jalan. Efeknya kelihatan kayak kolom Waktu satu baris tiba-tiba
            // balik ke 0 lalu lanjut lagi -- padahal itu 2 TIKET BEDA yang
            // gantian nongol di baris yang sama, bukan timer-nya yang reset.
            ->orderByDesc('created_at')
            ->orderByDesc('id');

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
            // Kode Order permanen formatnya "PJG-000123" (lihat
            // Ticket::getOrderCodeAttribute()) — kalau yang diketik cocok pola
            // itu atau murni angka, ikut cocokkan ke kolom id juga, supaya
            // pelanggan/tiket lama tetap ketemu walau ticket_number-nya sudah
            // dipakai ulang di hari lain.
            $orderIdSearch = null;
            if (preg_match('/^PJG-0*(\d+)$/i', $search, $m)) {
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
        $date = $this->normalizeFilterDate($request->query('bdate'));

        $locationIds ??= [$location->id];

        $query = Booking::whereIn('location_id', $locationIds)
            ->with(['serviceCategory', 'ticket'])
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time')
            // Tie-breaker sama alasannya kayak di filteredCustomerTickets() --
            // banyak reservasi bisa punya jam slot yang sama persis.
            ->orderByDesc('id');

        if ($date !== 'all') {
            $query->whereDate('booking_date', $date);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('order_code', 'like', "%{$search}%")
                    ->orWhere('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'like', "%{$search}%"));
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

        $location = $this->resolveWorkLocation($user);

        if (!$location) {
            abort(403, 'Belum ada studio/loket aktif yang cocok buat role Anda. Hubungi Admin.');
        }

        return $location;
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

        // Edit data tiket (nama/telepon/email/Order) berlaku untuk semua tahap &
        // status -- lihat catatan di updateTicket().

        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)->where('location_id', $ticket->location_id)->orderBy('name')->get();

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

        // Edit data tiket dari Data Pelanggan berlaku untuk SEMUA tahap
        // (registrasi, sesi foto, edit, pembayaran) dan semua status, termasuk
        // yang sudah selesai/dibatalkan -- buat membetulkan salah ketik nama,
        // telepon, email, atau Order. Yang tetap dijaga: tiketnya harus milik
        // lokasi yang dikelola user ini (dicek di atas) dan gembok Order (di bawah).

        $this->normalizeBookingInput($request);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_phone.regex' => 'Format nomor telepon tidak valid. Hanya angka (boleh diawali +), 8-15 digit.',
        ]);

        // Catatan "Order" punya gembok sendiri (notes_locked_at) yang diisi
        // pas confirmOrder()/startServing() -- lihat updateNotes() di atas.
        // Modal edit ini SENGAJA gak diikutin gembok itu di sisi klien (biar
        // gak dobel logic UI), makanya di sini WAJIB dibuang kalau lagi
        // terkunci -- kalau tidak, siapa pun yang megang tahap tiket ini bisa
        // diam-diam nimpa catatan yang sudah "fix"/dikonfirmasi lewat modal
        // ini, padahal fiturnya sendiri udah sengaja dikunci dan cuma boleh
        // dibuka lewat "Buka Kunci buat Edit" (unlockNotes()).
        if ($ticket->notes_locked_at) {
            unset($validated['notes']);
        }

        $ticket->update($validated);

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' berhasil diperbarui.');
    }

    public function destroyTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        // Boleh dibatalkan di semua tahap & status selama sesi BELUM selesai
        // (completed/cancelled sudah final).
        if (!$ticket->canBeCancelled()) {
            return back()->with('error', 'Tiket ' . $ticket->ticket_number . ' sudah selesai/dibatalkan, tidak bisa dibatalkan lagi.');
        }

        // cancel() mengisi completed_at + broadcast ke layar TV/HP pelanggan.
        $ticket->cancel();

        return back()->with('success', 'Tiket ' . $ticket->ticket_number . ' berhasil dibatalkan.');
    }

    /**
     * HAPUS permanen tiket (beda dengan Batalkan yang cuma mengubah status dan
     * riwayatnya tetap ada). Khusus Admin, dan hanya selama sesi belum selesai.
     * Data turunan (feedback, langganan push, order item) ikut terhapus;
     * reservasi yang terhubung cuma dilepas dari tiket ini.
     */
    public function removeTicket(Ticket $ticket): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403, 'Hanya Admin yang boleh menghapus tiket.');
        }

        if (!in_array($ticket->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Tiket ini bukan milik lokasi Anda.');
        }

        $number = $ticket->ticket_number;
        $location = $ticket->location;

        $deleted = DB::transaction(function () use ($ticket) {
            $fresh = Ticket::whereKey($ticket->id)->lockForUpdate()->first();

            if (!$fresh || !$fresh->canBeCancelled()) {
                return false;
            }

            $fresh->delete();

            return true;
        });

        if (!$deleted) {
            return back()->with('error', 'Tiket ' . $number . ' sudah selesai/dibatalkan, tidak bisa dihapus dari sini.');
        }

        // Layar TV & dashboard operator ikut menyegarkan daftar antrian.
        broadcast(new QueueUpdated($location))->toOthers();

        return back()->with('success', 'Tiket ' . $number . ' berhasil dihapus.');
    }
}