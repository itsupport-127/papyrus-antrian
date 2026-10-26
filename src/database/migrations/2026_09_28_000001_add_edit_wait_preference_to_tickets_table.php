<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Bayar Duluan": pelanggan yang fotonya belum selesai diedit boleh bayar
     * dulu. Kolom ini nyimpen pilihannya -- 'wait' (nunggu hasil edit di
     * tempat) atau 'leave' (pulang dulu, dikabari lewat notifikasi begitu
     * edit selesai) -- biar fotografer & kasir tau siapa yang perlu
     * diprioritaskan. NULL = tiket lama / belum dipilih.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'edit_wait_preference')) {
                $table->string('edit_wait_preference', 10)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'edit_wait_preference')) {
                $table->dropColumn('edit_wait_preference');
            }
        });
    }
};
