<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai tiket "edit menyusul" (lihat migrasi 2026_08_18_000001) yang
 * hasil cetaknya SUDAH BENERAN diserahkan ke pelanggan -- beda dengan
 * `edit_completed_at` yang cuma berarti "editnya udah kelar" (belum tentu
 * udah diambil). Dipakai supaya kasir tahu mana tiket "nyebrang hari" yang
 * masih perlu ditindaklanjuti (foto sudah jadi tapi pelanggan belum balik
 * ambil) vs yang sudah benar-benar tuntas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('edit_completed_at');
            }
            // Penanda TEGAS "tiket ini pernah lewat skipToPaymentPendingEdit()".
            // Kenapa perlu kolom terpisah, bukan cuma nebak dari
            // editing_counter_id: kolom itu diisi utk SEMUA tiket (dipakai
            // buat tracking studio biasa), bukan cuma yang lewat skip-flow --
            // jadi gak bisa dipakai buat mastiin ini tiket "istimewa" atau
            // tiket normal. Tanpa kolom ini, isReadyForPickup() akan salah
            // ke-true untuk SETIAP tiket normal yang sudah lunas (karena
            // editing_counter_id & edit_completed_at akhirnya sama-sama
            // terisi juga di alur normal), bikin badge "Siap Diambil" nongol
            // di semua transaksi, bukan cuma yang beneran nyebrang hari.
            if (!Schema::hasColumn('tickets', 'payment_skipped_pending_edit_at')) {
                $table->timestamp('payment_skipped_pending_edit_at')->nullable()->after('picked_up_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'picked_up_at')) {
                $table->dropColumn('picked_up_at');
            }
            if (Schema::hasColumn('tickets', 'payment_skipped_pending_edit_at')) {
                $table->dropColumn('payment_skipped_pending_edit_at');
            }
        });
    }
};
