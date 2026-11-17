<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\Location;
use App\Models\User;
use App\Services\BookingListExcel;
use App\Services\BookingListImporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    private const TABS = ['today' => 'Hari Ini', 'all' => 'Semua Jadwal', 'checkedin' => 'Sudah Check-in', 'cancelled' => 'Batal'];

    private function staff(): User
    {
        $user = auth()->user();
        if (!$user->isOperator() && !$user->isAdmin()) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat membuka List Booking.');
        }

        return $user;
    }

    private function locationsFor(User $user)
    {
        return Location::whereIn('id', $user->manageableLocationIds())->orderBy('name')->get();
    }

    private function pickLocation(Request $request, User $user, $locations): ?Location
    {
        $id = (int) $request->input('loc', 0);
        if ($id && $locations->contains('id', $id)) {
            return $locations->firstWhere('id', $id);
        }
        if ($user->location_id && $locations->contains('id', $user->location_id)) {
            return $locations->firstWhere('id', $user->location_id);
        }

        return $locations->first(fn (Location $l) => $l->offersBooking()) ?? $locations->first();
    }

    private function ownBooking(User $user, Booking $booking): void
    {
        if (!in_array($booking->location_id, $user->manageableLocationIds(), true)) {
            abort(403, 'Booking ini bukan milik lokasi Anda.');
        }
    }

    /** Query sesuai tab + filter (dipakai halaman & export). */
    private function query(Request $request, Location $location, string $tab)
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

        $month = (string) $request->input('month', $tab === 'all' ? now()->format('Y-m') : 'all');
        if ($tab !== 'today' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $q->whereYear('booking_date', (int) substr($month, 0, 4))->whereMonth('booking_date', (int) substr($month, 5, 2));
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $q->where(function ($w) use ($like) {
                $w->where('customer_name', 'like', $like)
                    ->orWhere('order_code', 'like', $like)
                    ->orWhere('booking_code', 'like', $like)
                    ->orWhere('package_name', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhere('univ', 'like', $like);
            });
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
        $bookings = $this->query($request, $location, $tab)->paginate(100)->withQueryString();

        $base = Booking::where('location_id', $location->id);
        $counts = [
            'today' => (clone $base)->whereDate('booking_date', today())->where('status', '!=', 'cancelled')->count(),
            'today_waiting' => (clone $base)->whereDate('booking_date', today())->whereIn('status', ['pending', 'confirmed'])->count(),
            'review' => (clone $base)->where('needs_review', true)->count(),
        ];

        // Daftar bulan untuk filter "Semua Jadwal" (dari tanggal terawal sampai terakhir yang ada).
        $range = (clone $base)->selectRaw('MIN(booking_date) as min_d, MAX(booking_date) as max_d')->first();
        $months = [];
        if ($range && $range->min_d) {
            $cursor = Carbon::parse($range->min_d)->startOfMonth();
            $end = Carbon::parse($range->max_d)->startOfMonth();
            while ($cursor <= $end && count($months) < 60) {
                $months[$cursor->format('Y-m')] = $cursor->translatedFormat('F Y');
                $cursor->addMonth();
            }
        }

        return view('operator.list-booking.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'bookings' => $bookings,
            'locations' => $locations,
            'location' => $location,
            'counts' => $counts,
            'months' => $months,
            'month' => (string) $request->input('month', $tab === 'all' ? now()->format('Y-m') : 'all'),
            'search' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'onlyReview' => $request->boolean('review'),
        ]);
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

        $data = $this->validated($request);
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
        if ($booking->ticket_id) {
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
        $request->merge(['month' => $request->input('month', 'all')]);
        $bookings = $this->query($request, $location, $tab)->get();

        return $excel->download($bookings, 'list-booking-' . str($location->name)->slug() . '-' . now()->format('Ymd-His') . '.xlsx');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge([
            'order_code' => $request->filled('order_code') ? strtoupper(preg_replace('/\s+/', '', (string) $request->input('order_code'))) : null,
            'customer_phone' => $request->filled('customer_phone') ? preg_replace('/[\s\-\.\(\)]+/', '', (string) $request->input('customer_phone')) : null,
            'package_name' => $request->filled('package_name') ? BookingPackage::normalizeName((string) $request->input('package_name')) : null,
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'order_code' => ['nullable', 'string', 'max:50'],
            'booking_date' => ['required', 'date'],
            'booking_time' => ['required', 'date_format:H:i'],
            'package_name' => ['nullable', 'string', 'max:100'],
            'people_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'customer_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'univ' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'arrived_time' => ['nullable', 'date_format:H:i'],
            'started_time' => ['nullable', 'date_format:H:i'],
            'finished_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'review_note' => ['nullable', 'string', 'max:500'],
        ], [
            'customer_name.required' => 'Isi nama pelanggan.',
            'booking_date.required' => 'Isi tanggal booking.',
            'booking_time.required' => 'Isi jam booking.',
            'booking_time.date_format' => 'Jam harus berformat JJ:MM, mis. 09:30.',
            'customer_phone.regex' => 'No. telepon: hanya angka (boleh diawali +), 8-15 digit.',
        ]);

        $data['needs_review'] = $request->boolean('needs_review');

        return $data;
    }
}
