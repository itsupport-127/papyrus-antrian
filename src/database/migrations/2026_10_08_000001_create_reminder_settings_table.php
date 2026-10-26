<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan PENGINGAT di dashboard operator (diatur Admin lewat menu
 * "Pengingat"). Hanya 3 jenis -- yang sering terlupa:
 *  - ready   : CS lupa klik "Ready" untuk pelanggan di Siap-siap
 *  - payment : pembayaran sudah selesai tapi lupa ditekan "Selesai" di app
 *  - edit    : fotografer lupa menyelesaikan edit foto
 *
 * start_after_minutes : mulai mengingatkan setelah tiket berada di kondisi itu
 *                       selama sekian menit.
 * interval_minutes    : sesudah itu diingatkan lagi tiap sekian menit, sampai
 *                       tiketnya diselesaikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('start_after_minutes')->default(3);
            $table->unsignedSmallInteger('interval_minutes')->default(3);
            $table->timestamps();
        });

        $now = now();
        DB::table('reminder_settings')->insert([
            ['type' => 'ready', 'enabled' => true, 'start_after_minutes' => 1, 'interval_minutes' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'payment', 'enabled' => true, 'start_after_minutes' => 3, 'interval_minutes' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'edit', 'enabled' => true, 'start_after_minutes' => 10, 'interval_minutes' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_settings');
    }
};
