<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supaya pengelompokan fotografer <-> studio KONSISTEN tiap hari (mis.
 * Fotografer1 selalu Studio Bawah, Fotografer2 selalu Studio Atas),
 * lepas dari siapa yang login/logout duluan.
 *
 * Sebelum ini, pengikatan user <-> Counter cuma lewat `current_operator_id`
 * di tabel `counters`, yang di-claim otomatis "siapa cepat dia dapat" tiap
 * kali login (dan dilepas lagi tiap logout — lihat AuthController::logout).
 * Itu cukup buat kasus 1 fotografer, tapi kalau ada 2+ studio dan mau
 * pemetaan yang tetap, perlu preferensi eksplisit per user.
 *
 * `preferred_counter_id` diisi Admin lewat Admin -> Kelola Pengguna.
 * Selama loket favorit itu free (atau memang lagi dipegang user ini
 * sendiri), `resolveCounterForUser()` akan selalu memprioritaskan ini
 * dibanding "loket kosong pertama yang ketemu".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_counter_id')
                ->nullable()
                ->after('location_id')
                ->constrained('counters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_counter_id');
        });
    }
};
