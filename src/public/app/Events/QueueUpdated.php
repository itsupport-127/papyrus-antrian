<?php

namespace App\Events;

use App\Models\Location;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Location $location
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('location.' . $this->location->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        $waitingTickets = $this->location->waitingTickets()->with('location')->get();

        return [
            'location_id' => $this->location->id,
            'waiting_count' => $waitingTickets->count(),
            'tickets' => $waitingTickets->map(fn ($ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'customer_name' => $ticket->customer_name,
                'position' => $ticket->position,
                'estimated_wait_time' => $ticket->estimated_wait_time,
                'created_at' => $ticket->created_at->toISOString(),
            ])->toArray(),
        ];
    }
}
