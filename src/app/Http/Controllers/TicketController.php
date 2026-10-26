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

        // Kalau yang buka form ini operator/admin yang sudah login (mis.
        // lewat tombol "Tambah Tiket" di dashboard buat bantu pelanggan
        // yang gak bisa/gak sempat scan QR sendiri), gak perlu cek GPS --
        // mereka jelas lagi di lokasi karena bertugas di sana. Cek GPS
        // cuma relevan buat pelanggan yang scan QR sendiri (guest, gak
        // login) buat mastiin mereka beneran datang ke lokasi.
        $isTrustedStaff = auth()->check() && (auth()->user()->isOperator() || auth()->user()->isAdmin());

        $gpsConfig = ($location->hasGpsRestriction() && !$isTrustedStaff) ? [
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
            'customer_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u'],
            'customer_phone' => ['required', 'string', 'regex:/^08[0-9]{8,12}$/'],
            'customer_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_name.regex' => 'Nama cuma boleh berisi huruf dan spasi (gak boleh angka/simbol).',
            'customer_phone.required' => 'No. Telepon wajib diisi.',
            'customer_phone.regex' => 'Format No. Telepon gak valid. Harus diawali "08" lalu angka saja, contoh: 081234567890.',
            'customer_email.required' => 'Email wajib diisi.',
            'customer_email.email' => 'Format email gak valid, contoh: nama@email.com.',
        ]);

        // Sama seperti di create(): operator/admin yang sudah login gak
        // perlu lolos cek GPS, karena mereka jelas lagi bertugas di lokasi.
        $isTrustedStaff = auth()->check() && (auth()->user()->isOperator() || auth()->user()->isAdmin());

        if ($location->hasGpsRestriction() && !$isTrustedStaff) {
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
