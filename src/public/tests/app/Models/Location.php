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
        'is_active',
        'average_service_time',
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
        $todayCount = $this->todayTickets()
            ->lockForUpdate()
            ->count() + 1;
        return sprintf('%s-%03d', $this->code, $todayCount);
    }

    public function getEstimatedWaitTime(int $position): int
    {
        $activeCounters = $this->activeCounters()->count();
        if ($activeCounters === 0) {
            return $position * $this->average_service_time;
        }

        return (int) ceil(($position / $activeCounters) * $this->average_service_time);
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