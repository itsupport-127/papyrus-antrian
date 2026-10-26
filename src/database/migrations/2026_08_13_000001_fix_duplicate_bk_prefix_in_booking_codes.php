<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan data lama: sebelum kode reservasi digenerate tanpa kode lokasi
 * (lihat komentar di Booking::generateBookingCode()), reservasi yang sudah
 * dibuat SEBELUM perubahan itu terlanjur tersimpan dobel, mis.
 * "BK-BK-20260812-005" (kode lokasi "BK" + prefix "BK-" yang memang sudah
 * baku di format kode). Migrasi ini SATU KALI membetulkan data lama yang
 * masih dobel itu jadi "BK-20260812-005", tanpa mengubah generator kode
 * (itu sudah benar, cuma data lamanya yang perlu dibersihkan).
 *
 * Aman dijalankan berkali-kali (idempotent) -- begitu prefiksnya sudah
 * "BK-" (bukan "BK-BK-"), baris itu tidak lagi kena UPDATE.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('booking_code', 'like', 'BK-BK-%')
            ->update([
                // Buang SATU "BK-" di depan saja (SUBSTRING dari karakter ke-4),
                // supaya "BK-BK-20260812-005" -> "BK-20260812-005".
                'booking_code' => DB::raw("CONCAT('BK-', SUBSTRING(booking_code, 7))"),
            ]);
    }

    public function down(): void
    {
        // Sengaja tidak di-reverse -- format lama ("BK-BK-...") memang salah
        // dan tidak perlu dikembalikan lagi kalau migrasi ini di-rollback.
    }
};
