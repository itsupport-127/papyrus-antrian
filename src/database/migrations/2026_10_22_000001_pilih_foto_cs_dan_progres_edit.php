<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alur reservasi: Pilih Foto lewat antrian CS (menunggu -> dipanggil -> timer jalan -> selesai),
 * lalu bayar dan edit berjalan di belakang dengan estimasi + catatan progres untuk pelanggan.
 * - tickets.select_queued_at : sesi foto selesai, masuk antrian Pilih Foto (CS)
 * - tickets.select_called_at : CS memanggil (diulang tiap Panggil Ulang)
 * - tickets.edit_eta_date / edit_note : estimasi selesai edit & catatan progres (dilihat pelanggan)
 * - booking_packages.edit_days : lama edit per paket (hari) -> estimasi awal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('select_queued_at')->nullable();
            $table->timestamp('select_called_at')->nullable();
            $table->date('edit_eta_date')->nullable();
            $table->string('edit_note', 255)->nullable();
        });

        Schema::table('booking_packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('edit_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['select_queued_at', 'select_called_at', 'edit_eta_date', 'edit_note']);
        });

        Schema::table('booking_packages', function (Blueprint $table) {
            $table->dropColumn('edit_days');
        });
    }
};
