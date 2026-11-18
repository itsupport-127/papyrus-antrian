<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::where('is_active', true)
            ->withCount(['tickets as waiting_count' => function ($query) {
                $query->where('status', 'waiting');
            }])
            ->get()
            ->filter(fn ($l) => $l->offersWalkInTicket())
            ->values();

        return view('locations.index', compact('locations'));
    }

    public function show(Request $request, Location $location): View
    {
        $waitingTickets = $location->waitingTickets()->get();
        $currentlyServing = $location->tickets()
            ->whereIn('status', ['calling', 'serving', 'editing'])
            ->with('counter')
            ->get();

        // Dibuka dari tiket pelanggan (?ticket=PP-041): tampilkan antrian di
        // TAHAP/SESI tiket itu saja + posisi pelanggan, supaya dia tahu masih
        // lama atau sebentar lagi. Tanpa parameter = tampilan umum seperti biasa.
        $myTicket = null;
        $myAhead = null;
        $ticketNo = trim((string) $request->query('ticket', ''));
        if ($ticketNo !== '') {
            $myTicket = $location->tickets()->where('ticket_number', $ticketNo)->latest('id')->first();
        }
        if ($myTicket) {
            if ($myTicket->status === 'waiting') {
                $waitingTickets = $waitingTickets->where('stage', $myTicket->stage)->values();
                $idx = $waitingTickets->search(fn ($t) => $t->id === $myTicket->id);
                $myAhead = $idx === false ? null : $idx;
            }
        }

        return view('locations.show', compact('location', 'waitingTickets', 'currentlyServing', 'myTicket', 'myAhead'));
    }

    public function display(Location $location): View
    {
        return view('locations.display', compact('location'));
    }

    /**
     * Versi gabungan semua lokasi aktif sekaligus (PP + BK, dst) -- untuk TV
     * ruang tunggu yang jadi pemberhentian bareng pelanggan dari lokasi
     * manapun. Beda dari display() di atas yang scope-nya cuma 1 lokasi.
     */
    public function displayAll(): View
    {
        return view('locations.display-all');
    }

    /**
     * Halaman QR code yang bisa dicetak dan ditempel/diletakkan di kasir,
     * agar pelanggan tinggal scan untuk membuka form order.
     */
    public function scan(Location $location): View
    {
        return view('locations.scan', compact('location'));
    }
}
