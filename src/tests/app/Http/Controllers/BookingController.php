<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Location;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService)
    {
    }
    public function create(Location $location): View
    {
        abort_unless($location->offersBooking(), 404);

        $serviceCategories = $location->serviceCategories()->where('is_active', true)->get();

        return view('bookings.create', compact('location', 'serviceCategories'));
    }

    /**
     * JSON: daftar jam yang sudah dipakai reservasi lain pada tanggal
     * tertentu (khusus mode "free"). Dipakai di sisi client untuk
     * mengingatkan pelanggan kalau jam yang mereka pilih bentrok dengan
     * reservasi lain — bukan untuk memblokir, karena mode "free" memang
     * tidak punya batas kapasitas per jam.
     */
    public function takenTimes(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        if (!$location->isFreeBookingMode()) {
            return response()->json(['times' => []]);
        }

        $times = $location->bookings()
            ->whereDate('booking_date', $validated['date'])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->get()
            ->map(fn ($b) => is_string($b->booking_time) ? substr($b->booking_time, 0, 5) : $b->booking_time->format('H:i'))
            ->values();

        return response()->json(['times' => $times]);
    }

    /**
     * JSON: daftar slot untuk tanggal tertentu + sisa kapasitasnya.
     * Dipanggil dari JS saat pelanggan memilih tanggal di form reservasi.
     * Kalau lokasi pakai mode "free", endpoint ini tidak dipakai —
     * form langsung menampilkan input jam bebas di sisi client.
     */
    public function availableSlots(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        if ($location->isFreeBookingMode()) {
            return response()->json([
                'mode' => 'free',
                'open_time' => $location->open_time->format('H:i'),
                'close_time' => $location->close_time->format('H:i'),
            ]);
        }

        $date = \Carbon\Carbon::parse($validated['date']);
        $dayOfWeek = (int) $date->dayOfWeek;

        $slots = BookingSlot::where('location_id', $location->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($slots->isEmpty()) {
            $hasAnySlotConfigured = BookingSlot::where('location_id', $location->id)->exists();

            return response()->json([
                'slots' => [],
                'schedule_unconfigured' => !$hasAnySlotConfigured,
            ]);
        }

        $bookedCounts = Booking::where('location_id', $location->id)
            ->whereDate('booking_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy(fn($b) => $b->booking_time->format('H:i'))
            ->map->count();

        $isToday = $date->isToday();
        $now = now();

        $result = $slots->map(function (BookingSlot $slot) use ($bookedCounts, $isToday, $now) {
            $time = $slot->start_time->format('H:i');
            $booked = $bookedCounts->get($time, 0);
            $remaining = max(0, $slot->capacity - $booked);

            if ($isToday && \Carbon\Carbon::createFromFormat('H:i', $time)->lt($now)) {
                $remaining = 0;
            }

            return [
                'time' => $time,
                'capacity' => $slot->capacity,
                'remaining' => $remaining,
                'available' => $remaining > 0,
            ];
        })->values();

        return response()->json(['slots' => $result]);
    }

    public function store(Request $request, Location $location): RedirectResponse
    {
        abort_unless($location->offersBooking(), 404);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
        ]);

        try {
            $booking = $this->bookingService->create($location, $validated);
        } catch (\App\Exceptions\BookingCreationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Reservasi berhasil dibuat!');
    }

    public function show(Booking $booking): View
    {
        $booking->load(['location', 'serviceCategory', 'ticket']);

        return view('bookings.show', compact('booking'));
    }

    public function checkIn(Request $request, Booking $booking): RedirectResponse
    {
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Reservasi tidak dapat di-check in.');
        }

        if (!$booking->booking_date->isToday()) {
            return back()->with('error', 'Check-in hanya bisa dilakukan pada hari reservasi.');
        }

        $request->validate([
            'booking_code' => ['required', 'string', 'max:255'],
        ]);

        if ($request->input('booking_code') !== $booking->booking_code) {
            return back()->with('error', 'Kode reservasi tidak cocok.');
        }

        $ticket = $booking->checkIn();

        if (!$ticket) {
            return back()->with('error', 'Gagal melakukan check-in.');
        }

        broadcast(new QueueUpdated($booking->location))->toOthers();

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Check-in berhasil! Tiket antrian Anda: ' . $ticket->ticket_number);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        if (in_array($booking->status, ['checked_in', 'completed'])) {
            return back()->with('error', 'Reservasi tidak dapat dibatalkan.');
        }

        $request->validate([
            'booking_code' => ['required', 'string', 'max:255'],
        ]);

        if ($request->input('booking_code') !== $booking->booking_code) {
            return back()->with('error', 'Kode reservasi tidak cocok.');
        }

        $isStaff = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isOperator());

        if (!$isStaff && !$booking->canBeCancelledByCustomer()) {
            $cutoffHours = Booking::CUSTOMER_CANCEL_CUTOFF_MINUTES / 60;
            return back()->with('error', "Reservasi hanya bisa dibatalkan sendiri paling lambat {$cutoffHours} jam sebelum jadwal. Silakan hubungi lokasi langsung untuk mengubah jadwal.");
        }

        $booking->cancel();

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    /**
     * Ubah jadwal (tanggal/jam) reservasi — khusus CS & Kasir dan Admin.
     * Dipakai kalau pelanggan minta pindah jadwal, supaya tidak perlu bikin
     * reservasi baru (yang bisa bikin data ganda & antrian bingung).
     */
    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        if (!auth()->check() || !(auth()->user()->isAdmin() || auth()->user()->isOperator())) {
            abort(403, 'Hanya CS & Kasir atau Admin yang dapat mengubah jadwal reservasi.');
        }

        if (in_array($booking->status, ['checked_in', 'completed', 'cancelled'])) {
            return back()->with('error', 'Reservasi ini sudah tidak bisa diubah jadwalnya.');
        }

        $location = $booking->location;

        $validated = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
        ]);

        $openTime = $location->open_time->format('H:i');
        $closeTime = $location->close_time->format('H:i');

        if ($validated['booking_time'] < $openTime || $validated['booking_time'] > $closeTime) {
            return back()->withInput()->with('error', "Jam baru harus di antara {$openTime} - {$closeTime} (jam operasional lokasi).");
        }

        $booking->reschedule($validated['booking_date'], $validated['booking_time']);

        return back()->with('success', "Jadwal reservasi {$booking->booking_code} berhasil diubah ke {$validated['booking_date']} {$validated['booking_time']}.");
    }

    public function check(Request $request): View|RedirectResponse
    {
        if ($request->has('booking_code')) {
            $booking = Booking::where('booking_code', $request->booking_code)->first();

            if ($booking) {
                return redirect()->route('bookings.show', $booking);
            }

            return back()->with('error', 'Kode reservasi tidak ditemukan.');
        }

        return view('bookings.check');
    }
}