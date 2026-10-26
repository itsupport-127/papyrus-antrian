<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'display_label',
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

    /**
     * Nama yang ditampilkan ke PELANGGAN (layar antrian, pesan panggilan,
     * notifikasi WA/browser). Beda dari `name` yang cuma dipakai Admin
     * buat bedain loket/device/staf secara internal -- mis. beberapa
     * loket bernama "CS - PC Bayar" & "CS - PC Panggil" (biar bisa
     * dipegang beberapa akun/device sekaligus tanpa saling lepas, dan
     * riwayat operator per device tetap kebedain) tapi sama-sama mau
     * muncul ke pelanggan sebagai "CS" saja, bukan "CS 1"/"CS 2".
     *
     * Kalau `display_label` gak diisi Admin, fallback ke `name` seperti
     * biasa -- jadi loket lain yang gak butuh fitur ini gak perlu diubah.
     */
    public function publicLabel(): string
    {
        return $this->display_label ?: $this->name;
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
     *
     * TIDAK ADA batasan jumlah -- boleh lebih dari 1 orang "Siap-siap"
     * bersamaan buat loket yang sama (mis. ruang ganti lebih dari 1),
     * murni keputusan operator, bukan sistem. Method ini cuma ambil YANG
     * PERTAMA (buat kode lama yang masih asumsi satu) -- kalau butuh
     * SEMUANYA, pakai preparingTickets() di bawah.
     */
    public function preparingTicket(): ?Ticket
    {
        return $this->tickets()->where('status', 'preparing')->first();
    }

    /**
     * Versi jamak dari preparingTicket() di atas -- semua tiket yang lagi
     * "Siap-siap" buat loket ini, bisa lebih dari 1.
     */
    public function preparingTickets(): Collection
    {
        return $this->tickets()->where('status', 'preparing')->get();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && !$this->is_paused && $this->currentOperator !== null && $this->currentTicket() === null;
    }
}
