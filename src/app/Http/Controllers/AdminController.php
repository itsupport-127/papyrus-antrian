<?php

namespace App\Http\Controllers;

use App\Models\BookingSlot;
use App\Models\Counter;
use App\Services\BookingSlotService;
use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(protected BookingSlotService $slotService)
    {
    }

    public function dashboard(): View
    {
        $stats = [
            'total_locations' => Location::count(),
            'active_locations' => Location::where('is_active', true)->count(),
            'total_operators' => User::whereIn('role', ['operator', 'fotografer'])->count(),
            'today_tickets' => Ticket::whereDate('created_at', today())->count(),
            'today_completed' => Ticket::whereDate('created_at', today())->where('status', 'completed')->count(),
            'today_waiting' => Ticket::where('status', 'waiting')->count(),
            // Sebelumnya kartu "Rata-rata Waktu" di dashboard ini nampilin
            // teks statis "~5" yang di-hardcode di Blade -- gak pernah
            // kehitung dari data beneran sama sekali. Sekarang pakai
            // rata-rata Total durasi (created_at s/d completed_at, lewat
            // Ticket::stageDurations()) dari tiket yang SELESAI HARI INI --
            // biar konsisten sama kartu-kartu lain di baris yang sama yang
            // semuanya scope "hari ini".
            'avg_service_minutes' => $this->averageServiceMinutesToday(),
        ];

        $locations = Location::withCount([
            'tickets as today_tickets_count' => fn ($q) => $q->whereDate('created_at', today()),
            'tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting'),
        ])->get();

        return view('admin.dashboard', compact('stats', 'locations'));
    }

    /**
     * Ringkasan buat lonceng notifikasi di navbar (lihat layout.blade.php) --
     * di-poll berkala lewat JS, BUKAN dipanggil pas render halaman biasa.
     * Sengaja dibikin ringan (cuma count + satu timestamp "aktivitas
     * terakhir"), biar aman di-poll tiap ~20 detik tanpa bebanin server.
     *
     * "latest_activity_at" dipakai klien buat DETEKSI ada kejadian baru
     * (tiket baru daftar ATAU tiket yang baru masuk status "calling")
     * sejak poll sebelumnya -- kalau nilainya lebih baru dari yang
     * tersimpan di klien, lonceng "berdering" (animasi) sebentar. Kalau
     * cuma nilainya SAMA, dianggap gak ada yang baru, badge count-nya aja
     * yang tetap nampilin total menunggu+dipanggil saat ini.
     */
    public function notificationsSummary(): \Illuminate\Http\JsonResponse
    {
        $waitingCount = Ticket::where('status', 'waiting')->count();
        $callingCount = Ticket::where('status', 'calling')->count();

        $latestActivityAt = collect([
            Ticket::max('created_at'),
            Ticket::whereNotNull('called_at')->max('called_at'),
        ])->filter()->max();

        return response()->json([
            'waiting_count' => $waitingCount,
            'calling_count' => $callingCount,
            'total' => $waitingCount + $callingCount,
            'latest_activity_at' => $latestActivityAt,
        ]);
    }

    /**
     * Rata-rata durasi Total (created_at s/d completed_at) tiket yang
     * SELESAI hari ini, dalam menit. null kalau belum ada tiket selesai
     * hari ini (Blade-nya tampilin "-", bukan angka ngasal).
     */
    private function averageServiceMinutesToday(): ?int
    {
        $completedToday = Ticket::whereDate('created_at', today())
            ->where('status', 'completed')
            ->get();

        if ($completedToday->isEmpty()) {
            return null;
        }

        $totalSeconds = $completedToday->sum(fn ($ticket) => $ticket->stageDurations()['total']['seconds'] ?? 0);

        return (int) round($totalSeconds / $completedToday->count() / 60);
    }

    // Location Management
    public function locations(): View
    {
        $locations = Location::withCount(['counters', 'users'])
            ->withCount(['bookingSlots as active_booking_slots_count' => fn ($q) => $q->where('is_active', true)])
            ->get();
        return view('admin.locations.index', compact('locations'));
    }

    public function createLocation(): View
    {
        return view('admin.locations.create');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:locations,code'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'gps_radius_meters' => ['nullable', 'integer', 'min:20', 'max:5000'],
            'order_line_suggestions' => ['nullable', 'string', 'max:4000'],
            'average_service_time' => ['required', 'integer', 'min:1', 'max:120'],
            'service_type' => ['required', 'in:both,ticket,booking'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
        ]);

        $validated['gps_radius_meters'] = $validated['gps_radius_meters'] ?? 150;

        Location::create($validated);

        return redirect()->route('admin.locations')
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function editLocation(Location $location): View
    {
        return view('admin.locations.edit', compact('location'));
    }

    public function updateLocation(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:locations,code,' . $location->id],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'gps_radius_meters' => ['nullable', 'integer', 'min:20', 'max:5000'],
            'order_line_suggestions' => ['nullable', 'string', 'max:4000'],
            'average_service_time' => ['required', 'integer', 'min:1', 'max:120'],
            'service_type' => ['required', 'in:both,ticket,booking'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
            'is_active' => ['boolean'],
        ]);

        $validated['gps_radius_meters'] = $validated['gps_radius_meters'] ?? $location->gps_radius_meters ?? 150;

        $location->update($validated);

        return redirect()->route('admin.locations')
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function deleteLocation(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('admin.locations')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    // Counter Management
    public function counters(Location $location): View
    {
        $counters = $location->counters()->with('currentOperator')->get();
        return view('admin.counters.index', compact('location', 'counters'));
    }

    public function storeCounter(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*' => ['in:registration,photo_session,payment'],
            'serves' => ['nullable', 'in:both,walkin,booking'],
        ]);

        $validated['serves'] = $validated['serves'] ?? 'both';

        $location->counters()->create($validated);

        return back()->with('success', 'Loket berhasil ditambahkan.');
    }

    public function toggleCounter(Request $request, Counter $counter): RedirectResponse
    {
        $counter->update(['is_active' => $request->boolean('is_active')]);

        $status = $counter->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Loket berhasil {$status}.");
    }

    /**
     * Edit nama & tahap loket yang sudah ada. Nama loket ini yang langsung
     * muncul ke pelanggan pas dipanggil/siap-siap (mis. "Silakan Bersiap-
     * siap di Studio Atas") — jadi penting bisa diganti supaya sesuai nama
     * studio/fotografer yang sebenarnya, tanpa harus hapus & bikin ulang
     * (yang akan memutus riwayat operator/tiket yang sudah tercatat).
     */
    public function updateCounter(Request $request, Counter $counter): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*' => ['in:registration,photo_session,payment'],
            'serves' => ['nullable', 'in:both,walkin,booking'],
        ]);

        $validated['serves'] = $validated['serves'] ?? 'both';

        $counter->update($validated);

        return back()->with('success', 'Loket "' . $counter->name . '" berhasil diperbarui.');
    }

    public function deleteCounter(Counter $counter): RedirectResponse
    {
        $counter->delete();
        return back()->with('success', 'Loket berhasil dihapus.');
    }

    // User Management
    public function users(): View
    {
        $users = User::with('location', 'preferredCounter.location')->get();
        $locations = Location::where('is_active', true)->get();
        $counters = Counter::with('location')->whereHas('location', fn ($q) => $q->where('is_active', true))->get();
        return view('admin.users.index', compact('users', 'locations', 'counters'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,operator,fotografer,viewer'],
            'preferred_counter_id' => ['nullable', 'exists:counters,id'],
        ]);

        // Gak ada lagi field Lokasi terpisah -- lokasi kerja otomatis
        // ngikut lokasi loket tetapnya (kalau ada). Kalau gak ada loket
        // tetap, dia "gantian" -- location_id kosong, dashboard tetap
        // nampilin data gabungan semua lokasi (lihat OperatorController::dashboard()),
        // dan pas login dia diminta pilih lokasi kerja (bisa ganti kapan saja).
        $validated['location_id'] = $validated['preferred_counter_id']
            ? Counter::find($validated['preferred_counter_id'])->location_id
            : null;

        User::create($validated);

        return back()->with('success', "Pengguna \"{$validated['name']}\" berhasil dibuat.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // Password sengaja nullable -- kosongkan berarti gak diubah.
            // Kalau diisi, minimal 8 karakter (sama kayak validasi bikin
            // pengguna baru).
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,operator,fotografer,viewer'],
            'preferred_counter_id' => ['nullable', 'exists:counters,id'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $validated['permissions'] = array_keys($validated['permissions'] ?? []);

        $validated['location_id'] = $validated['preferred_counter_id']
            ? Counter::find($validated['preferred_counter_id'])->location_id
            : null;

        // Password kosong = gak ikut diupdate (biar gak ketimpa hash string
        // kosong). Kolom `password` sudah di-cast 'hashed' di model User,
        // jadi cukup kasih plain text di sini -- otomatis di-hash Laravel.
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri yang sedang login.');
        }

        // Jaga-jaga supaya sistem gak pernah kehabisan Admin sama sekali
        // (kalau itu terjadi, gak ada yang bisa buka /admin lagi buat
        // benerin/nambah admin baru -- terkunci total).
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus Admin terakhir. Buat/jadikan Admin lain dulu sebelum menghapus akun ini.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna \"{$name}\" berhasil dihapus.");
    }

    // Service Category Management
    public function serviceCategories(Location $location): View
    {
        $categories = $location->serviceCategories()->get();
        return view('admin.service-categories.index', compact('location', 'categories'));
    }

    public function storeServiceCategory(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $location->serviceCategories()->create($validated);

        return back()->with('success', 'Kategori layanan berhasil ditambahkan.');
    }

    public function toggleServiceCategory(Request $request, ServiceCategory $category): RedirectResponse
    {
        $category->update(['is_active' => $request->boolean('is_active')]);

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kategori layanan berhasil {$status}.");
    }

    public function deleteServiceCategory(ServiceCategory $category): RedirectResponse
    {
        $category->delete();
        return back()->with('success', 'Kategori layanan berhasil dihapus.');
    }

    // Jadwal Reservasi (Booking Slots)
    // Admin/CS atur sendiri jam berapa saja pelanggan boleh reservasi per
    // hari, dan berapa banyak reservasi boleh masuk di jam yang sama
    // (kapasitas). Bisa ditambah satu-satu (custom) atau lewat generator
    // otomatis (interval tetap dari jam buka-tutup lokasi).
    public function bookingSlots(Location $location): View
    {
        [$slots, $bookedTodayByTime] = $this->slotService->getSlotsWithBookedCounts($location);

        $otherLocations = Location::where('id', '!=', $location->id)->orderBy('name')->get();

        return view('admin.booking-slots.index', compact('location', 'slots', 'bookedTodayByTime', 'otherLocations') + [
            'backRoute' => route('admin.locations'),
            'backLabel' => 'Kembali ke Lokasi',
            'routePrefix' => 'admin',
        ]);
    }

    /**
     * Salin Sistem Reservasi (mode + semua slot jam & kapasitas) dari lokasi
     * ini ke satu atau lebih lokasi lain — supaya tidak perlu setting jadwal
     * reservasi satu-satu tiap ada lokasi baru.
     */
    public function copyBookingSettings(Request $request, Location $source): RedirectResponse
    {
        $validated = $request->validate([
            'target_ids' => ['required', 'array', 'min:1'],
            'target_ids.*' => ['integer', 'exists:locations,id'],
        ]);

        $targets = Location::whereIn('id', $validated['target_ids'])
            ->where('id', '!=', $source->id)
            ->get();

        $sourceSlots = $source->bookingSlots()->get();

        foreach ($targets as $target) {
            $target->update(['booking_mode' => $source->booking_mode]);

            foreach ($sourceSlots as $slot) {
                BookingSlot::updateOrCreate(
                    [
                        'location_id' => $target->id,
                        'day_of_week' => $slot->day_of_week,
                        'start_time' => $slot->start_time,
                    ],
                    [
                        'capacity' => $slot->capacity,
                        'is_active' => $slot->is_active,
                    ]
                );
            }
        }

        $names = $targets->pluck('name')->implode(', ');

        return back()->with('success', "Pengaturan reservasi berhasil disalin ke: {$names}.");
    }

    /**
     * Ganti sistem reservasi lokasi: 'slots' (pelanggan pilih dari jadwal
     * jam yang sudah disiapkan) atau 'free' (pelanggan bebas isi jam
     * berapa saja selama masih dalam jam operasional).
     */
    public function updateBookingMode(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'booking_mode' => ['required', 'in:slots,free'],
        ]);

        $this->slotService->updateMode($location, $validated['booking_mode']);

        return back()->with('success', 'Sistem reservasi berhasil diubah.');
    }

    /**
     * Aksi massal: aktifkan / nonaktifkan / hapus banyak slot sekaligus
     * (dipilih lewat checkbox), daripada satu-satu.
     */
    public function bulkActionBookingSlots(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        // Batasi hanya ke slot milik lokasi ini, jaga-jaga ada id nyasar dari lokasi lain.
        $count = $this->slotService->bulkAction($location, $validated['action'], $validated['ids']);

        $messages = [
            'activate' => "{$count} slot berhasil diaktifkan.",
            'deactivate' => "{$count} slot berhasil dinonaktifkan.",
            'delete' => "{$count} slot berhasil dihapus.",
        ];

        return back()->with('success', $messages[$validated['action']]);
    }

    public function storeBookingSlot(Request $request, Location $location): RedirectResponse
    {
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

    public function generateBookingSlots(Request $request, Location $location): RedirectResponse
    {
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

    public function toggleBookingSlot(BookingSlot $bookingSlot): RedirectResponse
    {
        $this->slotService->toggle($bookingSlot);

        $status = $bookingSlot->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Slot berhasil {$status}.");
    }

    public function updateBookingSlot(Request $request, BookingSlot $bookingSlot): RedirectResponse
    {
        $validated = $request->validate([
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->slotService->update($bookingSlot, $validated['capacity'], $request->boolean('is_active'));

        return back()->with('success', 'Slot berhasil diperbarui.');
    }

    public function deleteBookingSlot(BookingSlot $bookingSlot): RedirectResponse
    {
        $this->slotService->delete($bookingSlot);
        return back()->with('success', 'Slot berhasil dihapus.');
    }

    // Customer Master Data
    // Tidak ada tabel "customers" terpisah — data pelanggan dikumpulkan dari
    // histori tiket (dikelompokkan per no. telepon, karena itu yang paling
    // unik dan konsisten diisi pelanggan setiap kali order).
    public function customers(Request $request): View
    {
        $search = trim((string) $request->get('search'));

        // Filter periode: all (default, perilaku lama) | day | week | month | year | custom.
        // Yang difilter = tanggal ORDER (created_at tiket), jadi "Total Order" &
        // "Order Terakhir" di tabel ikut menghitung order dalam periode itu saja.
        $range = (string) $request->get('range', 'all');
        if (!in_array($range, ['all', 'day', 'week', 'month', 'year', 'custom'], true)) {
            $range = 'all';
        }

        $today = today();
        $date = $this->parseFilterDate($request->get('date')) ?? $today->copy();
        $year = max(2000, min(2100, (int) ($request->get('year') ?: $today->year)));
        $month = max(1, min(12, (int) ($request->get('month') ?: $today->month)));
        $from = $this->parseFilterDate($request->get('from'));
        $to = $this->parseFilterDate($request->get('to'));
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from]; // kebalik? tukar saja, jangan error.
        }

        $periodStart = null;
        $periodEnd = null;
        $periodLabel = null;

        switch ($range) {
            case 'day':
                $periodStart = $date->copy()->startOfDay();
                $periodEnd = $date->copy()->endOfDay();
                $periodLabel = $date->copy()->locale('id')->isoFormat('dddd, D MMMM Y');
                break;
            case 'week':
                $periodStart = $date->copy()->startOfWeek(); // Senin
                $periodEnd = $date->copy()->endOfWeek();     // Minggu
                $periodLabel = $periodStart->copy()->locale('id')->isoFormat('D MMM')
                    . ' - ' . $periodEnd->copy()->locale('id')->isoFormat('D MMM Y');
                break;
            case 'month':
                $periodStart = Carbon::create($year, $month, 1)->startOfDay();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $periodLabel = $periodStart->copy()->locale('id')->isoFormat('MMMM Y');
                break;
            case 'year':
                $periodStart = Carbon::create($year, 1, 1)->startOfDay();
                $periodEnd = $periodStart->copy()->endOfYear();
                $periodLabel = 'Tahun ' . $year;
                break;
            case 'custom':
                $periodStart = $from?->copy()->startOfDay();
                $periodEnd = $to?->copy()->endOfDay();
                if ($periodStart && $periodEnd) {
                    $periodLabel = $periodStart->copy()->locale('id')->isoFormat('D MMM Y')
                        . ' - ' . $periodEnd->copy()->locale('id')->isoFormat('D MMM Y');
                } elseif ($periodStart) {
                    $periodLabel = 'Sejak ' . $periodStart->copy()->locale('id')->isoFormat('D MMM Y');
                } elseif ($periodEnd) {
                    $periodLabel = 'Sampai ' . $periodEnd->copy()->locale('id')->isoFormat('D MMM Y');
                } else {
                    $range = 'all'; // custom tapi kedua tanggal kosong = tanpa filter
                }
                break;
        }

        $query = Ticket::query()
            ->selectRaw('customer_phone')
            ->selectRaw('MAX(customer_name) as customer_name')
            ->selectRaw('MAX(customer_email) as customer_email')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->whereNotNull('customer_phone')
            ->when($periodStart, fn ($q) => $q->where('created_at', '>=', $periodStart))
            ->when($periodEnd, fn ($q) => $q->where('created_at', '<=', $periodEnd))
            ->groupBy('customer_phone');

        if ($search !== '') {
            $query->having('customer_phone', 'like', "%{$search}%")
                ->orHaving('customer_name', 'like', "%{$search}%");
        }

        // Ringkasan (jumlah pelanggan & total order dalam filter yang sama).
        $totalOrders = (int) DB::query()->fromSub(clone $query, 'c')->sum('total_orders');

        $customers = $query->orderByDesc('last_order_at')->paginate(20)->withQueryString();

        // Pilihan tahun: dari tahun order paling awal sampai tahun ini.
        $firstOrder = Ticket::whereNotNull('customer_phone')->min('created_at');
        $minYear = $firstOrder ? Carbon::parse($firstOrder)->year : $today->year;
        $years = range($today->year, min($minYear, $today->year));

        $filter = [
            'range' => $range,
            'date' => $date->format('Y-m-d'),
            'month' => $month,
            'year' => $year,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'label' => $periodLabel,
        ];

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'filter' => $filter,
            'years' => $years,
            'totalOrders' => $totalOrders,
        ]);
    }

    /** Tanggal dari query string (Y-m-d); nilai ngawur dianggap kosong. */
    private function parseFilterDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function customerShow(string $phone): View
    {
        $tickets = Ticket::where('customer_phone', $phone)
            ->with('location')
            ->orderByDesc('created_at')
            ->get();

        abort_if($tickets->isEmpty(), 404, 'Pelanggan tidak ditemukan.');

        $latest = $tickets->first();

        $summary = [
            'name' => $latest->customer_name,
            'phone' => $phone,
            'email' => $tickets->firstWhere('customer_email', '!=', null)?->customer_email,
            'total_orders' => $tickets->count(),
            'completed_orders' => $tickets->where('status', 'completed')->count(),
            'first_order_at' => $tickets->min('created_at'),
            'last_order_at' => $tickets->max('created_at'),
        ];

        return view('admin.customers.show', compact('tickets', 'summary', 'phone'));
    }

    /**
     * Memperbarui data kontak pelanggan (nama/email) di semua tiket dengan
     * no. telepon yang sama sekaligus, supaya histori tetap konsisten.
     */
    public function customerUpdate(Request $request, string $phone): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $updated = Ticket::where('customer_phone', $phone)->update([
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
        ]);

        abort_if($updated === 0, 404, 'Pelanggan tidak ditemukan.');

        return redirect()->route('admin.customers.show', $phone)
            ->with('success', 'Data pelanggan berhasil diperbarui di ' . $updated . ' tiket.');
    }

    /**
     * Menghapus satu baris riwayat pesanan/tiket milik pelanggan tertentu.
     */
    public function customerDeleteTicket(string $phone, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->customer_phone !== $phone, 404);

        $ticket->delete();

        return back()->with('success', 'Riwayat tiket ' . $ticket->ticket_number . ' berhasil dihapus.');
    }
}