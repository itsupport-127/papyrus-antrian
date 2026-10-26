<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status baru `ready_for_edit` ("Siap Diedit") — dipakai fotografer buat
 * "numpuk" tiket yang pengambilan fotonya sudah selesai tapi belum mulai
 * diedit, supaya bisa diproses beberapa sekaligus lewat batch (lihat
 * OperatorController::finishPhotoSession() dan batchStartEditing()).
 *
 * Beda dengan `editing`: `ready_for_edit` = antri nunggu diedit,
 * `editing` = beneran lagi dikerjakan.
 *
 * Pakai raw SQL karena kolom `status` didefinisikan sebagai MySQL ENUM
 * (bukan string biasa), dan doctrine/dbal tidak terpasang di project ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'preparing', 'serving', 'editing', 'ready_for_edit', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('ready_for_edit_at')->nullable()->after('photo_completed_at');
        });
    }

    public function down(): void
    {
        DB::table('tickets')->where('status', 'ready_for_edit')->update(['status' => 'serving']);

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ready_for_edit_at');
        });

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('waiting', 'calling', 'preparing', 'serving', 'editing', 'completed', 'skipped', 'cancelled') DEFAULT 'waiting'");
    }
};
