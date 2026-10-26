<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\Location;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function create(Location $location): View
    {
        abort_unless($location->offersWalkInTicket(), 404);

        if (!$location->isOpen()) {
            return view('tickets.closed', compact('location'));
        }

        $serviceCategories = $location->serviceCategories()->where('is_active', true)->get();

        $gpsConfig = $location->hasGpsRestriction() ? [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'radius' => $location->gps_radius_meters,
        ] : null;

        return view('tickets.create', compact('location', 'serviceCategories', 'gpsConfig'));
    }

    public function store(Request $request, Location $location): RedirectResponse
    {
        abort_unless($location->offersWalkInTicket(), 404);

        if (!$location->isOpen()) {
            return back()->with('error', 'Lokasi sedang tutup.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['required', 'email', 'max:255'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($location->hasGpsRestriction()) {
            $lat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
            $lng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

            if (!$location->isWithinGpsRadius($lat, $lng)) {
                return back()->withInput()->with(
                    'error',
                    'Pengambilan tiket cuma bisa dilakukan di lokasi ' . $location->name . '. Pastikan GPS/lokasi HP Anda aktif dan Anda berada di lokasi.'
                );
            }
        }

        $ticket = Ticket::createTicket(
            $location,
            $validated['customer_name'],
            $validated['customer_phone'],
            $validated['customer_email'] ?? null,
            $validated['service_category_id'] ?? null,
            $validated['notes'] ?? null
        );

        broadcast(new QueueUpdated($location))->toOthers();

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Tiket antrian berhasil dibuat!');
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['location', 'counter', 'feedback', 'serviceCategory']);

        return view('tickets.show', compact('ticket'));
    }

    public function check(Request $request): View|RedirectResponse
    {
        if ($request->has('ticket_number')) {
            $ticket = Ticket::where('ticket_number', $request->ticket_number)
                ->whereDate('created_at', today())
                ->first();

            if ($ticket) {
                return redirect()->route('tickets.show', $ticket);
            }

            return back()->with('error', 'Tiket tidak ditemukan.');
        }

        return view('tickets.check');
    }
}
