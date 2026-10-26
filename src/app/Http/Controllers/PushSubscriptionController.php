<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Save (or refresh) the customer's push subscription for this ticket.
     * Public endpoint — customers are never authenticated.
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'ticket_id' => $ticket->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Remove a subscription (e.g. the customer disabled notifications
     * in-browser, or the endpoint went stale on the client side).
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $ticket->pushSubscriptions()->where('endpoint', $validated['endpoint'])->delete();

        return response()->json(['status' => 'ok']);
    }
}
