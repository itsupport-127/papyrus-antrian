<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\GraduationDay;
use App\Models\Location;
use App\Models\User;
use App\Services\BookingListExcel;
use App\Services\BookingListImporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu "List Booking": daftar jadwal booking ala Excel (arsip + pantau harian) di tabel bookings yang
 * SAMA dengan reservasi. Check-in memakai alur reservasi yang sudah ada (operator.bookings.call).
 * Khusus CS & Kasir dan Admin.
 */
class BookingListController extends Controller
{
    private const TABS = ['today' => 'Hari Ini', 'all' => 'Semua Jadwal', 'calendar' => 'Kalender', 'checkedin' => 'Sudah Check-in', 'cancelled' => 'Batal'];

    private function staff(): User
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->hasPermission('list_booking')) {
            abort(403, 'Kamu belum punya hak akses List Booking.');
        }

        return $user;
    }

    private function locationsFor(User $user)
    {
        // List Booking khusus reservasi: hanya lokasi yang menyediakan reservasi (bukan studio walk-in).
        $all = Location::whereIn('id', $user->manageableLocationIds())->orderBy('name')->get();
        // Utamakan lokasi KHUSUS reservasi (service_type = booking, mis. BK); lokasi 'both' (mis. PP) hanya cadangan.
        $booking = $all->filter(fn (Location $l) => $l->service_type === 'booking')->values();
        if ($booking->isEmpty()) {
            $booking = $all->filter(fn (Location $l) => $l->offersBooking())->values();
        }

        return $booking->isNotEmpty() ? $booking : $all;
    }

    private function pickLocation(Request $request, User $user, $locations): ?Location
    {
        $id = (int) $request->input('loc', 0);
        if ($id && $locations->contains('id', $id)) {
            return $locations->firstWhere('id', $id);
        }
        // Tanpa ?loc: pakai lokasi yang PUNYA DATA booking (paling banyak), supaya CS & admin
        // sama-sama langsung melihat isi tabel walau lokasi tetap CS berbeda dengan lokasi import.
        $counts = Booking::whereIn('location_id', $locations->pluck('id'))
            ->selectRaw('location_id, count(*) as c')->groupBy('location_id')->pluck('c', 'location_id')->all();
        if ($counts) {
            arsort($counts);
            $best = $locations->firstWhere('id', (int) array_key_first($counts));
            if ($best) {
                return $best;
            }
        }

        return $locations->first(fn (Location $l) => $l->offersBooking()) ?? $locations->first();
    }

    /** Tabel hari wisuda baru ada setelah `php artisan migrate`; sebelum itu halaman tetap harus terbuka. */
    private function graduationsReady(): bool
    {
        try {
            return Schema::hasTable('graduation_days');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ownBooking(User $user, Booking $booking): void
    {
        if (!in_array($booking->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Booking ini bukan milik lokasi Anda.');
        }
    }

    /**
     * Bulan yang ditampilkan di "Semua Jadwal": pilihan user; kalau belum memilih, bulan ini (kalau ada
     * datanya), kalau tidak ada -> semua bulan. 'all' = semua bulan.
     */
    private function resolveMonth(Request $request, Location $location, string $tab): string
    {
        $m = $request->input('month');
        if ($m !== null && $m !== '') {
            return (string) $m;
        }
        if ($tab !== 'all') {
            return 'all';
        }
        $cur = now();
        $has = Booking::where('location_id', $location->id)
            ->whereYear('booking_date', $cur->year)->whereMonth('booking_date', $cur->month)->exists();

        return $has ? $cur->format('Y-m') : 'all';
    }

    /** Query sesuai tab + filter (dipakai halaman & export). */
    private function query(Request $request, Location $location, string $tab, string $month)
    {
        $q = Booking::where('location_id', $location->id)->with(['ticket', 'serviceCategory']);

        if ($tab === 'today') {
            $q->whereDate('booking_date', today())->orderBy('booking_time');
        } elseif ($tab === 'checkedin') {
            $q->whereNotNull('ticket_id')->orderByDesc('booking_date')->orderByDesc('booking_time');
        } elseif ($tab === 'cancelled') {
            $q->where('status', 'cancelled')->orderByDesc('booking_date')->orderByDesc('booking_time');
        } else {
            $q->orderBy('booking_date')->orderBy('booking_time');
        }

        $search = trim((string) $request->input('q', ''));

        // Saat mencari, pencarian jalan di SEMUA bulan (bukan hanya bulan yang sedang dipilih).
        if ($tab !== 'today' && $search === '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $q->whereYear('booking_date', (int) substr($month, 0, 4))->whereMonth('booking_date', (int) substr($month, 5, 2));
        }

        // Pencarian fleksibel: tiap kata harus ketemu (di nama, kode JG, paket, keterangan, UNIV, no. telp),
        // dan cukup sebagian saja -- "agn" sudah menemukan "AGNESIA"; "035474" menemukan "JG035474".
        if ($search !== '') {
            $tokens = array_slice(preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 6);
            foreach ($tokens as $token) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token) . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('customer_name', 'like', $like)
                        ->orWhere('order_code', 'like', $like)
                        ->orWhere('booking_code', 'like', $like)
                        ->orWhere('package_name', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhere('univ', 'like', $like)
                        ->orWhere('customer_phone', 'like', $like);
                });
            }
        }

        $status = (string) $request->input('status', '');
        if ($tab === 'all' && in_array($status, ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'], true)) {
            $q->where('status', $status);
        }

        if ($request->boolean('review')) {
            $q->where('needs_review', true);
        }

        return $q;
    }

    public function index(Request $request): View
    {
        $user = $this->staff();
        $locations = $this->locationsFor($user);
        $location = $this->pickLocation($request, $user, $locations);
        abort_if(!$location, 404, 'Belum ada lokasi.');

        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'today';
        $month = $this->resolveMonth($request, $location, $tab);

        $bookings = null;
        $graduations = collect();
        if ($tab !== 'calendar') {
            $bookings = $this->query($request, $location, $tab, $month)->paginate(100)->withQueryString();
            // Array biasa (bukan ->map()->unique() di Eloquent Collection: kalau kosong, unique() error "getKey() on string").
            $dates = [today()->format('Y-m-d')];
            foreach ($bookings as $b) {
                $dates[] = $b->booking_date->format('Y-m-d');
            }
            $dates = array_values(array_unique($dates));
            if ($this->graduationsReady()) {
                $graduations = GraduationDay::whereIn('date', $dates)->get()->groupBy(fn (GraduationDay $g) => $g->date->format('Y-m-d'));
            }
        }

        $base = Booking::where('location_id', $location->id);
        $counts = [
            'today' => (clone $base)->whereDate('booking_date', today())->where('status', '!=', 'cancelled')->count(),
            'today_waiting' => (clone $base)->whereDate('booking_date', today())->whereIn('status', ['pending', 'confirmed'])->count(),
            'review' => (clone $base)->where('needs_review', true)->count(),
        ];

        // Daftar bulan (+ jumlah booking) untuk pilihan bulan di "Semua Jadwal".
        $months = [];
        foreach ((clone $base)->toBase()->pluck('booking_date')->countBy(fn ($d) => substr((string) $d, 0, 7))->sortKeys() as $ym => $n) {
            $months[$ym] = ['label' => Carbon::createFromFormat('Y-m-d', $ym . '-01')->translatedFormat('F Y'), 'count' => $n];
        }

        $calendar = $tab === 'calendar' ? $this->calendarData($request, $location) : null;

        return view('operator.list-booking.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'bookings' => $bookings,
            'graduations' => $graduations,
            'calendar' => $calendar,
            'campusSuggestions' => GraduationDay::CAMPUS_SUGGESTIONS,
            'packages' => BookingPackage::ordered()->pluck('name'),
            'locations' => $locations,
            'location' => $location,
            'counts' => $counts,
            'months' => $months,
            'month' => $month,
            'search' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'onlyReview' => $request->boolean('review'),
        ]);
    }

    /** Data kalender sebulan: jumlah booking per hari + penanda wisuda + detail hari yang dipilih. */
    private function calendarData(Request $request, Location $location): array
    {
        $ym = (string) $request->input('month', '');
        $first = preg_match('/^\d{4}-\d{2}$/', $ym) ? Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfDay() : now()->startOfMonth();
        $last = $first->copy()->endOfMonth();

        $monthBookings = Booking::where('location_id', $location->id)
            ->whereBetween('booking_date', [$first->toDateString(), $last->toDateString()])
            ->orderBy('booking_time')->get();
        $byDay = $monthBookings->groupBy(fn (Booking $b) => $b->booking_date->format('Y-m-d'));

        $ready = $this->graduationsReady();
        $gradByDay = !$ready ? collect() : GraduationDay::whereBetween('date', [$first->toDateString(), $last->toDateString()])
            ->orderBy('campus')->get()->groupBy(fn (GraduationDay $g) => $g->date->format('Y-m-d'));

        // Grid minggu Senin-Minggu.
        $cursor = $first->copy()->startOfWeek(Carbon::MONDAY);
        $end = $last->copy()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];
        while ($cursor <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');
                $items = $byDay->get($key, collect());
                $week[] = [
                    'date' => $cursor->copy(),
                    'key' => $key,
                    'in_month' => $cursor->month === $first->month,
                    'is_today' => $cursor->isToday(),
                    'total' => $items->where('status', '!=', 'cancelled')->count(),
                    'cancelled' => $items->where('status', 'cancelled')->count(),
                    'grads' => $gradByDay->get($key, collect()),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $selected = (string) $request->input('day', '');
        $selected = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected) ? $selected : null;

        return [
            'first' => $first,
            'prev' => $first->copy()->subMonth()->format('Y-m'),
            'next' => $first->copy()->addMonth()->format('Y-m'),
            'weeks' => $weeks,
            'selected' => $selected,
            'selected_bookings' => $selected ? $byDay->get($selected, collect()) : collect(),
            'selected_grads' => $selected ? ($gradByDay->get($selected) ?? ($ready ? GraduationDay::whereDate('date', $selected)->get() : collect())) : collect(),
            'upcoming' => $ready ? GraduationDay::where('date', '>=', today()->toDateString())->orderBy('date')->orderBy('campus')->limit(15)->get() : collect(),
            'graduation_ready' => $ready,
        ];
    }

    public function storeGraduation(Request $request): RedirectResponse
    {
        $user = $this->staff();
        if (!$this->graduationsReady()) {
            return back()->with('error', 'Tabel hari wisuda belum dibuat. Jalankan: php artisan migrate --force');
        }
        $data = $request->validate([
            'date' => ['required', 'date'],
            'campus' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'date.required' => 'Pilih tanggal wisuda.',
            'campus.required' => 'Isi nama kampus.',
        ]);

        $campus = trim(preg_replace('/\s+/', ' ', $data['campus']));
        $date = Carbon::parse($data['date']);

        // Kampus yang sama di tanggal yang sama tidak perlu ditandai dua kali.
        $exists = GraduationDay::whereDate('date', $date->toDateString())->whereRaw('LOWER(campus) = ?', [mb_strtolower($campus)])->exists();
        if (!$exists) {
            GraduationDay::create(['date' => $date->toDateString(), 'campus' => $campus, 'note' => $data['note'] ?? null, 'created_by' => $user->id]);
        }

        return redirect()->route('operator.list-booking', array_filter([
            'tab' => 'calendar', 'month' => $date->format('Y-m'), 'day' => $date->toDateString(), 'loc' => $request->input('loc'),
        ]))->with('success', $exists ? 'Wisuda ' . $campus . ' di tanggal itu sudah ditandai.' : 'Wisuda ' . $campus . ' ditandai di ' . $date->translatedFormat('d F Y') . '.');
    }

    public function destroyGraduation(GraduationDay $graduationDay): RedirectResponse
    {
        $this->staff();
        $graduationDay->delete();

        return back()->with('success', 'Penanda wisuda ' . $graduationDay->campus . ' dihapus.');
    }

    public function create(Request $request): View
    {
        $user = $this->staff();
        $locations = $this->locationsFor($user);
        $location = $this->pickLocation($request, $user, $locations);

        return view('operator.list-booking.form', [
            'booking' => new Booking(['booking_date' => today(), 'status' => 'confirmed']),
            'location' => $location,
            'packages' => BookingPackage::ordered()->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->staff();
        $locations = $this->locationsFor($user);
        $location = $this->pickLocation($request, $user, $locations);
        abort_if(!$location, 404);

        $data = $this->validated($request, true);
        $data['status'] = $data['status'] ?? 'confirmed';
        $date = Carbon::parse($data['booking_date']);

        Booking::create($data + [
            'location_id' => $location->id,
            'booking_code' => Booking::generateBookingCode($location, $date),
            'source' => 'manual',
        ]);

        return redirect()->route('operator.list-booking', ['tab' => 'all', 'month' => $date->format('Y-m'), 'loc' => $location->id])
            ->with('success', 'Booking ' . $data['customer_name'] . ' ditambahkan.');
    }

    public function edit(Booking $booking): View
    {
        $this->ownBooking($this->staff(), $booking);

        return view('operator.list-booking.form', [
            'booking' => $booking,
            'location' => $booking->location,
            'packages' => BookingPackage::ordered()->pluck('name'),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->ownBooking($this->staff(), $booking);

        $data = $this->validated($request);
        // Booking yang sudah jadi tiket: statusnya diatur oleh alur antrian, jangan diubah dari sini.
        if ($booking->ticket_id || empty($data['status'])) {
            unset($data['status']);
        }
        // Dibatalkan lewat sini mengikuti aturan reservasi: DP yang sudah masuk ditandai hangus.
        if (($data['status'] ?? null) === 'cancelled' && $booking->status !== 'cancelled') {
            $data['dp_forfeited'] = $booking->dp_amount > 0;
        }
        if (!$request->boolean('needs_review')) {
            $data['review_note'] = null;
        }

        $booking->update($data);

        if ($booking->ticket) {
            $booking->ticket->update(['customer_name' => $booking->customer_name]);
        }

        return redirect()->route('operator.list-booking', ['tab' => 'all', 'month' => $booking->booking_date->format('Y-m'), 'loc' => $booking->location_id])
            ->with('success', 'Booking ' . $booking->customer_name . ' diperbarui.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->ownBooking($this->staff(), $booking);

        $name = $booking->customer_name;
        $ticket = $booking->ticket;
        $booking->delete();

        $msg = "Booking {$name} dihapus.";
        if ($ticket) {
            $msg .= " Tiket {$ticket->ticket_number} tetap ada.";
        }

        return back()->with('success', $msg);
    }

    public function import(Request $request, BookingListImporter $importer): RedirectResponse
    {
        $user = $this->staff();
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xlsm,xls', 'max:20480'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) dulu.',
            'file.mimes' => 'File harus berformat Excel (.xlsx / .xls).',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $locations = $this->locationsFor($user);
        $location = $this->pickLocation($request, $user, $locations);
        abort_if(!$location, 404);

        try {
            @set_time_limit(300);
            $result = $importer->import($location, $request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Import gagal dibaca: ' . $e->getMessage() . ' (tidak ada data yang tersimpan).');
        }

        $msg = "Import selesai: {$result['created']} booking baru, {$result['duplicates']} dilewati karena sudah ada, {$result['review']} perlu dicek.";

        return redirect()->route('operator.list-booking', ['tab' => 'all', 'month' => 'all', 'review' => $result['review'] > 0 ? 1 : 0, 'loc' => $location->id])
            ->with('success', $msg)
            ->with('import_skipped', array_slice($result['skipped'], 0, 60));
    }

    public function export(Request $request, BookingListExcel $excel): StreamedResponse
    {
        $user = $this->staff();
        $locations = $this->locationsFor($user);
        $location = $this->pickLocation($request, $user, $locations);
        abort_if(!$location, 404);

        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'all';
        $month = (string) $request->input('month', 'all');
        $bookings = $this->query($request, $location, $tab === 'calendar' ? 'all' : $tab, $month)->get();

        $graduations = !$this->graduationsReady() ? collect() : GraduationDay::all()->groupBy(fn (GraduationDay $g) => $g->date->format('Y-m-d'));

        return $excel->download($bookings, 'list-booking-' . str($location->name)->slug() . '-' . now()->format('Ymd-His') . '.xlsx', $graduations);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $isCreate = false): array
    {
        $request->merge([
            'order_code' => $request->filled('order_code') ? strtoupper(preg_replace('/\s+/', '', (string) $request->input('order_code'))) : null,
            'customer_phone' => $request->filled('customer_phone') ? preg_replace('/[\s\-\.\(\)]+/', '', (string) $request->input('customer_phone')) : null,
            'package_name' => $request->filled('package_name') ? BookingPackage::normalizeName((string) $request->input('package_name')) : null,
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'order_code' => ['required', 'string', 'max:50'],
            'booking_date' => ['required', 'date'],
            'booking_time' => ['required', 'date_format:H:i'],
            'package_name' => ['required', 'string', 'max:100'],
            'people_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            // Wajib saat menambah booking baru; saat edit boleh kosong (booking hasil import Excel tidak punya no. telp).
            'customer_phone' => [$isCreate ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'univ' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'arrived_time' => ['nullable', 'date_format:H:i'],
            'started_time' => ['nullable', 'date_format:H:i'],
            'finished_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'review_note' => ['nullable', 'string', 'max:500'],
        ], [
            'order_code.required' => 'Isi kode order.',
            'customer_name.required' => 'Isi nama pelanggan.',
            'package_name.required' => 'Isi paket.',
            'booking_date.required' => 'Isi tanggal booking.',
            'booking_time.required' => 'Isi jam booking.',
            'booking_time.date_format' => 'Jam harus berformat JJ:MM, mis. 09:30.',
            'customer_phone.required' => 'Isi no. telepon.',
            'customer_phone.regex' => 'No. telepon: hanya angka (boleh diawali +), 8-15 digit.',
        ]);

        $data['needs_review'] = $request->boolean('needs_review');

        return $data;
    }
}
