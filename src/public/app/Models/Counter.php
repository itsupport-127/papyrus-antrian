<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'stages',
        'is_active',
        'current_operator_id',
        'is_paused',
    ];

    public const STAGE_LABELS = [
        'registration' => 'Registrasi',
        'photo_session' => 'Sesi Foto',
        'payment' => 'Pembayaran & Cetak',
    ];

    /**
     * Label gabungan untuk semua tahap yang dilayani loket ini, misalnya
     * "Registrasi + Pembayaran & Cetak" untuk loket CS & Kasir.
     */
    public function stageLabel(): string
    {
        $stages = $this->stages ?? [];

        if (empty($stages)) {
            return '-';
        }

        return collect($stages)
            ->map(fn (string $stage) => self::STAGE_LABELS[$stage] ?? $stage)
            ->implode(' + ');
    }

    public function handlesStage(string $stage): bool
    {
        return in_array($stage, $this->stages ?? [], true);
    }

    protected function casts(): array
    {
        return [
            'stages' => 'array',
            'is_active' => 'boolean',
            'is_paused' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function currentOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_operator_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function currentTicket(): ?Ticket
    {
        return $this->tickets()
            ->whereIn('status', ['calling', 'serving'])
            ->orderByDesc('called_at')
            ->first();
    }

    /**
     * Tiket-tiket (bisa lebih dari satu, lihat batchStartEditing()) yang
     * lagi diedit lewat loket ini. Ditampilkan terpisah dari
     * currentTicket() supaya loket bisa langsung dipakai buat pelanggan
     * berikutnya begitu proses edit dimulai -- editing adalah kerjaan meja,
     * bukan kerjaan studio, jadi gak perlu "menyandera" loket.
     */
    public function editingTickets()
    {
        return $this->tickets()
            ->where('status', Ticket::STATUS_EDITING)
            ->orderByDesc('called_at')
            ->get();
    }

    /**
     * Tiket yang sudah dipanggil "Siap-siap" (status `preparing`) buat
     * loket ini — dipanggil lebih awal supaya pelanggan bisa bersiap
     * (ganti baju, dsb) selagi pelanggan sebelumnya masih difoto. Lihat
     * docs/ALUR-ANTRIAN-STUDIO.md bagian "Siap-siap (pipelining)".
     */
    public function preparingTicket(): ?Ticket
    {
        return $this->tickets()->where('status', 'preparing')->first();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && !$this->is_paused && $this->currentOperator !== null && $this->currentTicket() === null;
    }
}
