<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * called_at & served_at di-reset ke null tiap kali tiket pindah stage
     * (lihat Ticket::completeRegistration / advanceStage) supaya UI stage
     * yang sedang berjalan selalu nunjukin waktu panggil/layan yang segar.
     * Efek sampingnya: begitu tiket sudah lewat stage tertentu, riwayat
     * waktunya HILANG PERMANEN -- gak bisa dihitung ulang buat widget
     * "WAKTU" (breakdown per tahap). Kolom-kolom di bawah ini nyimpen
     * salinannya secara terpisah, khusus buat pelacakan durasi, tanpa
     * mengubah perilaku called_at/served_at yang sudah ada.
     *
     * Dicek satu-satu pakai hasColumn() sebelum nambah, karena beberapa
     * kolom (mis. editing_started_at) mungkin sudah pernah ditambahkan
     * manual/dari percobaan migrasi sebelumnya -- biar migration ini aman
     * dijalankan ulang di database yang datanya sudah campur begitu.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'photo_called_at')) {
                $table->timestamp('photo_called_at')->nullable()->after('registration_completed_at');
            }
            if (!Schema::hasColumn('tickets', 'photo_served_at')) {
                $table->timestamp('photo_served_at')->nullable()->after('photo_called_at');
            }
            if (!Schema::hasColumn('tickets', 'editing_started_at')) {
                $table->timestamp('editing_started_at')->nullable()->after('photo_completed_at');
            }
            if (!Schema::hasColumn('tickets', 'payment_called_at')) {
                $table->timestamp('payment_called_at')->nullable()->after('ready_for_edit_at');
            }
            if (!Schema::hasColumn('tickets', 'payment_served_at')) {
                $table->timestamp('payment_served_at')->nullable()->after('payment_called_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['photo_called_at', 'photo_served_at', 'editing_started_at', 'payment_called_at', 'payment_served_at'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
