<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `serves` = jenis tiket yang mau dilayani loket ini:
 *   - 'both'    (default): tiket walk-in maupun reservasi, gabungan (perilaku lama)
 *   - 'walkin'  : cuma tiket yang dibuat langsung di lokasi (bukan reservasi)
 *   - 'booking' : cuma tiket yang asalnya dari reservasi online
 *
 * Berguna misal pas ramai: Studio 1 di-set 'booking' biar pelanggan yang
 * udah janjian nggak ketiban antrian walk-in, Studio 2 di-set 'walkin'.
 * Lihat Counter::matchesTicketSource().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->enum('serves', ['both', 'walkin', 'booking'])->default('both')->after('stages');
        });
    }

    public function down(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->dropColumn('serves');
        });
    }
};
