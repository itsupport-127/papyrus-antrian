<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Tambahan" reservasi (pilihan: Kostum, Background, Pose) -- menggantikan
 * input angka "Tambahan Pergantian Kostum". Disimpan sebagai array JSON.
 * Kolom costume_changes lama dibiarkan supaya data reservasi lama tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('extras')->nullable()->after('costume_changes');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('extras');
        });
    }
};
