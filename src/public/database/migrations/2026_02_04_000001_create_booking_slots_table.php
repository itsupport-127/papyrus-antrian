<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal slot reservasi yang bisa diatur admin/CS per lokasi:
     * hari apa saja, jam berapa saja, dan kapasitas (berapa reservasi
     * boleh masuk) per slot. Bisa diisi manual satu-satu (custom) atau
     * di-generate otomatis (mis. tiap 30 menit dari jam buka-tutup).
     */
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Minggu ... 6 = Sabtu
            $table->time('start_time');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'day_of_week', 'start_time'], 'booking_slots_unique_slot');
            $table->index(['location_id', 'day_of_week', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
