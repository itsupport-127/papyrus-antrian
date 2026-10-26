<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        // Only completed tickets can receive feedback
        if ($ticket->status !== 'completed') {
            return back()->with('error', 'Feedback hanya dapat diberikan untuk tiket yang sudah selesai.');
        }

        // Prevent duplicate feedback
        if ($ticket->feedback) {
            return back()->with('error', 'Anda sudah memberikan feedback untuk tiket ini.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket->feedback()->create($validated);

        return back()->with('success', 'Terima kasih atas feedback Anda!');
    }
}
