<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis layanan yang dibuka lokasi ini ke pelanggan:
     * - 'both'    : bisa Ambil Tiket (walk-in) maupun Reservasi.
     * - 'ticket'  : cuma Ambil Tiket (walk-in), tombol Reservasi disembunyikan.
     * - 'booking' : cuma Reservasi, tombol Ambil Tiket disembunyikan.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('service_type', ['both', 'ticket', 'booking'])->default('both')->after('booking_mode');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
