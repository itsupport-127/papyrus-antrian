<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status baru `preparing` ("Siap-siap") — dipanggil fotografer buat
 * pelanggan BERIKUTNYA selagi pelanggan yang sekarang masih di-`serving`,
 * supaya pelanggan itu bisa ganti baju/bersiap dan gak ada jeda kosong
 * antar sesi foto. Lihat docs/ALUR-ANTRIAN-STUDIO.md bagian "Siap-siap
 * (pipelining)" untuk alur lengkapnya.
 *
 * Pakai raw SQL karena kolom `status` didefinisikan sebagai MySQL ENUM
 * (bukan string biasa), dan doctrine/dbal tidak terpasang di project ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'preparing', 'serving', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::table('tickets')->where('status', 'preparing')->update(['status' => 'waiting']);

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'serving', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");
    }
};
