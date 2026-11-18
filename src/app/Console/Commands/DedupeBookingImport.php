<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Location;
use Illuminate\Console\Command;

class DedupeBookingImport extends Command
{
    protected $signature = 'bookings:dedupe-import {--force : Benar-benar hapus (tanpa ini hanya simulasi)}';

    protected $description = 'Hapus booking hasil import Excel yang dobel di lokasi non-reservasi (mis. PP) bila sudah ada di lokasi reservasi (BK).';

    public function handle(): int
    {
        $bookingIds = Location::where('service_type', 'booking')->pluck('id')->all();
        if (!$bookingIds) {
            $this->error('Tidak ada lokasi dengan service_type = booking.');

            return self::FAILURE;
        }

        $keep = [];
        Booking::whereIn('location_id', $bookingIds)->get(['order_code', 'booking_date', 'booking_time'])->each(function (Booking $b) use (&$keep) {
            $keep[$this->key($b)] = true;
        });

        $victims = Booking::whereNotIn('location_id', $bookingIds)
            ->where('source', 'excel')->whereNull('ticket_id')
            ->get()->filter(fn (Booking $b) => isset($keep[$this->key($b)]));

        $this->info($victims->count() . ' booking dobel ditemukan di lokasi non-reservasi.');
        if (!$this->option('force')) {
            $this->line('Simulasi saja. Jalankan dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        $victims->each->delete();
        $this->info('Selesai dihapus.');

        return self::SUCCESS;
    }

    private function key(Booking $b): string
    {
        return strtoupper((string) $b->order_code) . '|' . $b->booking_date->format('Y-m-d') . '|' . $b->booking_time->format('H:i');
    }
}
