<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "List Booking": data jadwal dari Excel (arsip + pantau harian) disimpan di tabel
 * bookings yang SAMA dengan reservasi, supaya booking hari ini bisa langsung di-check-in.
 * Semua kolom baru nullable / punya default, jadi reservasi lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Excel tidak punya nomor HP.
            $table->string('customer_phone')->nullable()->change();

            $table->string('source', 20)->default('web')->after('status');   // web | manual | excel
            $table->string('univ', 100)->nullable()->after('source');        // kolom "UNIV" di Excel
            $table->time('arrived_time')->nullable();                        // DATANG
            $table->time('started_time')->nullable();                        // MULAI / MASUK STD
            $table->time('finished_time')->nullable();                       // SELESAI / KELUAR
            $table->boolean('needs_review')->default(false)->index();
            $table->string('review_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['source', 'univ', 'arrived_time', 'started_time', 'finished_time', 'needs_review', 'review_note']);
        });
    }
};
