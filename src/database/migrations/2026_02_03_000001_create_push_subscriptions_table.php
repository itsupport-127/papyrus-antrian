<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores Web Push subscriptions (PWA) so pelanggan can be notified even
     * when the ticket page tab / browser is fully closed. Tied to a ticket
     * (not a user) since customers are anonymous / not authenticated.
     */
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key')->nullable(); // p256dh
            $table->string('auth_token')->nullable(); // auth
            $table->string('content_encoding')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
