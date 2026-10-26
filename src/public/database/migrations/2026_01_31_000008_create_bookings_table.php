<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->date('booking_date');
            $table->time('booking_time');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'])->default('pending');
            $table->string('booking_code')->unique();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['location_id', 'booking_date', 'status']);
            $table->index('booking_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
