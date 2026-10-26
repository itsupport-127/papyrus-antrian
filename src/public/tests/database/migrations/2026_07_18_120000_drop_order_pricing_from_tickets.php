<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_items');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['total_price', 'acc_pemesanan']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('total_price', 12, 2)->default(0)->after('notes');
            $table->string('acc_pemesanan')->nullable()->after('total_price');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('description');
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->index('ticket_id');
        });
    }
};
