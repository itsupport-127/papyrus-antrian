<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    protected $fillable = [
        'location_id',
        'day_of_week',
        'start_time',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'is_active' => 'boolean',
        ];
    }

    public const DAY_LABELS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function dayLabel(): string
    {
        return self::DAY_LABELS[$this->day_of_week] ?? '-';
    }

    /**
     * Mencari "bucket" slot yang menaungi sebuah jam bebas (mis. jam yang
     * diketik pelanggan di form reservasi, bukan harus persis start_time
     * sebuah slot). Kapasitas berlaku untuk seluruh jendela waktu dari
     * start_time slot ini sampai start_time slot berikutnya (atau jam tutup
     * lokasi kalau ini slot terakhir di hari itu).
     *
     * @return array{slot: BookingSlot, window_start: string, window_end: string}|null
     */
    public static function resolveBucket(Location $location, \Carbon\Carbon $date, string $time): ?array
    {
        $dayOfWeek = (int) $date->dayOfWeek;

        $slots = self::where('location_id', $location->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $covering = null;
        foreach ($slots as $index => $slot) {
            $start = $slot->start_time->format('H:i');
            if ($time < $start) {
                break;
            }
            $covering = ['slot' => $slot, 'index' => $index];
        }

        if (!$covering) {
            return null; // Jam yang diketik lebih pagi dari slot pertama hari itu.
        }

        $windowStart = $covering['slot']->start_time->format('H:i');
        $nextSlot = $slots->get($covering['index'] + 1);
        $windowEnd = $nextSlot ? $nextSlot->start_time->format('H:i') : $location->close_time->format('H:i');

        if ($time >= $windowEnd) {
            return null; // Lewat dari jam tutup / cakupan slot terakhir.
        }

        return ['slot' => $covering['slot'], 'window_start' => $windowStart, 'window_end' => $windowEnd];
    }

    /**
     * Jendela waktu bucket milik slot INI sendiri (dipakai admin untuk
     * menghitung berapa reservasi sudah masuk ke slot tsb hari ini).
     *
     * @return array{window_start: string, window_end: string}
     */
    public function ownWindow(Location $location): array
    {
        $windowStart = $this->start_time->format('H:i');

        $nextSlot = self::where('location_id', $this->location_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('is_active', true)
            ->where('start_time', '>', $this->start_time->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        $windowEnd = $nextSlot ? $nextSlot->start_time->format('H:i') : $location->close_time->format('H:i');

        return ['window_start' => $windowStart, 'window_end' => $windowEnd];
    }

    /**
     * Menghitung berapa reservasi (belum dibatalkan) sudah ada di jendela
     * waktu tertentu pada tanggal tersebut.
     */
    public static function countBookingsInWindow(Location $location, \Carbon\Carbon $date, string $windowStart, string $windowEnd): int
    {
        return Booking::where('location_id', $location->id)
            ->whereDate('booking_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(function (Booking $b) use ($windowStart, $windowEnd) {
                $t = $b->booking_time->format('H:i');
                return $t >= $windowStart && $t < $windowEnd;
            })
            ->count();
    }
}
