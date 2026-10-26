<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap "Pilih Foto" (khusus tiket reservasi) dengan timer.
 * - tickets.select_*: kapan pilih foto dimulai, batas waktunya, dan kapan selesai.
 * - locations.photo_select_minutes: durasi default (menit), diatur CS/Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('select_started_at')->nullable();
            $table->timestamp('select_ends_at')->nullable();
            $table->timestamp('select_done_at')->nullable();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedSmallInteger('photo_select_minutes')->default(15);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['select_started_at', 'select_ends_at', 'select_done_at']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('photo_select_minutes');
        });
    }
};
