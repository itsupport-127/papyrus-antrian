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
        // Salinan staf per-kelompok-tahap -- lihat komentar di migrasi
        // 2026_08_10_000001_add_stage_staff_tracking_to_tickets_table.php.
        'registered_by',
        'photographed_by',
        'paid_by',
        'edited_by',
        'editing_counter_id',
        'edit_completed_at',
        'picked_up_at',
        'payment_skipped_pending_edit_at',
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
        'select_queued_at',
        'select_called_at',
        'select_started_at',
        'edit_eta_date',
        'edit_note',
        'select_ends_at',
        'select_done_at',
        'payment_called_at',
        'payment_served_at',
        'payment_completed_at',
        'service_time',
        'notes',
        'confirmed_at',
        'notes_locked_at',
        'ready_at',
        'ready_by',
        'skip_count',
        'last_skipped_at',
        'prepare_recall_count',
        'prepare_recalled_by',
        'edit_wait_preference',
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
        return 'PJG-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
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
            'select_queued_at' => 'datetime',
            'select_called_at' => 'datetime',
            'select_started_at' => 'datetime',
            'edit_eta_date' => 'date',
            'select_ends_at' => 'datetime',
            'select_done_at' => 'datetime',
            'notes_locked_at' => 'datetime',
            'payment_called_at' => 'datetime',
            'payment_served_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'notes_locked_at' => 'datetime',
            'ready_at' => 'datetime',
            'edit_completed_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'payment_skipped_pending_edit_at' => 'datetime',
            'service_time' => 'integer',
            'skip_count' => 'integer',
            'last_skipped_at' => 'datetime',
            'prepare_recall_count' => 'integer',
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

    /** CS yang menandai tiket ini "Ready" di Siap-siap. */
    public function readyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ready_by');
    }

    /**
     * Boleh tidak $user membuka gembok catatan (Order) tiket ini?
     *
     * - Yang memegang tiket (served_by) selalu boleh.
     * - Selain itu boleh kalau tiketnya TIDAK sedang dipegang orang lain di
     *   tengah sesi aktif (dipanggil/siap-siap/dilayani/diedit) -- supaya Admin/CS
     *   bisa membetulkan Order dari Data Pelanggan untuk tiket yang menunggu,
     *   sudah selesai, atau dibatalkan, tanpa harus "mengklaim" tiketnya dulu.
     */
    public function canUnlockNotesBy(User $user): bool
    {
        if ($this->served_by === $user->id) {
            return true;
        }

        if ($this->served_by !== null
            && in_array($this->status, ['calling', 'preparing', 'serving', 'editing', 'ready_for_edit'], true)) {
            return false;
        }

        return in_array($this->location_id, $user->manageableLocationIds(), true);
    }

    // Relasi buat kolom staf per-kelompok-tahap (lihat migrasi
    // 2026_08_10_000001) -- dipakai laporan evaluasi kerja per staf.
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function photographedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographed_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Siapa yang TERAKHIR mencet "Panggil Ulang" versi CS/Kasir selagi
    // tiket ini masih "Siap-siap" (preparing) -- lihat recallPrepare().
    public function prepareRecalledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepare_recalled_by');
    }

    // Fotografer yang PERTAMA kali mulai proses edit tiket ini (baik dia
    // sendiri atau lewat "Ambil Alih" dari kerjaan orang lain). Tidak
    // ditimpa kalau tiket ini dilanjutkan/di-ambil-alih orang lain
    // setelahnya -- lihat migrasi 2026_08_18_000001.
    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    // Studio yang tanggung jawab nyelesain edit tiket ini. Beda dengan
    // `counter` (studio tiket ini SEDANG berada) -- kolom ini TIDAK ikut
    // ke-reset walau tiket sudah lanjut ke stage payment/completed lewat
    // jalur "Lanjut ke Kasir, Edit Menyusul". Lihat migrasi 2026_08_18_000001.
    public function editingCounter(): BelongsTo
    {
        return $this->belongsTo(Counter::class, 'editing_counter_id');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /** Reservasi asal tiket ini (kalau tiket berasal dari check-in reservasi). */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'ticket_id');
    }

    public function isReservation(): bool
    {
        return $this->booking !== null;
    }

    /**
     * Lagi di tahap "Pilih Foto" (khusus reservasi), di sub-status MANA PUN:
     * menunggu CS memanggil -> dipanggil -> timer jalan. Selesai kalau CS klik "Selesai Pilih Foto".
     * Selama ini aktif, tiket tidak boleh masuk proses edit / kasir.
     */
    public function isSelectingPhotos(): bool
    {
        return $this->select_queued_at !== null
            && $this->select_done_at === null
            && !in_array($this->status, ['completed', 'cancelled'], true);
    }

    /** 'waiting' (antri di CS) | 'called' (dipanggil, belum mulai) | 'selecting' (timer jalan) | null. */
    public function photoSelectionState(): ?string
    {
        if (!$this->isSelectingPhotos()) {
            return null;
        }

        if ($this->select_started_at) {
            return 'selecting';
        }

        return $this->select_called_at ? 'called' : 'waiting';
    }

    /** Sisa detik timer Pilih Foto (negatif = sudah lewat), null kalau timer belum jalan. */
    public function selectSecondsLeft(): ?int
    {
        if ($this->photoSelectionState() !== 'selecting' || !$this->select_ends_at) {
            return null;
        }

        return (int) now()->diffInSeconds($this->select_ends_at, false);
    }

    /** Sesi foto selesai -> masuk antrian Pilih Foto di CS (belum ada timer). */
    public function queuePhotoSelection(): void
    {
        $this->update([
            'select_queued_at' => now(),
            'select_called_at' => null,
            'select_started_at' => null,
            'select_ends_at' => null,
            'select_done_at' => null,
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /** CS memanggil ke Pilih Foto (dipakai juga buat Panggil Ulang). TV membunyikan suara. */
    public function callPhotoSelection(): void
    {
        $this->update(['select_called_at' => now()]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /** CS klik "Mulai Pilih Foto" (pelanggan sudah duduk): timer jalan. */
    public function beginPhotoSelection(?int $minutes = null): void
    {
        $minutes = $minutes ?? max(1, (int) ($this->location?->photo_select_minutes ?: 15));

        $this->update([
            'select_called_at' => $this->select_called_at ?? now(),
            'select_started_at' => now(),
            'select_ends_at' => now()->addMinutes($minutes),
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /** Lama edit (hari) dari paket reservasi ini; null kalau paketnya tidak dikenal / belum diatur. */
    public function packageEditDays(): ?int
    {
        $name = $this->booking?->package_name;
        if (!$name) {
            return null;
        }

        $days = BookingPackage::where('name', $name)->value('edit_days');

        return $days === null ? null : (int) $days;
    }

    /**
     * "Selesai Pilih Foto": tiket LANGSUNG ke Pembayaran dengan tanda "edit menyusul"
     * (pakai jalur Edit Menyusul yang sudah ada). Estimasi selesai edit dihitung dari paket
     * (kalau paketnya punya "lama edit"); paket 0 hari = edit hari ini, pelanggan menunggu.
     */
    public function finishPhotoSelection(int $userId): void
    {
        $days = $this->packageEditDays();

        $updates = ['select_done_at' => now()];
        if ($days !== null && !$this->edit_eta_date) {
            $updates['edit_eta_date'] = now()->addDays($days)->toDateString();
        }
        $this->update($updates);

        $this->skipToPaymentPendingEdit($userId, $days === 0 ? 'wait' : 'leave');
    }

    /** Tambah waktu (menit) ke timer yang sedang berjalan. */
    public function extendPhotoSelection(int $minutes): void
    {
        $base = $this->select_ends_at && $this->select_ends_at->isFuture() ? $this->select_ends_at : now();

        $this->update(['select_ends_at' => $base->copy()->addMinutes($minutes)]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * Langkah-langkah progres untuk tiket RESERVASI (dilihat pelanggan):
     * Sesi foto -> Pilih foto -> Pembayaran -> Edit -> Siap diambil.
     * state: done | current | todo.
     *
     * @return list<array{label: string, state: string, note: ?string}>
     */
    public function reservationSteps(): array
    {
        $photoDone = $this->select_queued_at !== null;
        $selectDone = $this->select_done_at !== null;
        $paid = $this->status === 'completed';
        $editDone = $this->edit_completed_at !== null;
        $pickedUp = $this->picked_up_at !== null;

        $done = [$photoDone, $selectDone, $paid, $editDone, $pickedUp];
        $labels = ['Sesi foto', 'Pilih foto', 'Pembayaran', 'Edit', 'Siap diambil'];

        $currentIdx = null;
        foreach ($done as $i => $d) {
            if (!$d) {
                $currentIdx = $i;
                break;
            }
        }

        $steps = [];
        foreach ($labels as $i => $label) {
            $state = $done[$i] ? 'done' : ($i === $currentIdx ? 'current' : 'todo');
            $note = null;

            if ($label === 'Edit' && $state !== 'done' && $this->payment_skipped_pending_edit_at) {
                $bits = [];
                if ($this->edit_eta_date) {
                    $bits[] = $this->edit_eta_date->isToday()
                        ? 'Estimasi selesai: hari ini'
                        : 'Estimasi selesai: ' . $this->edit_eta_date->translatedFormat('l, d M');
                }
                if ($this->edit_note) {
                    $bits[] = $this->edit_note;
                }
                $note = $bits ? implode(' · ', $bits) : null;
            }

            $steps[] = ['label' => $label, 'state' => $state, 'note' => $note];
        }

        return $steps;
    }

    /**
     * Isi order dipecah jadi baris [label|null, isi] supaya enak dibaca (satu info per baris).
     * Tiket reservasi: Kode Order (KO ...), Paket, Jumlah Orang, Tambahan, Kostum Extra, DP, + catatan.
     * Tiket biasa: tiap baris catatan apa adanya (tanpa label).
     * $forCustomer: kode order custom (KO ...) milik CS disembunyikan, "Note Order" jadi "Catatan".
     *
     * @return list<array{0: ?string, 1: string}>
     */
    public function orderRows(bool $forCustomer = false): array
    {
        $raw = trim((string) $this->notes);
        if ($raw === '') {
            return [];
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)), fn ($l) => $l !== ''));
        $hasReservation = collect($lines)->contains(fn ($l) => str_starts_with($l, 'Reservasi:'));
        $rows = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Reservasi:')) {
                foreach (preg_split('/\s·\s/u', trim(substr($line, strlen('Reservasi:')))) as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    if (preg_match('/^KO\s+(.+)$/u', $part, $m)) {
                        $rows[] = ['Kode Order', $m[1]];
                    } elseif (preg_match('/^(\d+)\s+orang$/u', $part, $m)) {
                        $rows[] = ['Jumlah Orang', $m[1] . ' orang'];
                    } elseif (preg_match('/^Tambahan:\s*(.+)$/u', $part, $m)) {
                        $rows[] = ['Tambahan', $m[1]];
                    } elseif (preg_match('/^\+(\d+)\s+kostum$/u', $part, $m)) {
                        $rows[] = ['Kostum Extra', '+' . $m[1]];
                    } elseif (preg_match('/^DP\b/u', $part)) {
                        $rows[] = ['DP', $part];
                    } else {
                        $rows[] = ['Paket', $part];
                    }
                }
            } else {
                $rows[] = [$hasReservation ? ($forCustomer ? 'Catatan' : 'Note Order') : null, $line];
            }
        }

        if ($forCustomer) {
            $rows = array_values(array_filter($rows, fn ($r) => $r[0] !== 'Kode Order'));
        }

        return $rows;
    }

    /** Tiket yang TIDAK sedang di tahap Pilih Foto (dipakai antrian edit). */
    public function scopeNotSelectingPhotos($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('select_queued_at')->orWhereNotNull('select_done_at');
        });
    }

    /** Tiket yang sedang di tahap Pilih Foto. */
    public function scopeSelectingPhotos($query)
    {
        return $query->whereNotNull('select_queued_at')
            ->whereNull('select_done_at')
            ->whereNotIn('status', ['completed', 'cancelled']);
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
    /**
     * True kalau tiket ini sudah "kadung" lanjut ke pembayaran/selesai
     * lewat "Lanjut ke Kasir — Edit Menyusul" (skipToPaymentPendingEdit())
     * SEBELUM editnya beneran kelar. Selama ini true, tiket masih harus
     * nongol di panel "Sudah Dibayar, Edit Belum Selesai" studio yang
     * tercatat di `editing_counter_id`, dan pelanggan/kasir perlu tau
     * lewat label khusus (lihat customerStatusLabel() di bawah).
     */
    /**
     * True kalau tiket ini SUDAH kadung dilewatin ke pembayaran sebelum
     * editnya kelar (lewat skipToPaymentPendingEdit()), dan editnya masih
     * belum ditandai selesai sampai sekarang.
     *
     * PENTING: patokan utamanya `payment_skipped_pending_edit_at` (bukan
     * cuma editing_counter_id) -- kolom itu SATU-SATUNYA penanda yang
     * benar-benar eksklusif buat tiket yang lewat skip-flow. Tanpa ini,
     * tiket normal (yang juga akhirnya punya editing_counter_id &
     * edit_completed_at terisi lewat alur biasa) bisa ketuker keanggap
     * "istimewa" juga.
     */
    public function isEditPending(): bool
    {
        return $this->payment_skipped_pending_edit_at !== null && $this->edit_completed_at === null;
    }

    /**
     * True kalau tiket ini lewat jalur "Lanjut ke Kasir — Edit Menyusul",
     * SUDAH LUNAS, editnya SUDAH kelar, tapi hasil cetaknya belum ditandai
     * diserahkan ke pelanggan. Beda dengan isEditPending() (yang berarti
     * editnya BELUM kelar) -- ini fase berikutnya, buat nge-flag "siap
     * diambil / masih nyangkut di kasir nunggu pelanggan balik".
     *
     * Wajib cek `payment_skipped_pending_edit_at` (bukan cuma
     * editing_counter_id/edit_completed_at) DAN status === 'completed'
     * (artinya sudah LUNAS) -- lihat catatan lengkap di isEditPending() dan
     * migrasi 2026_08_27_000001 kenapa kedua hal ini penting. Tanpa
     * keduanya, badge "Siap Diambil" & tombol "Sudah Diambil" bisa salah
     * nongol untuk (a) tiket normal yang sebenarnya gak butuh alur ini
     * sama sekali, atau (b) tiket yang editnya sudah kelar tapi belum bayar.
     */
    public function isReadyForPickup(): bool
    {
        return $this->payment_skipped_pending_edit_at !== null
            && $this->status === 'completed'
            && $this->edit_completed_at !== null
            && $this->picked_up_at === null;
    }

    /**
     * Kasir klik "Sudah Diambil" begitu hasil cetak beneran diserahkan ke
     * pelanggan -- baik itu pelanggan balik hari yang sama atau nyebrang
     * hari (setelah lewat skipToPaymentPendingEdit() + markEditCompleted()).
     */
    public function markPickedUp(): void
    {
        $this->update(['picked_up_at' => now()]);
    }

    public function customerStatusLabel(): string
    {
        if ($this->isSelectingPhotos()) {
            return match ($this->photoSelectionState()) {
                'waiting' => 'Pilih Foto — Menunggu Dipanggil CS',
                'called' => 'Pilih Foto — Silakan Menuju CS & Kasir',
                default => 'Pilih Foto — Silakan Pilih Foto Anda',
            };
        }

        if ($this->status === 'completed') {
            if ($this->isEditPending()) {
                return 'Selesai — Hasil Edit Menyusul';
            }
            if ($this->isReadyForPickup()) {
                return 'Hasil Edit Selesai — Silakan Diambil';
            }
            return 'Selesai';
        }

        if ($this->status === 'cancelled') {
            return 'Dibatalkan';
        }

        if ($this->stage === 'registration') {
            return match ($this->status) {
                'waiting' => 'Registrasi — Menunggu Dipanggil ke CS',
                'calling' => 'Registrasi — Silakan Menuju CS',
                // 'serving' dipecah: sebelum vs sesudah "Konfirmasi Pesanan"
                // diklik CS -- biar pelanggan tahu persis sudah sampai mana.
                'serving' => $this->confirmed_at
                    ? 'Registrasi Selesai (Order ' . $this->order_code . ') — Menunggu Diarahkan ke Sesi Foto'
                    : 'Registrasi — Sedang Konsultasi Order dengan CS',
                default => $this->stageLabel(),
            };
        }

        if ($this->stage === 'photo_session') {
            return match ($this->status) {
                'waiting' => 'Sesi Foto — Menunggu Giliran',
                'preparing' => 'Siap-siap di Ruang Ganti — Sebentar Lagi Giliran Anda ke ' . ($this->counter?->publicLabel() ?? 'Studio'),
                'calling' => 'Sesi Foto — Silakan Masuk ke ' . ($this->counter?->publicLabel() ?? 'Studio'),
                'serving' => 'Sesi Foto — Foto Anda Sedang Diambil',
                'ready_for_edit' => 'Foto Selesai Diambil — Menunggu Diedit',
                'editing' => 'Foto Anda Sedang Diedit',
                default => $this->stageLabel(),
            };
        }

        if ($this->stage === 'payment') {
            return match ($this->status) {
                'waiting' => $this->isEditPending()
                    ? 'Pembayaran — Menunggu Dipanggil ke Kasir (Hasil Edit Menyusul)'
                    : 'Pembayaran — Menunggu Dipanggil ke Kasir',
                'calling' => 'Pembayaran — Silakan Menuju Kasir',
                // 'calling' = baru dipanggil, belum sampai kasir; 'serving' =
                // benar-benar sedang diproses di kasir. Dipisah biar tidak salah paham.
                'serving' => 'Pembayaran — Sedang Diproses di Kasir',
                default => $this->stageLabel(),
            };
        }

        return $this->stageLabel();
    }

    /**
     * Penjelasan 1 kalimat untuk pelanggan (ditampilkan di bawah label status
     * di halaman tiket) -- biar jelas "sekarang lagi apa & habis ini apa".
     * Status 'calling', 'preparing', 'editing', dst sudah punya teks sendiri
     * di tampilan, jadi di sini hanya waiting & serving.
     */
    public function customerStatusHint(): ?string
    {
        if ($this->isSelectingPhotos()) {
            return match ($this->photoSelectionState()) {
                'waiting' => 'Sesi foto selesai. Mohon tunggu sebentar, CS akan memanggil Anda untuk memilih foto.',
                'called' => 'Silakan menuju CS & Kasir untuk dipandu memilih foto di komputer preview.',
                default => 'Pilih foto Anda sebelum waktu habis; petugas akan membantu. Setelah ini pembayaran, lalu foto diedit dan kami kabari progresnya.',
            };
        }

        if ($this->status === 'waiting') {
            return match ($this->stage) {
                'registration' => 'Nomor Anda sudah masuk antrian registrasi. Anda boleh pergi dulu, kami kabari saat Anda dipanggil ke CS untuk mencatat order.',
                'photo_session' => 'Registrasi sudah selesai. Anda sedang menunggu giliran sesi foto, kami kabari saat tiba waktunya bersiap.',
                'payment' => 'Sesi foto sudah selesai. Anda sedang menunggu dipanggil ke kasir untuk pembayaran.',
                default => null,
            };
        }

        if ($this->status === 'serving') {
            return match ($this->stage) {
                'registration' => $this->confirmed_at
                    ? 'Order Anda sudah dikonfirmasi CS. Sebentar lagi Anda akan diarahkan ke sesi foto.'
                    : 'CS sedang mencatat order Anda. Sampaikan pesanan Anda langsung ke CS.',
                'photo_session' => 'Sesi foto sedang berlangsung. Setelah ini Anda akan diarahkan ke pembayaran.',
                'payment' => 'Silakan selesaikan pembayaran di kasir. Setelah itu tiket Anda selesai.',
                default => null,
            };
        }

        return null;
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
            // PENTING: kalau tiket sudah final (completed/cancelled) tapi tahap
            // ini memang gak pernah kejadian ($end null -- mis. dilewati/skip),
            // JANGAN pakai now() buat effectiveEnd -- itu bikin durasi terus
            // membengkak tiap kali halaman dibuka (mis. jadi "46d") padahal
            // tiketnya sudah lama kelar. Biarkan null -> seconds jadi null ->
            // formatDuration() tampilin '-', bukan angka ngawur.
            //
            // clone $now di sini -- $now itu SATU objek Carbon yang sama
            // dipakai bareng buat SEMUA segmen (registration, photo_wait,
            // photo_session, dst) lewat "use ($now)" di atas. Carbon itu
            // MUTABLE: kalau salah satu segmen yang pakai $now kena method
            // yang (secara gak kentara) ubah objeknya sendiri, segmen
            // BERIKUTNYA yang ikut pakai $now yang SAMA bakal ketiban nilai
            // yang udah keubah, bukan waktu sekarang yang asli lagi. Ini
            // yang bikin banyak segmen tiba-tiba nampilin "0s" padahal
            // beda jauh sama waktu aslinya (ketahuan dari HAR file asli --
            // data-start segmen "Tunggu Foto" beda 26 detik dari data-start
            // "Total", tapi "Registrasi" yang harusnya 26s malah tampil 0s).
            // clone di sini + hitung selisih pakai angka mentah
            // (getTimestamp(), bukan diffInSeconds()) di bawah, dua-duanya
            // jaga-jaga biar $now gak kesenggol sama sekali oleh segmen
            // manapun.
            $effectiveEnd = $end ?? (($start && !$isFinal) ? (clone $now) : null);

            return [
                'key' => $key,
                'label' => $label,
                'available' => (bool) $start,
                'ongoing' => $ongoing,
                'start' => $start,
                'end' => $end,
                // Selisih dihitung dari angka Unix timestamp mentah (integer),
                // BUKAN $effectiveEnd->diffInSeconds($start) -- biar gak ada
                // celah method Carbon mana pun yang bisa mutasi objeknya.
                'seconds' => $start && $effectiveEnd ? max(0, $effectiveEnd->getTimestamp() - $start->getTimestamp()) : null,
            ];
        };

        $segments = [
            $seg('registration', 'Registrasi', $this->created_at, $this->registration_completed_at),
            // PENTING: patokan akhir "Tunggu Foto"/"Tunggu Bayar" HARUS
            // served_at (bukan called_at) sebagai prioritas utama. Sebelumnya
            // segmen ini berhenti di called_at (saat pelanggan DIPANGGIL),
            // padahal segmen berikutnya ("Sesi Foto") baru mulai dari
            // served_at (saat operator klik "Mulai Melayani"). Jeda di
            // antara keduanya -- pelanggan jalan ke studio/ganti baju, dsb
            // -- gak kehitung di segmen mana pun sama sekali, jadi jumlah
            // semua segmen jadi jauh lebih kecil dari Total. served_at
            // dijadikan prioritas, called_at cuma fallback buat tiket yang
            // di-skip/dibatalkan SEBELUM sempat dilayani (supaya tetap ada
            // angka, bukan '-').
            $seg('photo_wait', 'Tunggu Foto', $this->registration_completed_at, $this->photo_served_at ?? $this->photo_called_at),
            $seg('photo_session', 'Sesi Foto', $this->photo_served_at, $this->editing_started_at ?? $this->photo_completed_at),
            $seg('editing', 'Edit Foto', $this->editing_started_at, $this->photo_completed_at),
            $seg('payment_wait', 'Tunggu Bayar', $this->photo_completed_at, $this->payment_served_at ?? $this->payment_called_at ?? $this->payment_completed_at),
            // Segmen baru -- sebelumnya rentang payment_served_at s/d tiket
            // beneran selesai (proses bayar & cetak foto) gak punya segmen
            // sama sekali, jadi ikut bikin jumlah semua segmen < Total.
            $seg('payment_processing', 'Proses Bayar', $this->payment_served_at, $this->payment_completed_at),
        ];

        $total = $seg('total', 'Total', $this->created_at, $this->completed_at);

        return ['segments' => $segments, 'total' => $total];
    }

    /**
     * Staf yang menangani tiap KELOMPOK tahap (bukan cuma yang terakhir
     * megang seperti served_by) -- buat kolom "Dilayani Oleh" di Data
     * Pelanggan, biar kelihatan CS siapa, fotografer siapa, editor siapa,
     * kasir siapa. Kolomnya diisi sekali di kejadian pertama tiap tahap
     * (lihat call() & startEditing()). Yang belum kejadian -> 'user' null.
     */
    public function stageStaff(): array
    {
        // 'segments' = key segmen di stageDurations() yang jadi "jatah waktu"
        // staf itu, biar kolom Dilayani Oleh bisa nampilin durasi per staf.
        return [
            ['label' => 'CS', 'user' => $this->registeredBy, 'segments' => ['registration']],
            ['label' => 'Fotografer', 'user' => $this->photographedBy, 'segments' => ['photo_session']],
            ['label' => 'Editor', 'user' => $this->editedBy, 'segments' => ['editing']],
            ['label' => 'Kasir', 'user' => $this->paidBy, 'segments' => ['payment_wait', 'payment_processing']],
        ];
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
    /**
     * $counter NULLABLE -- ini yang bedain 2 jalur "Siap-siap":
     * - Fotografer manggil SENDIRI dari studio-nya -> $counter diisi studio
     *   itu -> LANGSUNG terkunci ke studio itu (kayak semula/sebelum ada
     *   perubahan fairness), nama studio disebut ke pelanggan.
     * - CS yang mulai duluan (karena fotografer belum sempat manggil) ->
     *   $counter dikosongkan (null) -> GENERIK, gak dikunci ke studio
     *   manapun (studio mana pun boleh nyomot pas kosong -- lihat
     *   OperatorController::callPreparingTicket()), nama studio TIDAK
     *   disebut karena emang belum ditentukan.
     */
    public function callToPrepare(?Counter $counter, User $operator): void
    {
        $this->update([
            'status' => 'preparing',
            'counter_id' => $counter?->id,
            'served_by' => $operator->id,
            'called_at' => now(),
            // Siap-siap baru = CS harus tandai "Ready" lagi (mis. pelanggan
            // sempat dibatalkan Siap-siap-nya lalu dipanggil ulang dari nol).
            'ready_at' => null,
            'ready_by' => null,
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();

        $body = $counter
            ? "Sebentar lagi giliran Anda di {$counter->publicLabel()}. Silakan bersiap-siap (ganti baju, dsb)."
            : 'Sebentar lagi giliran Anda. Silakan bersiap-siap di ruang ganti (ganti baju, dsb).';

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '👋 Bersiap-siap!',
            $body,
            ['url' => route('tickets.show', $this)]
        );
    }

    /**
     * "Panggil Ulang" versi CS/Kasir buat tiket yang MASIH "Siap-siap"
     * (belum dinaikkan fotografer jadi "Dipanggil"/calling). Beda dari
     * callToPrepare(): status & counter_id TIDAK diubah sama sekali --
     * cuma nge-nudge ulang notifikasi/TTS "bersiap-siap" ke pelanggan,
     * buat kasus pelanggan kelihatan belum sadar/belum ke ruang ganti.
     * `called_at` ikut di-refresh biar "sejak X menit lalu" di dashboard
     * juga reset, konsisten sama Panggil Ulang versi calling (call()).
     */
    public function recallPrepare(User $operator): void
    {
        $this->update([
            'called_at' => now(),
            'prepare_recall_count' => $this->prepare_recall_count + 1,
            'prepare_recalled_by' => $operator->id,
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();

        $body = $this->counter
            ? "Ayo, sudah ditunggu di {$this->counter->publicLabel()}. Silakan segera bersiap-siap ya."
            : 'Ayo, sudah ditunggu. Silakan segera bersiap-siap di ruang ganti ya.';

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '👋 Bersiap-siap!',
            $body,
            ['url' => route('tickets.show', $this)]
        );
    }

    // Nomor loket (CS 1, CS 2, Kasir 1, Kasir 2, dst) itu penanda INTERNAL
    // buat staf milih fisik loket mana -- pelanggan gak perlu tau nomornya,
    // cukup tau kategorinya ("CS"/"Kasir") aja lewat notifikasi. Studio
    // SENGAJA TIDAK disederhanakan (beda dari CS/Kasir) karena nomor studio
    // itu lokasi fisik yang memang harus diketahui pelanggan buat nentuin
    // ruang mana yang dituju. Dipakai di teks notifikasi push customer-
    // facing; TIDAK dipakai buat tampilan internal (dashboard operator).
    private function customerFacingCounterName(?Counter $counter): ?string
    {
        if (!$counter || !$counter->name) {
            return $counter?->name;
        }

        // display_label yang diisi admin SELALU menang -- itu memang
        // mekanisme resmi buat nama yang dilihat pelanggan (Counter::publicLabel()).
        if ($counter->display_label) {
            return $counter->display_label;
        }

        if ($this->stage === 'photo_session') {
            return $counter->name;
        }

        // Nama loket beneran bisa "CS 3", "Kasir 2", TAPI JUGA bentuk
        // gabungan kayak "CS & Kasir 3" (satu loket ngerangkep dua fungsi,
        // nomor di belakang cuma pembeda fisik ke berapa) -- makanya di sini
        // cuma buang angka di paling belakang, BUKAN nyederhanain jadi
        // "CS" doang, biar "CS & Kasir 3" jadi "CS & Kasir" (bukan "CS").
        // Kalau sisa teksnya bukan cuma kombinasi kata CS/Kasir/&/dan (mis.
        // "Kasir Utama", "CS Depan" -- gak ada angka buat dibuang, atau nama
        // custom lain), nama aslinya dipakai apa adanya.
        $stripped = trim(preg_replace('/\s*\d+\s*$/', '', $counter->name));
        if ($stripped !== $counter->name && preg_match('/^(?:CS|Kasir|&|dan|\s)+$/i', $stripped)) {
            return $stripped;
        }

        return $counter->name;
    }

    public function call(Counter $counter, User $operator): void
    {
        // Dipakai buat bedain "panggilan PERTAMA kali" vs "Panggil Ulang" --
        // kalau tiket ini SEBELUM update ini statusnya udah 'calling', berarti
        // ini panggilan ulang (recall), bukan yang pertama. Dikirim ke
        // frontend (lihat TicketCalled::broadcastWith()) buat nentuin boleh
        // bunyi suara panggilan apa nggak -- lihat diskusi soal suara
        // panggilan pertama sengaja DIHILANGKAN (notifnya tetap jalan),
        // sementara Panggil Ulang tetap ada suara + notif seperti biasa.
        $isRecall = $this->status === 'calling';

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
        // PENTING: cuma diisi SEKALI di kejadian pertama (guard !$this->...),
        // JANGAN ditimpa tiap kali dipanggil -- sebelumnya baris ini jalan
        // tanpa syarat, jadi tiap kali operator klik "Panggil Ulang" buat
        // tiket yang sama, timestamp-nya ikut maju ke waktu sekarang dan
        // segmen "Tunggu Foto"/"Tunggu Bayar" di widget WAKTU keliatan
        // "reset ke 0 terus ngitung lagi" padahal cuma lagi dipanggil ulang.
        if ($this->stage === 'photo_session' && !$this->photo_called_at) {
            $updates['photo_called_at'] = now();
        } elseif ($this->stage === 'payment' && !$this->payment_called_at) {
            $updates['payment_called_at'] = now();
        }

        // Salinan staf per-tahap buat laporan evaluasi kerja -- served_by
        // di atas ketimpa tiap pindah tahap, jadi siapa yang nangani
        // registrasi/sesi-foto/bayar dicatat terpisah di sini. Sama-sama
        // guard "cuma sekali", biar staf yang megang tiket ini pertama
        // kali di tahap itu yang tercatat -- bukan siapa yang paling akhir
        // klik "Panggil Ulang".
        if ($this->stage === 'registration' && !$this->registered_by) {
            $updates['registered_by'] = $operator->id;
        } elseif ($this->stage === 'photo_session' && !$this->photographed_by) {
            $updates['photographed_by'] = $operator->id;
        } elseif ($this->stage === 'payment' && !$this->paid_by) {
            $updates['paid_by'] = $operator->id;
        }

        $this->update($updates);

        broadcast(new TicketCalled($this, $isRecall))->toOthers();
        broadcast(new TicketStatusChanged($this))->toOthers();

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '🔔 Giliran Anda!',
            "Tiket {$this->ticket_number} dipanggil ke {$this->customerFacingCounterName($counter)}. Silakan menuju lokasi sekarang.",
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
        // Sama juga: cuma diisi sekali (guard), jangan ditimpa tiap kali
        // "Mulai Melayani" dipencet ulang -- kalau tidak, segmen "Sesi Foto"
        // di widget WAKTU restart dari 0 tiap kali stage-nya dimulai ulang.
        if ($this->stage === 'photo_session' && !$this->photo_served_at) {
            $updates['photo_served_at'] = now();
        } elseif ($this->stage === 'payment' && !$this->payment_served_at) {
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

        // Sebelumnya startServing() TIDAK pernah kirim push notification
        // sama sekali (beda dari callToPrepare/call/startEditing di atas) --
        // padahal pelanggan yang lagi ninggalin tab/HP-nya jadi gak tau sesi
        // foto/pembayarannya udah mulai. Ditambahkan supaya konsisten dengan
        // status lain yang butuh perhatian pelanggan.
        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            $this->stage === 'payment' ? '💳 Sesi Pembayaran Dimulai' : '📸 Sesi Foto Dimulai',
            $this->stage === 'payment'
                ? 'Silakan lanjutkan pembayaran di kasir.'
                : 'Sesi foto Anda sedang berlangsung.',
            ['url' => route('tickets.show', $this)]
        );
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
    public function startEditing(?int $editedByUserId = null): void
    {
        $updates = ['status' => 'editing'];

        if (!$this->editing_started_at) {
            $updates['editing_started_at'] = now();
        }

        // Sekali diisi, tidak ditimpa -- kalau tiket ini nantinya di-ambil
        // alih atau dilanjutkan orang lain (lihat OperatorController::
        // takeoverTicket()), `edited_by` tetap nyimpen siapa yang PERTAMA
        // mulai ngedit, buat laporan evaluasi kerja per staf.
        if (!$this->edited_by) {
            $updates['edited_by'] = $editedByUserId ?? $this->served_by;
        }

        // Studio yang tanggung jawab nyelesain edit ini -- lihat migrasi
        // 2026_08_18_000001 kenapa kolom ini terpisah dari `counter_id`.
        if (!$this->editing_counter_id) {
            $updates['editing_counter_id'] = $this->counter_id;
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
     *
     * Catatan "Order" ikut TERKUNCI di titik ini juga (notes_locked_at) --
     * sama seperti startServing() mengunci pas sesi foto dimulai. Alasannya
     * sama: begitu pesanan "fix" & dikonfirmasi ke pelanggan, jangan sampai
     * ke-klik/ke-edit gak sengaja. Perlu "Buka Kunci buat Edit" dulu kalau
     * memang mau diubah.
     */
    public function confirmOrder(): void
    {
        $updates = ['confirmed_at' => now()];

        if (!$this->notes_locked_at) {
            $updates['notes_locked_at'] = now();
        }

        $this->update($updates);

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

            // Promosi otomatis "preparing -> calling" DIHAPUS atas permintaan --
            // sekarang semua keputusan manggil sepenuhnya manual, fotografer
            // yang mutusin kapan mau manggil tiket yang sudah "Siap-siap" (lihat
            // tombol "Panggil Sekarang" di card Antrian Siap-siap /
            // OperatorController::callPreparingTicket()). Sebelumnya promosi ini
            // nempel di sini (baru jalan pas advanceStage() dipanggil, yaitu
            // SETELAH proses edit foto kelar) -- itu ternyata nunda tiket
            // "Siap-siap" jadi "Dipanggil" berjam-jam, padahal studionya sendiri
            // sudah kosong dari saat finishPhotoSession() dipanggil.

            return;
        }

        // stage === 'payment' -> selesai total (bayar + cetak foto)
        $serviceTime = $this->served_at ? $this->served_at->diffInSeconds(now()) : null;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'payment_completed_at' => now(),
            'service_time' => $serviceTime,
        ]);

        broadcast(new TicketStatusChanged($this))->toOthers();
    }

    /**
     * "Lanjut ke Kasir — Edit Menyusul" — dipakai fotografer kalau editnya
     * bakal masih lama (mis. baru kelar besok) tapi pelanggan udah mau
     * pulang. Tiket TETAP lanjut ke antrian pembayaran normal (lewat
     * advanceStage() yang sama), tapi `edit_completed_at` sengaja
     * dibiarkan kosong -- itu yang bikin tiket ini tetap nongol di panel
     * "Sudah Dibayar, Edit Belum Selesai" studio yang tercatat di
     * `editing_counter_id`, walau tiketnya sendiri udah pindah ke stage
     * payment/completed. Boleh dipanggil baik dari status `ready_for_edit`
     * (belum sempat diedit sama sekali) maupun `editing` (baru dikerjain
     * sebagian).
     */
    public function skipToPaymentPendingEdit(int $userId, ?string $waitPreference = null): void
    {
        $updates = [];

        // 'wait' = pelanggan nunggu hasil edit di tempat, 'leave' = pulang
        // dulu (dikabari lewat notifikasi). Nilai lain diabaikan.
        if (in_array($waitPreference, ['wait', 'leave'], true)) {
            $updates['edit_wait_preference'] = $waitPreference;
        }

        if (!$this->editing_counter_id) {
            $updates['editing_counter_id'] = $this->counter_id;
        }

        if (!$this->edited_by) {
            $updates['edited_by'] = $userId;
        }

        // Penanda TEGAS "tiket ini lewat skip-flow" -- lihat catatan lengkap
        // di isEditPending()/isReadyForPickup() & migrasi 2026_08_27_000001
        // kenapa ini gak bisa cukup dari editing_counter_id doang.
        if (!$this->payment_skipped_pending_edit_at) {
            $updates['payment_skipped_pending_edit_at'] = now();
        }

        if (!empty($updates)) {
            $this->update($updates);
        }

        // advanceStage() yang mindahin photo_session -> payment (reset
        // counter_id/served_by, dsb) -- editing_counter_id & edit_completed_at
        // TIDAK disentuh method itu, jadi aman dipakai apa adanya di sini.
        $this->advanceStage();
    }

    /**
     * Fotografer klik "Edit Selesai" untuk tiket yang tadinya dilewatin
     * lewat skipToPaymentPendingEdit() -- bisa dipanggil kapan saja setelah
     * itu, gak peduli tiketnya udah di kasir atau udah `completed`
     * sekalipun. Sekalian kasih tau pelanggan lewat push notification,
     * karena kemungkinan besar dia udah pulang duluan.
     */
    public function markEditCompleted(): void
    {
        $this->update(['edit_completed_at' => now()]);

        broadcast(new TicketStatusChanged($this))->toOthers();

        app(\App\Services\WebPushService::class)->sendToTicket(
            $this,
            '🎉 Hasil Edit Foto Siap',
            $this->edit_wait_preference === 'wait'
                ? 'Hasil edit foto Anda (' . $this->ticket_number . ') sudah selesai. Silakan ambil sekarang, kami tunggu di tempat.'
                : 'Hasil edit foto Anda (' . $this->ticket_number . ') sudah selesai dan siap diambil.',
            ['url' => route('tickets.show', $this)]
        );
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

    /**
     * Tiket boleh dibatalkan di SEMUA tahap (registrasi, sesi foto, pembayaran)
     * dan status apa pun selama sesinya BELUM selesai -- yang sudah
     * 'completed' atau 'cancelled' sudah final dan tidak bisa dibatalkan lagi.
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled'], true);
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
