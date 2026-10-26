<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `notes` di sini sama fungsinya kayak Ticket::notes -- catatan order dari
 * pelanggan (mis. "2x cetak 4R, 1x album"). Diisi CS pas bikin reservasi
 * manual, terus ikut kebawa ke tiket begitu reservasinya check-in (lihat
 * Booking::checkIn()), jadi gak perlu ditulis ulang dari nol.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
