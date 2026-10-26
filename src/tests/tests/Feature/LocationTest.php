<?php

use App\Models\Location;
use App\Models\Ticket;

test('can view locations list', function () {
    Location::factory()->create(['name' => 'Customer Service', 'code' => 'CS1']);

    $response = $this->get('/locations');

    $response->assertStatus(200);
    $response->assertSee('Customer Service');
});

test('can view single location', function () {
    $location = Location::factory()->create(['name' => 'Payment', 'code' => 'PAY1']);

    $response = $this->get("/locations/{$location->id}");

    $response->assertStatus(200);
    $response->assertSee('Payment');
});

test('can view ticket creation form', function () {
    $location = Location::factory()->create([
        'open_time' => '00:00',
        'close_time' => '23:59',
    ]);

    $response = $this->get("/locations/{$location->id}/ticket");

    $response->assertStatus(200);
    $response->assertSee('Order Sekarang');
});

test('can create a ticket', function () {
    $location = Location::factory()->create([
        'code' => 'CS',
        'open_time' => '00:00',
        'close_time' => '23:59',
    ]);

    $response = $this->post("/locations/{$location->id}/ticket", [
        'customer_name' => 'John Doe',
        'customer_phone' => '08123456789',
        'customer_email' => 'john@example.com',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tickets', [
        'location_id' => $location->id,
        'customer_name' => 'John Doe',
        'customer_phone' => '08123456789',
        'status' => 'waiting',
    ]);
});

test('ticket number follows location code pattern', function () {
    $location = Location::factory()->create([
        'code' => 'CS',
        'open_time' => '00:00',
        'close_time' => '23:59',
    ]);

    $this->post("/locations/{$location->id}/ticket", [
        'customer_name' => 'Test User',
        'customer_phone' => '08123456789',
        'customer_email' => 'test@example.com',
    ]);

    $ticket = Ticket::first();

    expect($ticket->ticket_number)->toStartWith('CS-');
});

test('can view ticket status', function () {
    $location = Location::factory()->create(['code' => 'CS2']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'ticket_number' => 'CS-001',
    ]);

    $response = $this->get("/tickets/{$ticket->id}");

    $response->assertStatus(200);
    $response->assertSee('CS-001');
});

test('can check ticket by number', function () {
    $location = Location::factory()->create(['code' => 'CS3']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'ticket_number' => 'CS-099',
    ]);

    $response = $this->get('/tickets/check?ticket_number=CS-099');

    $response->assertRedirect("/tickets/{$ticket->id}");
});

test('displays location as closed outside operating hours', function () {
    $location = Location::factory()->create([
        'open_time' => '23:00',
        'close_time' => '23:30',
    ]);

    // If current time is outside 23:00-23:30, it should show closed
    if (now()->format('H:i') < '23:00' || now()->format('H:i') > '23:30') {
        $response = $this->get("/locations/{$location->id}/ticket");
        $response->assertSee('Tutup');
    } else {
        $this->assertTrue(true);
    }
});
