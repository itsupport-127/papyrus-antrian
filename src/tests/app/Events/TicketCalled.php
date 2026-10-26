<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCalled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('location.' . $this->ticket->location_id),
            new Channel('ticket.' . $this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.called';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'customer_name' => $this->ticket->customer_name,
                'status' => $this->ticket->status,
                'called_at' => $this->ticket->called_at?->toISOString(),
            ],
            'counter' => [
                'id' => $this->ticket->counter_id,
                'name' => $this->ticket->counter?->name,
            ],
            'location_id' => $this->ticket->location_id,
        ];
    }
}
