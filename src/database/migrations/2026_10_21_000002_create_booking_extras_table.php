<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar pilihan dropdown "Tambahan" di form reservasi, dikelola Admin lewat
 * menu "Tambahan Reservasi" (sama seperti "Paket Reservasi"). Reservasi
 * menyimpan NAMA pilihannya (bookings.extras, JSON), bukan id -- jadi
 * menghapus/menonaktifkan pilihan tidak merusak reservasi lama.
 */
return new class extends Migration
{
    private array $defaults = ['Kostum', 'Background', 'Pose'];

    public function up(): void
    {
        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('booking_extras')->insert(array_map(
            fn (string $name, int $i) => [
                'name' => $name,
                'is_active' => true,
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $this->defaults,
            array_keys($this->defaults)
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extras');
    }
};
