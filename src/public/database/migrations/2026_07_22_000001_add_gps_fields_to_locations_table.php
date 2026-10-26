<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinat GPS lokasi + radius yang diizinkan (meter), dipakai untuk
 * membatasi pengambilan tiket walk-in supaya cuma bisa dilakukan saat
 * pelanggan benar-benar berada di lokasi. Kalau latitude/longitude kosong,
 * berarti lokasi itu belum dikonfigurasi GPS-nya dan pengambilan tiket
 * TIDAK dibatasi (supaya lokasi lama tidak mendadak terkunci begitu
 * migration ini jalan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('gps_radius_meters')->default(150)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'gps_radius_meters']);
        });
    }
};
