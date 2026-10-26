<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `is_paused` = operator/fotografer yang lagi pegang loket ini sedang
 * istirahat sebentar (BEDA dengan `is_active` yang diatur Admin buat
 * matiin loket permanen/struktural). Loket yang di-pause TETAP terikat ke
 * operator itu (current_operator_id gak berubah) — cuma sementara gak
 * ditawarin buat "Panggil Antrian Berikutnya" / auto-siap-siap, sampai
 * operatornya sendiri nyalain lagi. Lihat OperatorController::togglePause().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->boolean('is_paused')->default(false)->after('current_operator_id');
        });
    }

    public function down(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->dropColumn('is_paused');
        });
    }
};
