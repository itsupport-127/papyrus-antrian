<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar "Pilihan Paket" untuk reservasi, dikelola Admin lewat menu
 * "Paket Reservasi" (sebelumnya statis di config/booking.php).
 *
 * Diisi awal dengan 21 paket dari spreadsheet LIST BOOKING 2026 (nama sudah
 * dirapikan: "ART 1" -> "ARTISTIK 1", "GRUP BIG" -> "GROUP BIG", dst) supaya
 * dropdown tidak kosong begitu fitur ini dipasang.
 *
 * bookings.package_name menyimpan NAMA paket (teks), bukan id -- jadi menghapus
 * atau menonaktifkan paket di sini tidak merusak reservasi yang sudah ada.
 */
return new class extends Migration
{
    private array $defaults = [
        'PERSONAL', 'SIMPLE', 'ELEGANT', 'EXCLUSIVE', 'PREMIUM A', 'PREMIUM B',
        'ARTISTIK 1', 'ARTISTIK 2', 'ART MONOKROM', 'GROUP MINI', 'GROUP BIG',
        'GROUP REALSET', 'BABY 1', 'BABY 2', 'HAPPY KIDS', 'KETUPAT 16RP',
        'KETUPAT 20RP', 'KETUPAT 24RP', 'EXPRESS', 'PASFOTO', 'SPECIAL',
    ];

    public function up(): void
    {
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('booking_packages')->insert(array_map(
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
        Schema::dropIfExists('booking_packages');
    }
};
