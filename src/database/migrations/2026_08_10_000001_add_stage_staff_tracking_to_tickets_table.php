<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama alasannya kayak migrasi 2026_07_30_000001 (stage duration
     * tracking): kolom `served_by` di tabel tickets ketimpa TIAP KALI
     * tiket pindah tahap (lihat Ticket::call()) -- begitu tiket selesai,
     * `served_by` cuma nyimpen staf TERAKHIR yang pegang (biasanya
     * kasir), riwayat siapa yang registrasi/motret duluan udah hilang.
     * Ini bikin gak mungkin bikin laporan "evaluasi kerja per staf per
     * tahap" (mis. rata-rata durasi sesi foto per fotografer) secara
     * akurat.
     *
     * Kolom-kolom di bawah nyimpen salinan staf yang menangani tiap
     * KELOMPOK tahap (registrasi, sesi foto, bayar) secara terpisah --
     * pola sama persis kayak photo_served_at dkk buat waktu, diisi SEKALI
     * di kejadian pertama, gak ditimpa kalau "Panggil Ulang"/"Mulai
     * Melayani" dipencet ulang oleh staf yang sama atau staf lain di
     * tahap yang sama.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'registered_by')) {
                $table->foreignId('registered_by')->nullable()->after('served_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'photographed_by')) {
                $table->foreignId('photographed_by')->nullable()->after('registered_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('photographed_by')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['registered_by', 'photographed_by', 'paid_by'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
