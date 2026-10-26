<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // "Lewati" tidak lagi mengakhiri tiket — tiket kembali ke antrian
            // menunggu pada tahap yang sama. Kolom ini dipakai untuk menaruh
            // tiket yang dilewati ke belakang antrian (bukan hilang) dan untuk
            // pelaporan berapa kali sebuah tiket dilewati.
            $table->unsignedInteger('skip_count')->default(0)->after('notes');
            $table->timestamp('last_skipped_at')->nullable()->after('skip_count');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['skip_count', 'last_skipped_at']);
        });
    }
};
