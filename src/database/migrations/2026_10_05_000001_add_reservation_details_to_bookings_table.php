<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detail reservasi sesuai format booking WA (diisi CS/operator, bukan
 * pelanggan): jumlah orang, pilihan paket, tambahan kostum, DP, plus kode
 * order (KO, "JG0xxxxx") yang selama ini dicatat CS di spreadsheet.
 *
 * - package_name  : nama paket (teks, dari daftar config/booking.php). Sengaja
 *                   BUKAN service_category_id karena Layanan itu milik 1 lokasi,
 *                   sedangkan reservasi bisa masuk ke lokasi "BK" yang beda.
 * - dp_amount     : nominal DP dalam rupiah (0 = belum DP).
 * - dp_forfeited  : true kalau reservasi dibatalkan padahal sudah DP
 *                   (ketentuan: cancel booking = DP hangus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('package_name', 100)->nullable()->after('service_category_id');
            $table->unsignedSmallInteger('people_count')->nullable()->after('package_name');
            $table->unsignedTinyInteger('costume_changes')->default(0)->after('people_count');
            $table->unsignedInteger('dp_amount')->default(0)->after('costume_changes');
            $table->boolean('dp_forfeited')->default(false)->after('dp_amount');
            $table->string('order_code', 50)->nullable()->after('dp_forfeited');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['package_name', 'people_count', 'costume_changes', 'dp_amount', 'dp_forfeited', 'order_code']);
        });
    }
};
