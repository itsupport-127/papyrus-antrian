<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mendukung 2 fitur terkait proses edit foto:
 *
 * 1. "Ambil Alih" — kalau fotografer A pulang dengan tiket masih
 *    `ready_for_edit`/`editing`, dan besoknya fotografer B masuk ke studio
 *    yang sama, B perlu ditawarin (bukan otomatis) buat lanjutin kerjaan A.
 *    Ini masih pakai kolom `counter_id`/`served_by` yang SUDAH ADA (belum
 *    di-reset selama tiket masih di stage `photo_session`) — jadi TIDAK
 *    butuh kolom baru. Cukup query tambahan di dashboard: tiket yang
 *    `counter_id`-nya = studio yang lagi dipegang user, tapi `served_by`
 *    BUKAN dia.
 *
 * 2. "Lanjut ke Kasir — Edit Menyusul" — tiket boleh lanjut ke antrian
 *    pembayaran walau editnya belum kelar. Masalahnya: begitu
 *    `Ticket::advanceStage()` mindahin tiket dari `photo_session` ke
 *    `payment`, `counter_id` & `served_by` DI-RESET jadi null (biar loket
 *    studio bebas dipakai pelanggan lain) — jadi begitu itu terjadi, tidak
 *    ada lagi cara tau "ini seharusnya diselesaikan editnya oleh studio
 *    mana". Makanya perlu kolom terpisah yang TIDAK ikut ke-reset:
 *    - `editing_counter_id`: studio yang tanggung jawab nyelesain edit
 *      tiket ini. Diisi begitu tiket masuk `ready_for_edit` (lihat
 *      OperatorController::finishPhotoSession()), TIDAK ikut ke-reset
 *      walau tiket sudah lanjut ke stage `payment`/`completed`.
 *    - `edit_completed_at`: kosong berarti editnya belum benar-benar
 *      selesai (baik masih di panel edit, ATAU sudah kadung dilewatin ke
 *      kasir lewat "Edit Menyusul"). Diisi begitu fotografer klik "Edit
 *      Selesai" -- baik itu lewat alur normal (sebelum lanjut ke
 *      pembayaran) maupun belakangan (setelah tiket sudah di kasir/selesai
 *      bayar). Dipakai buat nampilin label "Hasil Edit Menyusul" ke
 *      pelanggan & kasir, dan buat filter panel "Sudah Dibayar, Edit Belum
 *      Selesai" di dashboard studio.
 *    - `edited_by`: fotografer yang PERTAMA kali klik "Mulai Edit" untuk
 *      tiket ini (baik dia sendiri atau yang "Ambil Alih" dari orang lain).
 *      Diisi sekali, tidak ditimpa kalau tiket di-ambil-alih atau
 *      dilanjutkan orang lain -- beda tujuan dengan `photographed_by`
 *      (siapa yang MOTRET), field ini nyimpen siapa yang MULAI ngedit,
 *      buat laporan evaluasi kerja per staf per tahap (pola sama seperti
 *      migrasi 2026_08_10_000001_add_stage_staff_tracking_to_tickets_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'edited_by')) {
                $table->foreignId('edited_by')->nullable()->after('paid_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'editing_counter_id')) {
                $table->foreignId('editing_counter_id')->nullable()->after('editing_started_at')
                    ->constrained('counters')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'edit_completed_at')) {
                $table->timestamp('edit_completed_at')->nullable()->after('editing_counter_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'edited_by')) {
                $table->dropConstrainedForeignId('edited_by');
            }
            if (Schema::hasColumn('tickets', 'editing_counter_id')) {
                $table->dropConstrainedForeignId('editing_counter_id');
            }
            if (Schema::hasColumn('tickets', 'edit_completed_at')) {
                $table->dropColumn('edit_completed_at');
            }
        });
    }
};
