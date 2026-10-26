<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar "template" baris order yang sering dipakai CS/Kasir (mis. "1x PP A
 * merah", "2x Cetak 10R") — diatur Admin per lokasi, ditampilkan sebagai
 * tombol cepat di kolom Order supaya CS gak perlu ngetik ulang hal yang
 * sama terus-menerus. Disimpan sebagai teks satu baris per template
 * (bukan tabel terpisah) supaya gampang diatur lewat 1 textarea di form
 * Admin -> Lokasi, tanpa perlu CRUD terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->text('order_line_suggestions')->nullable()->after('gps_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('order_line_suggestions');
        });
    }
};
