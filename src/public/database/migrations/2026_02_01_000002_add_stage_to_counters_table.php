<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            // Menentukan loket ini melayani panggilan tahap apa (bisa lebih dari
            // satu, misalnya loket "CS & Kasir" menangani registration + payment
            // sekaligus, karena tugas CS dan kasir digabung jadi satu loket).
            $table->json('stages')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->dropColumn('stages');
        });
    }
};
