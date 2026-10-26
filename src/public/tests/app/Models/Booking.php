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
        'customer_name',
        'customer_phone',
        'customer_email',
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
        // Sertakan tanggal (Ymd) supaya kode unik secara global — kolom
        // booking_code punya unique constraint, tapi nomor urut di-reset per
        // hari, jadi tanpa tanggal akan tabrakan lintas hari (mis. booking
        // hari ke-2 kebetulan "BK-BK-001" sama dengan punya hari sebelumnya).
        $date = now()->format('Ymd');
        $prefix = "BK-{$location->code}-{$date}-";

        $last = self::where('location_id', $location->id)
            ->where('booking_code', 'like', $prefix . '%')
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
                null,
                $locked->service_category_id
            );

            $locked->update([
                'status' => 'checked_in',
                'ticket_id' => $ticket->id,
            ]);

            return $ticket;
        });
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
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
