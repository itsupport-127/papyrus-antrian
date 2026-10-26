<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dipakai buat fitur "Panggil Ulang" versi CS/Kasir di card "Antrian
     * Siap-siap" -- CS bisa nge-nudge ulang notifikasi/TTS "bersiap-siap"
     * buat tiket yang statusnya masih `preparing` (BELUM dipanggil masuk
     * studio oleh fotografer), tanpa mengubah status/counter_id-nya.
     *
     * `prepare_recall_count` & `prepare_recalled_by` dipakai buat nampilin
     * info di card "Antrian Siap-siap" fotografer, biar ftg tau kalau CS
     * sudah bantu manggil ulang tiket ini (sebelumnya gak ada info apa pun,
     * ftg gak tau tiketnya udah "di-nudge" apa belum).
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'prepare_recall_count')) {
                $table->unsignedInteger('prepare_recall_count')->default(0)->after('called_at');
            }
            if (!Schema::hasColumn('tickets', 'prepare_recalled_by')) {
                $table->foreignId('prepare_recalled_by')->nullable()->after('prepare_recall_count')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'prepare_recalled_by')) {
                $table->dropConstrainedForeignId('prepare_recalled_by');
            }
            if (Schema::hasColumn('tickets', 'prepare_recall_count')) {
                $table->dropColumn('prepare_recall_count');
            }
        });
    }
};
