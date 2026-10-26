<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('customer_phone');

            // Which stage of the photo-order pipeline this ticket is currently in.
            // registration    -> pelanggan mengisi data & CS mencatat order manual (kertas -> sistem)
            // photo_session   -> menunggu / sedang dipanggil untuk sesi foto
            // payment         -> menunggu / sedang dipanggil untuk pembayaran & cetak foto
            $table->enum('stage', ['registration', 'photo_session', 'payment'])
                ->default('registration')
                ->after('status');

            $table->decimal('total_price', 12, 2)->default(0)->after('notes');
            $table->string('acc_pemesanan')->nullable()->after('total_price'); // nama CS yang meng-ACC order

            $table->timestamp('registration_completed_at')->nullable()->after('completed_at');
            $table->timestamp('photo_completed_at')->nullable()->after('registration_completed_at');
            $table->timestamp('payment_completed_at')->nullable()->after('photo_completed_at');

            $table->index(['location_id', 'stage', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['location_id', 'stage', 'status']);
            $table->dropColumn([
                'customer_email',
                'stage',
                'total_price',
                'acc_pemesanan',
                'registration_completed_at',
                'photo_completed_at',
                'payment_completed_at',
            ]);
        });
    }
};
