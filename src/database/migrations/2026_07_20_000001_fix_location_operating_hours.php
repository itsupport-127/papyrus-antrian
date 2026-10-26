<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Beberapa lokasi ketahuan ter-setting jam operasional "00:00 - 23:59
 * (Buka 24 Jam)" dari preset di form Admin, padahal maksudnya jam kantor
 * biasa. Migration ini membetulkan data yang sudah kepalang tersimpan
 * seperti itu, jadi ikut jam kantor 09:00 - 21:00.
 *
 * Catatan: ini cuma menyentuh lokasi yang jam-nya masih persis 00:00-23:59
 * (default preset "Buka 24 Jam"), lokasi lain yang sudah di-set manual ke
 * jam lain tidak ikut berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')
            ->where('open_time', '00:00:00')
            ->where('close_time', '23:59:00')
            ->update([
                'open_time' => '09:00:00',
                'close_time' => '21:00:00',
            ]);
    }

    public function down(): void
    {
        // Tidak ada cara aman untuk tahu baris mana yang tadinya 00:00-23:59,
        // jadi rollback tidak mengembalikan nilai lama.
    }
};
