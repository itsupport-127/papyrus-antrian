<?php

namespace App\Models;

use App\Events\TicketCalled;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'service_category_id',
        'counter_id',
        'served_by',
        'ticket_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'stage',
        'called_at',
        'served_at',
        'completed_at',
        'registration_completed_at',
        'photo_completed_at',
        'payment_completed_at',
        'service_time',
        'notes',
        'skip_count',
        'last_skipped_at',
    ];

    public const STAGE_LABELS = [
        'registration' => 'Registrasi',
        'photo_session' => 'Sesi Foto',
        'payment' => 'Pembayaran & Cetak Foto',
    ];

    // Urutan tahapan / 3 "panggilan" pada alur studio foto.
    public const STAGE_ORDER = ['registration', 'photo_session', 'payment'];

    // Setelah dilewati sebanyak ini (pelanggan tidak ada di tempat berkali-kali),
    // tiket otomatis dibatalkan alih-alih terus menumpuk di antrian.
    public const MAX_SKIPS = 3;

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'completed_at' => 'datetime',
            'registration_completed_at' => 'datetime',
            'photo_completed_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'service_time' => 'integer',
            'skip_count' => 'integer',
            'last_skipped_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function stageLabel(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? $this->stage;
    }

    /**
     * Position within the queue for its current stage (not the whole location).
     */
    public function getPositionAttribute(): int
    {
        if ($this->status !== 'waiting') {
            return 0;
        }

        return Ticket::where('location_id', $this->location_id)
            ->where('stage', $this->stage)
            ->where('status', 'waiting')
            ->where('created_at', '<', $this->created_at)
            ->count() + 1;
    }

    public function getEstimatedWaitTimeAttribute(): int
    {
        if ($this->status !== 'waiting') {
            return 0;
        }

        return $this->location->getEstimatedWaitTime($this->position);
    }

    public function call(Counter $counter, User $operator): void
    {
        $this->update([
            'status' => 'calling',
            'counter_id' => $counter->id,
            'served_by' => $operator->id,
            'called_at' => now(),
        ]);

        broadcast(new TicketCalled($this))->toOthers();
        broadcast(new TicketStatusChanged($this))->toOthers();

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '🔔 Giliran Anda!',
            "Tiket {$this->ticket_number} dipanggil ke {$counter->name}. Silakan menuju lokasi sekarang.",
            ['url' => route('tickets.show', $this)]
        );
    }

    public function startServing(): void
    {
        $this->update([
            'status' => 'serving',
            'served_at' => now(),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * Move the ticket from "registration" into the "photo_session" queue.
     * Registration itself is just a note (see Ticket::notes) — there is no
     * itemized order or pricing in this system.
     */
    public function completeRegistration(): void
    {
        $this->update([
            'stage' => 'photo_session',
            'status' => 'waiting',
            'counter_id' => null,
            'served_by' => null,
            'called_at' => null,
            'served_at' => null,
            'registration_completed_at' => now(),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * Advance the ticket to the next stage of the pipeline, or fully
     * complete it if it was already in the last stage (payment).
     */
    public function advanceStage(): void
    {
        if ($this->stage === 'photo_session') {
            $this->update([
                'stage' => 'payment',
                'status' => 'waiting',
                'counter_id' => null,
                'served_by' => null,
                'called_at' => null,
                'served_at' => null,
                'photo_completed_at' => now(),
            ]);

            broadcast(new TicketStatusChanged($this))->toOthers();

            return;
        }

        // stage === 'payment' -> selesai total (bayar + cetak foto)
        $serviceTime = $this->served_at ? now()->diffInSeconds($this->served_at) : null;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'payment_completed_at' => now(),
            'service_time' => $serviceTime,
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * "Lewati" — dipanggil saat pelanggan tidak ada di tempat saat dipanggil.
     * Selama belum melewati batas MAX_SKIPS, tiket dikembalikan ke antrian
     * menunggu pada tahap yang sama dan ditaruh di belakang antrian (lihat
     * Location::waitingTickets dan query "panggil berikutnya" yang
     * mengurutkan berdasarkan COALESCE(last_skipped_at, created_at)) supaya
     * pelanggan lain yang sudah menunggu tetap dipanggil lebih dulu.
     *
     * Setelah dilewati sebanyak MAX_SKIPS kali (pelanggan berkali-kali tidak
     * ada di tempat, misalnya sampai toko mau tutup), tiket otomatis
     * dibatalkan supaya tidak terus menumpuk dan operator tidak perlu
     * memanggilnya berulang-ulang tanpa hasil.
     */
    public function skip(): void
    {
        $newSkipCount = $this->skip_count + 1;

        if ($newSkipCount >= self::MAX_SKIPS) {
            $this->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'skip_count' => $newSkipCount,
                'last_skipped_at' => now(),
                'notes' => trim(($this->notes ? $this->notes . ' | ' : '') . 'Dibatalkan otomatis: dilewati ' . $newSkipCount . 'x, pelanggan tidak ada di tempat.'),
            ]);

            broadcast(new TicketStatusChanged($this))->toOthers();

            return;
        }

        $this->update([
            'status' => 'waiting',
            'counter_id' => null,
            'served_by' => null,
            'called_at' => null,
            'served_at' => null,
            'skip_count' => $newSkipCount,
            'last_skipped_at' => now(),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    public static function createTicket(Location $location, ?string $customerName = null, ?string $customerPhone = null, ?string $customerEmail = null, ?int $serviceCategoryId = null): self
    {
        $ticket = self::create([
            'location_id' => $location->id,
            'service_category_id' => $serviceCategoryId,
            'ticket_number' => $location->generateTicketNumber(),
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'status' => 'waiting',
            'stage' => 'registration',
        ]);

        broadcast(new TicketCreated($ticket))->toOthers();

        return $ticket;
    }
}
