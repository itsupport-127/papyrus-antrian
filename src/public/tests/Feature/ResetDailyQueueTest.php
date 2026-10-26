<?php

use App\Models\Location;
use App\Models\Ticket;

test('reset daily queue command cancels old waiting tickets', function () {
    $location = Location::factory()->create(['code' => 'RST']);

    // Create tickets from yesterday
    $oldWaiting = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'created_at' => now()->subDay(),
    ]);

    $oldCalling = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'calling',
        'created_at' => now()->subDay(),
    ]);

    // Create today's ticket (should NOT be affected)
    $todayWaiting = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'created_at' => now(),
    ]);

    // Already completed ticket from yesterday (should NOT be affected)
    $oldCompleted = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('queue:reset-daily')
        ->assertExitCode(0);

    $oldWaiting->refresh();
    $oldCalling->refresh();
    $todayWaiting->refresh();
    $oldCompleted->refresh();

    expect($oldWaiting->status)->toBe('cancelled');
    expect($oldWaiting->notes)->toContain('direset otomatis');

    expect($oldCalling->status)->toBe('cancelled');
    expect($oldCalling->notes)->toContain('direset otomatis');

    expect($todayWaiting->status)->toBe('waiting');
    expect($oldCompleted->status)->toBe('completed');
});

test('reset daily queue outputs count', function () {
    $location = Location::factory()->create(['code' => 'RS2']);

    Ticket::factory()->count(3)->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('queue:reset-daily')
        ->expectsOutputToContain('3 tiket dibatalkan')
        ->assertExitCode(0);
});

test('reset daily queue with no old tickets', function () {
    $this->artisan('queue:reset-daily')
        ->expectsOutputToContain('0 tiket dibatalkan')
        ->assertExitCode(0);
});
