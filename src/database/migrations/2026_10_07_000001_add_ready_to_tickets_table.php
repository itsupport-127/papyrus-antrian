<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tombol "Ready" di Siap-siap: CS (yang melihat ruang depan) menandai
 * pelanggan yang SUDAH siap/ada di tempat, baru setelah itu fotografer boleh
 * memanggilnya masuk studio.
 *
 * - ready_at : kapan CS menandai Ready (null = belum ready).
 * - ready_by : staf yang menandai.
 * Direset tiap kali tiket (baru) dipanggil Siap-siap -- lihat Ticket::callToPrepare().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('ready_at')->nullable();
            $table->unsignedBigInteger('ready_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['ready_at', 'ready_by']);
        });
    }
};
