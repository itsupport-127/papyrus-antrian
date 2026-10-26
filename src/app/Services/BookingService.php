<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * Logika pembuatan reservasi (Booking) yang dipakai bersama oleh
 * BookingController (reservasi mandiri pelanggan) dan OperatorController
 * (reservasi manual oleh CS & Kasir).
 *
 * Menangani dua mode lokasi:
 *  - "free"  : pelanggan bebas isi jam, cukup dalam jam operasional.
 *  - "slots" : pelanggan pilih dari jadwal, kapasitas per jam dicek pakai
 *               lockForUpdate() supaya tidak ada double-booking melebihi kapasitas.
 */
class BookingService
{
    /**
     * @param  array{location_id?: int, service_category_id?: ?int, customer_name: string, customer_phone: string, customer_email?: ?string, booking_date: string, booking_time: string}  $data
     */
    /**
     * Reservasi MANUAL dari CS/operator (form "Tambah Reservasi"): tidak ada
     * tanggal/jam -- otomatis hari ini & jam saat ini, dan TIDAK melewati
     * pengecekan slot/kapasitas (pelanggannya sudah di studio / sudah pasti).
     */
    public function createManual(Location $location, array $data): Booking
    {
        return DB::transaction(function () use ($location, $data) {
            return Booking::create([
                'location_id' => $location->id,
                'service_category_id' => $data['service_category_id'] ?? null,
                'package_name' => $data['package_name'] ?? null,
                'people_count' => $data['people_count'] ?? null,
                'extras' => $data['extras'] ?? [],
                'order_code' => $data['order_code'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booking_date' => now()->toDateString(),
                'booking_time' => now()->format('H:i'),
                'booking_code' => Booking::generateBookingCode($location),
                'status' => 'confirmed',
            ]);
        });
    }

    public function create(Location $location, array $data): Booking
    {
        if ($location->isFreeBookingMode()) {
            return $this->createFree($location, $data);
        }

        return $this->createSlot($location, $data);
    }

    /**
     * Mode "free": satu-satunya syarat adalah jam masih di dalam jam
     * operasional lokasi (dan belum lewat kalau tanggalnya hari ini).
     * Tidak ada pengecekan slot/kapasitas seperti mode "slots".
     */
    private function createFree(Location $location, array $data): Booking
    {
        $openTime = $location->open_time->format('H:i');
        $closeTime = $location->close_time->format('H:i');

        if ($data['booking_time'] < $openTime || $data['booking_time'] > $closeTime) {
            throw new \App\Exceptions\BookingCreationException(
                "Jam kunjungan harus di antara {$openTime} - {$closeTime}."
            );
        }

        if (\Carbon\Carbon::parse($data['booking_date'])->isToday()
            && \Carbon\Carbon::createFromFormat('H:i', $data['booking_time'])->lt(now())
        ) {
            throw new \App\Exceptions\BookingCreationException(
                'Jam yang dipilih sudah lewat. Silakan pilih jam lain.'
            );
        }

        // Bungkus cek + insert dalam satu transaction supaya lockForUpdate()
        // benar-benar mencegah dua reservasi yang masuk berbarengan di jam
        // persis sama sama-sama lolos (race condition).
        return DB::transaction(function () use ($location, $data) {
            $alreadyTaken = Booking::where('location_id', $location->id)
                ->whereDate('booking_date', $data['booking_date'])
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->lockForUpdate()
                ->get()
                ->contains(fn ($b) => (is_string($b->booking_time) ? substr($b->booking_time, 0, 5) : $b->booking_time->format('H:i')) === $data['booking_time']);

            if ($alreadyTaken) {
                throw new \App\Exceptions\BookingCreationException(
                    'Jam ini sudah dipakai reservasi lain. Silakan pilih jam yang berbeda.'
                );
            }

            return Booking::create([
                'location_id' => $location->id,
                'service_category_id' => $data['service_category_id'] ?? null,
                'package_name' => $data['package_name'] ?? null,
                'people_count' => $data['people_count'] ?? null,
                'costume_changes' => $data['costume_changes'] ?? 0,
                'dp_amount' => $data['dp_amount'] ?? 0,
                'order_code' => $data['order_code'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'booking_code' => Booking::generateBookingCode($location),
                'status' => 'confirmed',
            ]);
        });
    }

    /**
     * Mode "slots": kunci baris slot supaya dua reservasi yang masuk
     * berbarengan (mepet detik yang sama) tidak sama-sama lolos melebihi
     * kapasitas.
     */
    private function createSlot(Location $location, array $data): Booking
    {
        $date = \Carbon\Carbon::parse($data['booking_date']);
        $dayOfWeek = (int) $date->dayOfWeek;

        return DB::transaction(function () use ($data, $location, $dayOfWeek, $date) {
            $slot = BookingSlot::where('location_id', $location->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $data['booking_time'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$slot) {
                throw new \App\Exceptions\BookingCreationException(
                    'Jam yang dipilih tidak tersedia untuk reservasi. Silakan pilih jam lain.'
                );
            }

            if ($date->isToday() && \Carbon\Carbon::createFromFormat('H:i', $data['booking_time'])->lt(now())) {
                throw new \App\Exceptions\BookingCreationException(
                    'Jam yang dipilih sudah lewat. Silakan pilih jam lain.'
                );
            }

            $bookedCount = Booking::where('location_id', $location->id)
                ->whereDate('booking_date', $date->toDateString())
                ->where('booking_time', $data['booking_time'])
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->count();

            if ($bookedCount >= $slot->capacity) {
                throw new \App\Exceptions\BookingCreationException(
                    'Mohon maaf, jam yang Anda pilih baru saja penuh dibooking orang lain. Silakan pilih jam lain.'
                );
            }

            return Booking::create([
                'location_id' => $location->id,
                'service_category_id' => $data['service_category_id'] ?? null,
                'package_name' => $data['package_name'] ?? null,
                'people_count' => $data['people_count'] ?? null,
                'costume_changes' => $data['costume_changes'] ?? 0,
                'dp_amount' => $data['dp_amount'] ?? 0,
                'order_code' => $data['order_code'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'booking_code' => Booking::generateBookingCode($location),
                'status' => 'confirmed',
            ]);
        });
    }
}
