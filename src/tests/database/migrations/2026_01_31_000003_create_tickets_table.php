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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number'); // e.g., 'CS-001', 'PAY-042'
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('status', ['waiting', 'calling', 'serving', 'completed', 'skipped', 'cancelled'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('service_time')->nullable(); // in seconds
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'status', 'created_at']);
            $table->index(['ticket_number', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
