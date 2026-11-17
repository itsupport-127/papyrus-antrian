<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Penanda hari wisuda kampus (kalender List Booking). Satu tanggal boleh punya beberapa kampus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('campus', 120);
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_days');
    }
};
