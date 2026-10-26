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
        'photo_called_at',
        'photo_served_at',
        'photo_completed_at',
        'editing_started_at',
        'ready_for_edit_at',
        'payment_called_at',
        'payment_served_at',
        'payment_completed_at',
        'service_time',
        'notes',
        'confirmed_at',
        'notes_locked_at',
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

    public const STATUS_READY_FOR_EDIT = 'ready_for_edit';
    public const STATUS_EDITING = 'editing';

    // Setelah dilewati sebanyak ini (pelanggan tidak ada di tempat berkali-kali),
    // tiket otomatis dibatalkan alih-alih terus menumpuk di antrian.
    public const MAX_SKIPS = 3;

    /**
     * Kode order permanen, dibuat dari primary key (auto-increment, tidak
     * pernah reset). Beda dengan `ticket_number` (mis. "PP-001") yang cuma
     * nomor urut harian dan diulang lagi dari 001 tiap hari — jadi kalau mau
     * cari/lacak tiket tertentu lintas hari, "PP-001" saja ambigu (bisa jadi
     * banyak tiket berbeda di hari yang berbeda). Kode ini unik selamanya.
     */
    public function getOrderCodeAttribute(): string
    {
        return 'ORD-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'completed_at' => 'datetime',
            'registration_completed_at' => 'datetime',
            'photo_called_at' => 'datetime',
            'photo_served_at' => 'datetime',
            'photo_completed_at' => 'datetime',
            'editing_started_at' => 'datetime',
            'ready_for_edit_at' => 'datetime',
            'notes_locked_at' => 'datetime',
            'payment_called_at' => 'datetime',
            'payment_served_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'notes_locked_at' => 'datetime',
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

    /**
     * Label status yang ramah buat ditampilkan ke pelanggan — jangan pernah
     * tampilkan `stage`/`status` mentah-mentah di halaman pelanggan.
     * Lihat tabel lengkapnya di docs/ALUR-ANTRIAN-STUDIO.md §4.
     */
    public function customerStatusLabel(): string
    {
        if ($this->status === 'completed') {
            return 'Selesai';
        }

        if ($this->status === 'cancelled') {
            return 'Dibatalkan';
        }

        if ($this->stage === 'registration') {
            if ($this->status === 'waiting') {
                return 'Menunggu Dipanggil';
            }

            return 'Sedang Konsultasi';
        }

        if ($this->stage === 'photo_session') {
            return match ($this->status) {
                'waiting' => 'Menunggu Sesi Foto',
                'calling' => 'Dipanggil ke ' . ($this->counter->name ?? 'Studio'),
                'preparing' => 'Silakan Bersiap-siap di ' . ($this->counter->name ?? 'Studio') . ' — Sebentar Lagi Giliran Anda',
                'serving' => 'Foto Anda Sedang Diambil',
                'ready_for_edit' => 'Pengambilan Foto Selesai, Menunggu Diedit',
                'editing' => 'Foto Anda Sedang Diedit',
                default => $this->stageLabel(),
            };
        }

        if ($this->stage === 'payment') {
            return match ($this->status) {
                'waiting' => 'Foto Selesai, Menunggu Pembayaran',
                'calling', 'serving' => 'Dipanggil ke Kasir',
                default => $this->stageLabel(),
            };
        }

        return $this->stageLabel();
    }

    public function stageLabel(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? $this->stage;
    }

    /**
     * Breakdown durasi per tahap buat widget "WAKTU" (Operator > Data
     * Pelanggan). Tiap segmen punya 'start' & 'end': kalau 'end' masih null,
     * berarti tahap itu MASIH BERJALAN -- front-end wajib nampilin sebagai
     * hitungan hidup (live ticking dari 'start' sampai sekarang), BUKAN
     * angka mati, dan baru boleh dibekukan jadi angka final begitu tiket
     * berstatus 'completed' (atau 'cancelled'). Kalau 'start' null, berarti
     * tahap itu belum/tidak pernah kejadian buat tiket ini -- tampilin '-'.
     *
     * called_at/served_at di-reset tiap pindah stage (lihat call() &
     * startServing() di atas), jadi segmen di bawah ini sengaja pakai
     * kolom salinan (photo_called_at, photo_served_at, editing_started_at,
     * payment_called_at, payment_served_at) yang gak ikut ke-reset.
     */
    public function stageDurations(): array
    {
        $isFinal = in_array($this->status, ['completed', 'cancelled'], true);
        $now = now();

        $seg = function (string $key, string $label, ?\Carbon\Carbon $start, ?\Carbon\Carbon $end) use ($isFinal, $now) {
            $ongoing = $start && !$end && !$isFinal;
            $effectiveEnd = $end ?? ($start ? $now : null);

            return [
                'key' => $key,
                'label' => $label,
                'available' => (bool) $start,
                'ongoing' => $ongoing,
                'start' => $start,
                'end' => $end,
                'seconds' => $start && $effectiveEnd ? max(0, $effectiveEnd->diffInSeconds($start)) : null,
            ];
        };

        $segments = [
            $seg('registration', 'Registrasi', $this->created_at, $this->registration_completed_at),
            $seg('photo_wait', 'Tunggu Foto', $this->registration_completed_at, $this->photo_called_at ?? $this->photo_served_at),
            $seg('photo_session', 'Sesi Foto', $this->photo_served_at, $this->editing_started_at ?? $this->photo_completed_at),
            $seg('editing', 'Edit Foto', $this->editing_started_at, $this->photo_completed_at),
            $seg('payment_wait', 'Tunggu Bayar', $this->photo_completed_at, $this->payment_called_at ?? $this->payment_served_at ?? $this->payment_completed_at),
        ];

        $total = $seg('total', 'Total', $this->created_at, $this->completed_at);

        return ['segments' => $segments, 'total' => $total];
    }

    /**
     * Format detik jadi teks singkat "Xd Yh" / "Xh Ym" / "Xm Ys" / "Xs".
     * Dipakai buat angka FINAL (sudah fix) di widget "WAKTU" -- buat yang
     * masih berjalan, front-end hitung sendiri live pakai JS dari timestamp
     * 'start', bukan lewat fungsi ini (biar gak perlu refresh tiap detik).
     */
    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . 'm ' . ($seconds % 60) . 's';
        }
        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . 'h ' . ($minutes % 60) . 'm';
        }
        $days = intdiv($hours, 24);
        return $days . 'd ' . ($hours % 24) . 'h';
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

    /**
     * "Panggil Siap-siap" — dipanggil fotografer buat tiket BERIKUTNYA
     * selagi tiket yang sekarang masih di-service (`serving`), supaya
     * pelanggan berikutnya bisa langsung bersiap (ganti baju, dsb) dan gak
     * ada jeda kosong begitu gilirannya tiba. Beda dari call() biasa:
     * statusnya `preparing`, bukan `calling`.
     *
     * Guard (siapa boleh dipanggil siap-siap) dicek di controller —
     * lihat OperatorController::callNextToPrepare().
     */
    public function callToPrepare(Counter $counter, User $operator): void
    {
        $this->update([
            'status' => 'preparing',
            'counter_id' => $counter->id,
            'served_by' => $operator->id,
            'called_at' => now(),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '👋 Bersiap-siap!',
            "Sebentar lagi giliran Anda di {$counter->name}. Silakan bersiap-siap (ganti baju, dsb).",
            ['url' => route('tickets.show', $this)]
        );
    }

    public function call(Counter $counter, User $operator): void
    {
        $updates = [
            'status' => 'calling',
            'counter_id' => $counter->id,
            'served_by' => $operator->id,
            'called_at' => now(),
        ];

        // Salinan khusus buat widget "WAKTU" -- called_at di atas bakal
        // di-null-kan lagi begitu tiket pindah stage, jadi kalau mau tetap
        // bisa hitung "Tunggu foto" / "Tunggu bayar" setelah tiket lanjut
        // ke stage berikutnya, nilainya harus disimpan terpisah di sini.
        if ($this->stage === 'photo_session') {
            $updates['photo_called_at'] = now();
        } elseif ($this->stage === 'payment') {
            $updates['payment_called_at'] = now();
        }

        $this->update($updates);

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
        $updates = [
            'status' => 'serving',
            'served_at' => now(),
        ];

        // Sama seperti photo_called_at/payment_called_at di call() di atas --
        // salinan buat widget "WAKTU" yang gak ikut ke-reset pas ganti stage.
        if ($this->stage === 'photo_session') {
            $updates['photo_served_at'] = now();
        } elseif ($this->stage === 'payment') {
            $updates['payment_served_at'] = now();
        }

        // Begitu sesi foto beneran mulai, kunci catatan pesanan supaya gak
        // ada yang diam-diam diubah CS/Kasir lain di tengah sesi. Cuma
        // Admin yang bisa buka kuncinya lagi (lihat guard di
        // OperatorController::updateNotes).
        if ($this->stage === 'photo_session' && !$this->notes_locked_at) {
            $updates['notes_locked_at'] = now();
        }

        $this->update($updates);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * "Foto Selesai, Mulai Edit" — dipanggil fotografer begitu pengambilan
     * foto beneran kelar dan dia mulai proses edit. INI TETAP MANUAL,
     * bukan otomatis: sistem tidak bisa menebak kapan motretnya selesai,
     * cuma fotografer yang tau. Beda dengan startServing() -> serving
     * (mulai motret): status 'editing' TIDAK menempati slot "tiket aktif"
     * loket lagi (lihat Counter::currentTicket()) -- loket langsung bebas
     * dipakai buat pelanggan berikutnya begitu status ini masuk, tapi
     * tiket tetap dipegang fotografer yang sama (lewat served_by) dan
     * tetap kelihatan di panel "Sedang Diedit" sampai tombol
     * "Selesaikan, Lanjut ke Pembayaran" (advanceStage()) diklik.
     */
    public function startEditing(): void
    {
        $updates = ['status' => 'editing'];

        if (!$this->editing_started_at) {
            $updates['editing_started_at'] = now();
        }

        $this->update($updates);

        broadcast(new TicketStatusChanged($this))->toOthers();

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '🎨 Foto Sedang Diedit',
            'Pengambilan foto Anda sudah selesai. Fotografer sedang mengedit hasilnya, mohon tunggu sebentar.',
            ['url' => route('tickets.show', $this)]
        );
    }

    /**
     * CS klik "Konfirmasi Pesanan" setelah pelanggan setuju dengan paket &
     * catatan yang ditulis. Sebelum ini dipanggil, tiket tidak boleh lanjut
     * ke stage photo_session — lihat guard di completeRegistration().
     */
    public function confirmOrder(): void
    {
        $this->update(['confirmed_at' => now()]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * Move the ticket from "registration" into the "photo_session" queue.
     * Registration itself is just a note (see Ticket::notes) — there is no
     * itemized order or pricing in this system.
     *
     * Wajib sudah dikonfirmasi (confirmOrder()) sebelum ini boleh jalan,
     * supaya tidak ada pesanan yang lanjut ke fotografer padahal belum fix.
     */
    public function completeRegistration(): bool
    {
        if (!$this->confirmed_at) {
            return false;
        }

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

        return true;
    }

    /**
     * Advance the ticket to the next stage of the pipeline, or fully
     * complete it if it was already in the last stage (payment).
     */
    public function advanceStage(): void
    {
        if ($this->stage === 'photo_session') {
            $counterId = $this->counter_id;

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

            // Kalau ada tiket lain yang tadi dipanggil "Siap-siap" di loket
            // yang sama, langsung "naik kelas" jadi Dipanggil — pelanggan
            // itu sudah standby, gak perlu dipanggil ulang dari nol.
            if ($counterId) {
                $preparingTicket = static::where('counter_id', $counterId)
                    ->where('status', 'preparing')
                    ->first();

                if ($preparingTicket) {
                    $preparingTicket->update([
                        'status' => 'calling',
                        'called_at' => now(),
                    ]);

                    broadcast(new TicketStatusChanged($preparingTicket))->toOthers();
                }
            }

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

    /**
     * "Batalkan Pemanggilan" — dipakai kalau STAFF yang salah (salah pencet,
     * ternyata gak jadi motret, dll), BUKAN karena pelanggan tidak ada di
     * tempat. Beda dengan skip(): ini TIDAK menambah skip_count, karena
     * kesalahan staff tidak boleh berujung tiket pelanggan ke-auto-cancel.
     * Tiket dikembalikan ke depan antrian (created_at asli, bukan di-skip)
     * supaya pelanggan tidak dirugikan urutan tunggunya.
     */
    public function uncall(): void
    {
        $this->update([
            'status' => 'waiting',
            'counter_id' => null,
            'served_by' => null,
            'called_at' => null,
            'served_at' => null,
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

    public static function createTicket(Location $location, ?string $customerName = null, ?string $customerPhone = null, ?string $customerEmail = null, ?int $serviceCategoryId = null, ?string $notes = null): self
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
            'notes' => $notes,
        ]);

        broadcast(new TicketCreated($ticket))->toOthers();

        return $ticket;
    }
}
