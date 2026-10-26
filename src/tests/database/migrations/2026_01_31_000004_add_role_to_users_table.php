<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'operator' = CS & Kasir (digabung: registrasi/order + pembayaran & cetak)
            // 'fotografer' = sesi foto
            $table->enum('role', ['admin', 'operator', 'fotografer', 'viewer'])->default('viewer')->after('email');
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['role', 'location_id']);
        });
    }
};
