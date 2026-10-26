<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `confirmed_at`      — diisi saat CS klik "Konfirmasi Pesanan". Sebelum ini
 *                        terisi, tiket tidak boleh lanjut ke stage photo_session
 *                        (lihat Ticket::confirmOrder() / completeRegistration()).
 * `notes_locked_at`   — diisi otomatis saat sesi foto mulai (startServing()
 *                        pada stage photo_session). Selama terisi, `notes`
 *                        cuma boleh diedit Admin.
 *
 * Lihat docs/ALUR-ANTRIAN-STUDIO.md untuk konteks alur lengkapnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('notes');
            $table->timestamp('notes_locked_at')->nullable()->after('confirmed_at');
        });

        // Turunkan default radius GPS dari 150m -> 50m (lihat
        // docs/ALUR-ANTRIAN-STUDIO.md §6 keputusan #4). Cuma ubah lokasi yang
        // masih pakai default lama — lokasi yang sudah di-set manual ke nilai
        // lain oleh admin TIDAK disentuh. Pakai raw SQL (bukan ->change())
        // karena doctrine/dbal tidak terpasang di project ini.
        DB::statement('ALTER TABLE locations ALTER COLUMN gps_radius_meters SET DEFAULT 50');
        DB::table('locations')->where('gps_radius_meters', 150)->update(['gps_radius_meters' => 50]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'notes_locked_at']);
        });
    }
};
