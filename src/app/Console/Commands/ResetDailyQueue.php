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
        // Tiket yang isEditPending() (lihat migrasi 2026_08_18 & 2026_08_27
        // -- artinya lewat "Lanjut ke Kasir, Edit Menyusul") DIKECUALIKAN
        // dari auto-cancel, walau status-nya masih waiting/calling. Kalau
        // tidak, pelanggan yang fotonya masih diproses tapi belum sempat
        // bayar sampai lewat tengah malam bakal ke-cancel otomatis, padahal
        // studio masih punya kewajiban nyelesain editnya.
        //
        // Patokannya `payment_skipped_pending_edit_at` (BUKAN
        // editing_counter_id) -- kolom itu diisi utk SEMUA tiket yang
        // pernah masuk tahap edit foto (dipakai buat tracking studio biasa),
        // jadi gak bisa dipakai buat mastiin "ini tiket istimewa lewat
        // skip-flow" vs tiket normal. `payment_skipped_pending_edit_at`
        // satu-satunya penanda yang benar-benar eksklusif.
        $cancelledTickets = Ticket::whereIn('status', ['waiting', 'calling'])
            ->whereDate('created_at', '<', today())
            ->where(function ($query) {
                $query->whereNull('payment_skipped_pending_edit_at')
                    ->orWhereNotNull('edit_completed_at');
            })
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
