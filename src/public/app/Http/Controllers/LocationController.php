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
            ->get();

        return view('locations.index', compact('locations'));
    }

    public function show(Location $location): View
    {
        $location->load(['counters' => function ($query) {
            $query->where('is_active', true)->with('currentOperator');
        }]);

        $waitingTickets = $location->waitingTickets()->get();
        $currentlyServing = $location->tickets()
            ->whereIn('status', ['calling', 'serving'])
            ->with('counter')
            ->get();

        return view('locations.show', compact('location', 'waitingTickets', 'currentlyServing'));
    }

    public function display(Location $location): View
    {
        return view('locations.display', compact('location'));
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
