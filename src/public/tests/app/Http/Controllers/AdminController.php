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
use Illuminate\Http\Request;
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
        ];

        $locations = Location::withCount([
            'tickets as today_tickets_count' => fn ($q) => $q->whereDate('created_at', today()),
            'tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting'),
        ])->get();

        return view('admin.dashboard', compact('stats', 'locations'));
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
            'average_service_time' => ['required', 'integer', 'min:1', 'max:120'],
            'service_type' => ['required', 'in:both,ticket,booking'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
        ]);

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
            'average_service_time' => ['required', 'integer', 'min:1', 'max:120'],
            'service_type' => ['required', 'in:both,ticket,booking'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
            'is_active' => ['boolean'],
        ]);

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
        ]);

        $location->counters()->create($validated);

        return back()->with('success', 'Loket berhasil ditambahkan.');
    }

    public function toggleCounter(Request $request, Counter $counter): RedirectResponse
    {
        $counter->update(['is_active' => $request->boolean('is_active')]);

        $status = $counter->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Loket berhasil {$status}.");
    }

    public function deleteCounter(Counter $counter): RedirectResponse
    {
        $counter->delete();
        return back()->with('success', 'Loket berhasil dihapus.');
    }

    // User Management
    public function users(): View
    {
        $users = User::with('location')->get();
        $locations = Location::where('is_active', true)->get();
        return view('admin.users.index', compact('users', 'locations'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,operator,fotografer,viewer'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = array_keys($validated['permissions'] ?? []);
        $validated['permissions'] = $permissions;

        $user->update($validated);

        return back()->with('success', 'User berhasil diperbarui.');
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

        $query = Ticket::query()
            ->selectRaw('customer_phone')
            ->selectRaw('MAX(customer_name) as customer_name')
            ->selectRaw('MAX(customer_email) as customer_email')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->whereNotNull('customer_phone')
            ->groupBy('customer_phone');

        if ($search !== '') {
            $query->having('customer_phone', 'like', "%{$search}%")
                ->orHaving('customer_name', 'like', "%{$search}%");
        }

        $customers = $query->orderByDesc('last_order_at')->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers', 'search'));
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