<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory;

    /**
     * Pelanggan cuma boleh batalin reservasi sendiri kalau jadwalnya masih
     * lebih dari sekian menit lagi. Di bawah itu, harus hubungi CS/Admin
     * biar bisa diubah jadwalnya (bukan bikin reservasi baru).
     *
     * 120 menit (2 jam) dipilih sebagai nilai wajar: cukup longgar buat
     * pelanggan yang beneran mendadak ada urusan, tapi tetap kasih waktu ke
     * studio buat isi ulang slot itu ke pelanggan lain kalau dibatalkan.
     */
    public const CUSTOMER_CANCEL_CUTOFF_MINUTES = 120;

    protected $fillable = [
        'location_id',
        'service_category_id',
        'package_name',
        'people_count',
        'costume_changes',
        'extras',
        'dp_amount',
        'dp_forfeited',
        'order_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'notes',
        'booking_date',
        'booking_time',
        'status',
        'booking_code',
        'ticket_id',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_time' => 'datetime:H:i',
            'people_count' => 'integer',
            'costume_changes' => 'integer',
            'extras' => 'array',
            'dp_amount' => 'integer',
            'dp_forfeited' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public static function generateBookingCode(Location $location): string
    {
        // Sengaja BUKAN pakai kode lokasi lagi di kode reservasi (dulu
        // "BK-{kode lokasi}-{tanggal}-{urut}", jadi dobel "BK-BK-..." kalau
        // kode lokasinya kebetulan juga "BK" -- bikin bingung). Sekarang
        // cuma "BK-{tanggal}-{urut}".
        //
        // KONSEKUENSINYA: karena kode lokasi udah gak ada di kodenya,
        // urutannya HARUS dihitung GLOBAL lintas SEMUA lokasi yang buka
        // reservasi (bukan per-lokasi lagi kayak sebelumnya) -- soalnya
        // kolom booking_code unique secara GLOBAL di database (bukan
        // unique per-lokasi). Kalau urutannya tetap dihitung per-lokasi
        // tapi kodenya udah gak bawa identitas lokasi, 2 lokasi berbeda
        // bisa sama-sama coba generate kode PERSIS SAMA di hari yang sama
        // (mis. reservasi pertama hari itu di lokasi A dan di lokasi B
        // sama-sama jadi "BK-20260813-001") -- lockForUpdate() di bawah
        // makanya juga ngunci lintas lokasi, biar generate-nya tetap aman
        // dari race condition walau dari 2 lokasi sekaligus.
        $date = now()->format('Ymd');
        $prefix = "BK-{$date}-";

        $last = self::where('booking_code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('booking_code')
            ->first();

        $seq = $last ? ((int) substr($last->booking_code, strlen($prefix)) + 1) : 1;

        return $prefix . sprintf('%03d', $seq);
    }

    /**
     * Gabungan tanggal + jam reservasi jadi satu Carbon instance, buat
     * dibandingkan dengan waktu sekarang (cutoff pembatalan, dsb).
     */
    public function appointmentAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(
            $this->booking_date->format('Y-m-d') . ' ' . $this->booking_time->format('H:i:s')
        );
    }

    /**
     * true = pelanggan masih boleh batalin sendiri lewat halaman "Cek
     * Reservasi". false = sudah lewat cutoff, harus hubungi CS/Admin.
     */
    public function canBeCancelledByCustomer(): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        $minutesUntilAppointment = ($this->appointmentAt()->timestamp - now()->timestamp) / 60;

        return $minutesUntilAppointment >= self::CUSTOMER_CANCEL_CUTOFF_MINUTES;
    }

    public function checkIn(): ?Ticket
    {
        // Kunci baris booking supaya dua check-in yang masuk berbarengan
        // (ples pelanggan dari HP, ples CS klik "Panggil" di dashboard)
        // tidak sama-sama lolos dan malah bikin dua tiket dari satu reservasi.
        return DB::transaction(function () {
            $locked = static::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            if (!$locked || !in_array($locked->status, ['pending', 'confirmed'])) {
                return null;
            }

            $ticket = Ticket::createTicket(
                $locked->location,
                $locked->customer_name,
                $locked->customer_phone,
                $locked->customer_email,
                $locked->service_category_id,
                $locked->ticketOrderText()
            );

            // Reservasi SUDAH punya nama, kode order & catatan, jadi tiketnya tidak
            // perlu lewat CS/Registrasi: order langsung dianggap terkonfirmasi (+ terkunci,
            // sama seperti "Konfirmasi Pesanan" di CS) dan tiket masuk antrian Studio.
            // Dari sini alurnya sama seperti tiket biasa: Siap-siap -> Studio -> dst.
            $ticket->update([
                'stage' => 'photo_session',
                'status' => 'waiting',
                'confirmed_at' => now(),
                'notes_locked_at' => now(),
                'registration_completed_at' => now(),
            ]);

            $locked->update([
                'status' => 'checked_in',
                'ticket_id' => $ticket->id,
            ]);

            return $ticket;
        });
    }

    /**
     * Batalkan reservasi. Sesuai ketentuan booking ("cancel booking DP otomatis
     * hangus"), kalau sudah ada DP maka DP-nya ditandai hangus.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'dp_forfeited' => $this->dp_amount > 0,
        ]);
    }

    /** "Rp 50.000" */
    public static function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /** Label DP buat tampilan: "DP Rp 50.000", "DP Rp 50.000 (hangus)", atau "Belum DP". */
    public function dpLabel(): string
    {
        if ($this->dp_amount <= 0) {
            return 'Belum DP';
        }

        return 'DP ' . self::formatRupiah($this->dp_amount) . ($this->dp_forfeited ? ' (hangus)' : '');
    }

    /**
     * Ringkasan detail reservasi dalam satu baris, mis.
     * "PREMIUM A · 6 orang · +2 kostum · DP Rp 50.000". Bagian yang kosong
     * dilewati. Dipakai di daftar reservasi dan di Order tiket.
     *
     * @return list<string>
     */
    public function detailParts(): array
    {
        $parts = [];
        if ($this->package_name) {
            $parts[] = $this->package_name;
        }
        if ($this->people_count) {
            $parts[] = $this->people_count . ' orang';
        }
        if ($this->costume_changes > 0) {
            $parts[] = '+' . $this->costume_changes . ' kostum';
        }
        if (!empty($this->extras)) {
            $parts[] = 'Tambahan: ' . implode(', ', $this->extras);
        }
        if ($this->dp_amount > 0) {
            $parts[] = $this->dpLabel();
        }

        return $parts;
    }

    /**
     * Isi kolom Order di tiket saat reservasi check-in: baris pertama = detail
     * reservasi (supaya fotografer & kasir langsung tahu paket, jumlah orang,
     * kostum, dan DP yang sudah masuk), sesudahnya catatan order apa adanya.
     */
    public function ticketOrderText(): ?string
    {
        $parts = $this->detailParts();
        if ($this->order_code) {
            array_unshift($parts, 'KO ' . $this->order_code);
        }

        $lines = [];
        if ($parts) {
            $lines[] = 'Reservasi: ' . implode(' · ', $parts);
        }
        if (filled($this->notes)) {
            $lines[] = trim($this->notes);
        }

        return $lines ? implode("\n", $lines) : null;
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Ubah tanggal/jam reservasi tanpa bikin record baru — dipakai CS/Admin
     * saat pelanggan minta pindah jadwal, daripada pelanggan disuruh
     * reservasi ulang dari nol (yang bikin data ganda & bingung).
     */
    public function reschedule(string $date, string $time): void
    {
        $this->update([
            'booking_date' => $date,
            'booking_time' => $time,
            'status' => 'confirmed',
        ]);
    }
}
