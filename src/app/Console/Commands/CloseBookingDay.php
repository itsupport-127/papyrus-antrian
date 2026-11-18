<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class CloseBookingDay extends Command
{
    protected $signature = 'bookings:close-day {--dry-run : Hanya tampilkan, tidak mengubah data}';

    protected $description = 'Akhir hari: reservasi yang tidak check-in dibatalkan otomatis bila tanpa keterangan; yang ada keterangan ditandai "perlu dicek".';

    public function handle(): int
    {
        $base = Booking::whereDate('booking_date', '<=', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('ticket_id');

        $cancelled = 0;
        $flagged = 0;

        foreach ($base->get() as $b) {
            $hasNotes = trim((string) $b->notes) !== '';

            if (!$hasNotes) {
                if (!$this->option('dry-run')) {
                    // Sengaja TIDAK memakai Booking::cancel(): status DP tidak diubah otomatis,
                    // hangus/tidaknya DP diputuskan CS lewat Edit.
                    $b->update([
                        'status' => 'cancelled',
                        'needs_review' => true,
                        'review_note' => 'Batal otomatis: tidak check-in & tanpa keterangan. Cek status DP bila perlu.',
                    ]);
                }
                $cancelled++;
            } elseif (!$b->needs_review) {
                if (!$this->option('dry-run')) {
                    $b->update([
                        'needs_review' => true,
                        'review_note' => 'Tidak check-in. Ada keterangan, mohon cek: ' . mb_substr(trim((string) $b->notes), 0, 120),
                    ]);
                }
                $flagged++;
            }
        }

        $this->info("Dibatalkan otomatis: {$cancelled}, ditandai perlu dicek: {$flagged}" . ($this->option('dry-run') ? ' (simulasi)' : ''));

        return self::SUCCESS;
    }
}
