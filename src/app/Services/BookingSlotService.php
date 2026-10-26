<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * Logika manajemen jadwal reservasi (BookingSlot) yang dipakai bersama
 * oleh AdminController dan OperatorController — supaya keduanya tidak
 * menduplikasi method yang identik. Perbedaan otorisasi (admin pakai
 * {location} dari route, operator pakai lokasi dari user yang login)
 * tetap diurus masing-masing di controller; service ini murni soal
 * data/logika CRUD slot.
 */
class BookingSlotService
{
    /**
     * Daftar slot (dikelompokkan per hari) + jumlah reservasi yang sudah
     * masuk hari ini per jam — untuk halaman manajemen jadwal.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    public function getSlotsWithBookedCounts(Location $location): array
    {
        $slots = $location->bookingSlots()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $bookedTodayByTime = Booking::where('location_id', $location->id)
            ->whereDate('booking_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy(fn ($b) => $b->booking_time->format('H:i'))
            ->map->count();

        return [$slots, $bookedTodayByTime];
    }

    public function updateMode(Location $location, string $mode): void
    {
        $location->update(['booking_mode' => $mode]);
    }

    /**
     * Aksi massal: aktifkan / nonaktifkan / hapus banyak slot sekaligus.
     * Selalu dibatasi ke slot milik $location (jaga-jaga ada id nyasar).
     */
    public function bulkAction(Location $location, string $action, array $ids): int
    {
        $query = BookingSlot::where('location_id', $location->id)
            ->whereIn('id', $ids);

        return match ($action) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => $query->delete(),
        };
    }

    /**
     * Tambah slot satu per hari (jam tetap, bisa beberapa hari sekaligus).
     *
     * @return array{0: int, 1: int}  [jumlah dibuat, jumlah dilewati]
     */
    public function store(Location $location, array $validated): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($validated['days'] as $day) {
            $exists = $location->bookingSlots()
                ->where('day_of_week', $day)
                ->where('start_time', $validated['start_time'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $location->bookingSlots()->create([
                'day_of_week' => $day,
                'start_time' => $validated['start_time'],
                'capacity' => $validated['capacity'],
                'is_active' => true,
            ]);
            $created++;
        }

        return [$created, $skipped];
    }

    /**
     * Generate slot otomatis (interval tetap dari jam buka–tutup).
     *
     * @return array{0: int, 1: int}  [jumlah dibuat, jumlah dilewati]
     */
    public function generate(Location $location, array $validated): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($validated['days'] as $day) {
            $cursor = \Carbon\Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = \Carbon\Carbon::createFromFormat('H:i', $validated['end_time']);

            while ($cursor->lt($end)) {
                $time = $cursor->format('H:i');

                $exists = $location->bookingSlots()
                    ->where('day_of_week', $day)
                    ->where('start_time', $time)
                    ->exists();

                if ($exists) {
                    $skipped++;
                } else {
                    $location->bookingSlots()->create([
                        'day_of_week' => $day,
                        'start_time' => $time,
                        'capacity' => $validated['capacity'],
                        'is_active' => true,
                    ]);
                    $created++;
                }

                $cursor->addMinutes((int) $validated['interval_minutes']);
            }
        }

        return [$created, $skipped];
    }

    public function update(BookingSlot $bookingSlot, int $capacity, bool $isActive): void
    {
        $bookingSlot->update([
            'capacity' => $capacity,
            'is_active' => $isActive,
        ]);
    }

    public function toggle(BookingSlot $bookingSlot): void
    {
        $bookingSlot->update(['is_active' => !$bookingSlot->is_active]);
    }

    public function delete(BookingSlot $bookingSlot): void
    {
        $bookingSlot->delete();
    }
}
