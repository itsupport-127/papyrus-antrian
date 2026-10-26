<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique(); // e.g., 'CS', 'PAY', 'REG'
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('average_service_time')->default(5); // in minutes
            $table->time('open_time')->default('08:00');
            $table->time('close_time')->default('17:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
