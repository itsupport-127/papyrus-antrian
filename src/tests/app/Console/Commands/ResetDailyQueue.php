<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Console\Command;

class ResetDailyQueue extends Command
{
    protected $signature = 'queue:reset-daily';

    protected $description = 'Cancel all remaining waiting/calling tickets and unattended reservations from previous days, resetting queue state for the new day';

    public function handle(): int
    {
        $cancelledTickets = Ticket::whereIn('status', ['waiting', 'calling'])
            ->whereDate('created_at', '<', today())
            ->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'notes' => 'Auto-cancelled: antrian direset otomatis di awal hari baru.',
            ]);

        // Reservasi hari-hari sebelumnya yang belum di-check-in (pelanggan
        // tidak datang) ikut ditandai batal, supaya tidak menumpuk selamanya
        // sebagai "Menunggu Konfirmasi" / "Akan datang".
        $cancelledBookings = Booking::whereIn('status', ['pending', 'confirmed'])
            ->whereDate('booking_date', '<', today())
            ->update([
                'status' => 'cancelled',
            ]);

        $this->info("Reset antrian selesai. {$cancelledTickets} tiket dibatalkan otomatis, {$cancelledBookings} reservasi lama ditandai batal (tidak hadir).");

        return self::SUCCESS;
    }
}
