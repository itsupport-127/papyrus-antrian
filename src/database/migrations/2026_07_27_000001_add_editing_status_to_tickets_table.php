<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status baru `editing` ("Sedang Diedit") — dipakai di tahap photo_session
 * setelah fotografer selesai MEMOTRET (status `serving`) tapi belum
 * beneran kelar, karena hasilnya masih diedit dulu. Sebelum ini, kedua
 * pekerjaan (motret + edit) sama-sama numpuk di status `serving`, jadi
 * pelanggan gak bisa bedain mana lagi difoto, mana lagi diedit — lihat
 * docs/ALUR-ANTRIAN-STUDIO.md bagian "Sedang Foto vs Sedang Diedit".
 *
 * Transisinya TETAP manual (tombol "Foto Selesai, Mulai Edit" diklik
 * fotografer sendiri) karena cuma fotografer yang tau kapan pemotretan
 * beneran selesai — bukan sesuatu yang bisa dideteksi otomatis.
 *
 * Pakai raw SQL karena kolom `status` didefinisikan sebagai MySQL ENUM
 * (bukan string biasa), dan doctrine/dbal tidak terpasang di project ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'preparing', 'serving', 'editing', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::table('tickets')->where('status', 'editing')->update(['status' => 'serving']);

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'preparing', 'serving', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");
    }
};
