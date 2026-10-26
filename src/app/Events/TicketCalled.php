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
        public Ticket $ticket,
        // BUG YANG DIBENERIN: parameter ini SEBELUMNYA TIDAK ADA sama
        // sekali di constructor -- padahal Ticket::call() sudah manggil
        // `new TicketCalled($this, $isRecall)` dengan 2 argumen. PHP TIDAK
        // error kalau argumen kelebihan (silently diabaikan), jadi gak
        // ketahuan lewat testing manual biasa -- akibatnya $isRecall
        // SELALU hilang, never masuk ke broadcastWith(), dan frontend
        // (dashboard.blade.php: `e.is_recall === true`) SELALU membaca
        // `undefined === true` alias FALSE. Kombinasi dengan logic
        // "suara panggilan PERTAMA dihilangkan, kecuali Panggil Ulang"
        // di announceCalledTicketOperator() (kind === 'calling' && !isRecall
        // => return, gak bunyi), ini bikin SEMUA panggilan -- termasuk
        // "Panggil Ulang" yang seharusnya TETAP bunyi -- ikut kesenyapan.
        public bool $isRecall = false
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
                'stage' => $this->ticket->stage,
                'called_at' => $this->ticket->called_at?->toISOString(),
            ],
            'counter' => [
                'id' => $this->ticket->counter_id,
                'name' => $this->ticket->counter?->name,
                // Label buat PELANGGAN (display_label admin, fallback name) --
                // layar publik & HP pelanggan wajib pakai ini, bukan 'name'.
                'public_label' => $this->ticket->counter?->publicLabel(),
            ],
            'location_id' => $this->ticket->location_id,
            'is_recall' => $this->isRecall,
            // Penanda unik per siaran: layar TV memakainya supaya Panggil Ulang SELALU bersuara,
            // sementara event kembar (siaran yang sama terkirim 2x) tetap dibunyikan sekali.
            'event_at' => now()->format('Y-m-d\TH:i:s.u'),
        ];
    }
}
