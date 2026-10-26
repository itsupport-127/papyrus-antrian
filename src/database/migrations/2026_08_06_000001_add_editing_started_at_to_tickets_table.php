<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `editing_started_at` = persis kapan status tiket masuk 'editing' (lihat
 * Ticket::startEditing()). Sebelum ini, gak ada cara buat misahin "berapa
 * lama sesi foto/motret" dari "berapa lama proses edit" -- soalnya
 * `photo_completed_at` cuma nyatet kapan TAHAP photo_session-nya kelar
 * total (termasuk edit), bukan kapan motretnya doang selesai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('editing_started_at')->nullable()->after('ready_for_edit_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('editing_started_at');
        });
    }
};
