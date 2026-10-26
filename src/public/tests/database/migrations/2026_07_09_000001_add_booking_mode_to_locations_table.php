<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode reservasi per lokasi:
     * - 'slots' : pelanggan pilih dari jam yang sudah disiapkan admin
     *             (lihat tabel booking_slots), dengan kapasitas per jam.
     * - 'free'  : pelanggan bebas isi jam berapa saja selama masih di
     *             dalam jam operasional lokasi (open_time - close_time),
     *             tanpa dibatasi slot/kapasitas tertentu.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('booking_mode', ['slots', 'free'])->default('slots')->after('close_time');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('booking_mode');
        });
    }
};
