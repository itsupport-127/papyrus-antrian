<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Implementasi §7 langkah 1 dari docs/ALUR-ANTRIAN-STUDIO.md.
 *
 * - `confirmed_at`: diisi CS lewat Ticket::confirmOrder() saat klik
 *   "Konfirmasi Pesanan" (Tahap 3). Selama kosong, tiket tidak boleh lanjut
 *   ke photo_session (guard di Ticket::completeRegistration()).
 * - `notes_locked_at`: diisi otomatis di Ticket::startServing() begitu
 *   sesi foto mulai (stage = photo_session). Selama terisi, `notes` cuma
 *   boleh diedit Admin (guard di OperatorController::updateNotes()).
 * - `locations.gps_radius_meters` default diturunkan 150 -> 50 untuk baris
 *   BARU saja (lokasi yang sudah ada nilai spesifiknya tidak berubah).
 *   Dipakai ALTER MODIFY langsung (bukan Schema::table()->change()) karena
 *   proyek ini tidak menginstal doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('notes');
            $table->timestamp('notes_locked_at')->nullable()->after('confirmed_at');
        });

        DB::statement('ALTER TABLE locations MODIFY gps_radius_meters INT UNSIGNED NOT NULL DEFAULT 50');
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'notes_locked_at']);
        });

        DB::statement('ALTER TABLE locations MODIFY gps_radius_meters INT UNSIGNED NOT NULL DEFAULT 150');
    }
};
