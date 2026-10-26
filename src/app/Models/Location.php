<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'address',
        'latitude',
        'longitude',
        'gps_radius_meters',
        'order_line_suggestions',
        'is_active',
        'average_service_time',
        'photo_select_minutes',
        'open_time',
        'close_time',
        'booking_mode',
        'service_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'average_service_time' => 'integer',
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gps_radius_meters' => 'integer',
        ];
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }

    public function offersWalkInTicket(): bool
    {
        return $this->service_type !== 'booking';
    }

    public function offersBooking(): bool
    {
        return $this->service_type !== 'ticket';
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function bookingSlots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activeCounters(): HasMany
    {
        return $this->counters()->where('is_active', true);
    }

    public function waitingTickets(): HasMany
    {
        return $this->tickets()->where('status', 'waiting')->orderByRaw('COALESCE(last_skipped_at, created_at)');
    }

    public function todayTickets(): HasMany
    {
        return $this->tickets()->whereDate('created_at', today());
    }

    public function generateTicketNumber(): string
    {
        // Dulu: jumlah tiket hari ini + 1. Masalahnya kalau ada tiket yang DIHAPUS
        // permanen di tengah antrian, jumlahnya turun dan nomor berikutnya bisa
        // KEMBAR dengan tiket yang masih ada (mis. PP-005 dobel). Sekarang dipakai
        // nilai yang lebih besar antara jumlah tiket dan nomor urut tertinggi hari ini.
        $todayNumbers = $this->todayTickets()
            ->lockForUpdate()
            ->pluck('ticket_number');
        $count = $todayNumbers->count();
        $maxSeq = $todayNumbers
            ->map(fn ($n) => (int) preg_replace('/^.*?(\d+)$/', '$1', (string) $n))
            ->max() ?? 0;
        return sprintf('%s-%03d', $this->code, max($count, $maxSeq) + 1);
    }

    public function getEstimatedWaitTime(int $position): int
    {
        $activeCounters = $this->activeCounters()->count();
        if ($activeCounters === 0) {
            return $position * $this->average_service_time;
        }

        return (int) ceil(($position / $activeCounters) * $this->average_service_time);
    }

    /**
     * true kalau admin sudah mengatur koordinat lokasi ini, artinya
     * pengambilan tiket walk-in harus diverifikasi lewat GPS pelanggan.
     */
    /**
     * Daftar template baris order (lihat migration
     * add_order_line_suggestions_to_locations_table) sebagai array bersih —
     * baris kosong dibuang, dipakai buat tombol cepat di kolom Order.
     */
    public function orderLineSuggestions(): array
    {
        if (!$this->order_line_suggestions) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $this->order_line_suggestions))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    public function hasGpsRestriction(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Jarak dari titik (lat, lng) yang diberikan ke lokasi ini, dalam meter.
     * Pakai rumus Haversine (cukup akurat untuk radius kecil seperti ini).
     */
    public function distanceInMetersFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meter

        $latFrom = deg2rad((float) $this->latitude);
        $lngFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * asin(min(1, sqrt($a)));

        return $earthRadius * $c;
    }

    /**
     * true kalau titik (lat, lng) yang diberikan ada di dalam radius yang
     * diizinkan untuk lokasi ini. Kalau lokasi belum dikonfigurasi GPS-nya,
     * selalu true (tidak dibatasi).
     */
    public function isWithinGpsRadius(?float $lat, ?float $lng): bool
    {
        if (!$this->hasGpsRestriction()) {
            return true;
        }

        if ($lat === null || $lng === null) {
            return false;
        }

        return $this->distanceInMetersFrom($lat, $lng) <= $this->gps_radius_meters;
    }

    public function isOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->format('H:i');
        return $now >= $this->open_time->format('H:i') && $now <= $this->close_time->format('H:i');
    }

    /**
     * true  = pelanggan bebas isi jam berapa saja (dalam jam operasional).
     * false = pelanggan pilih dari jadwal slot yang sudah disiapkan admin.
     */
    public function isFreeBookingMode(): bool
    {
        return $this->booking_mode === 'free';
    }
}