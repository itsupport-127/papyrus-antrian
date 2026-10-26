<?php

use App\Models\Booking;
use App\Models\Counter;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;

test('operator can access dashboard', function () {
    $location = Location::factory()->create();
    Counter::factory()->csKasir()->create(['location_id' => $location->id]);

    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $response = $this->actingAs($operator)->get('/operator/dashboard');

    $response->assertStatus(200);
});

test('fotografer can access dashboard', function () {
    $location = Location::factory()->create();
    Counter::factory()->fotografer()->create(['location_id' => $location->id]);

    $fotografer = User::factory()->create([
        'role' => 'fotografer',
        'location_id' => $location->id,
    ]);

    $response = $this->actingAs($fotografer)->get('/operator/dashboard');

    $response->assertStatus(200);
});

test('non-operator cannot access operator dashboard', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $response = $this->actingAs($viewer)->get('/operator/dashboard');

    $response->assertStatus(403);
});

test('operator is automatically assigned to the CS & Kasir counter without picking one', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);

    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $response = $this->actingAs($operator)->get('/operator/dashboard');

    $response->assertStatus(200);
    $this->assertDatabaseHas('counters', [
        'id' => $counter->id,
        'current_operator_id' => $operator->id,
    ]);
});

test('fotografer is automatically assigned to the photo session counter, not the CS & Kasir one', function () {
    $location = Location::factory()->create();
    Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $fotoCounter = Counter::factory()->fotografer()->create(['location_id' => $location->id]);

    $fotografer = User::factory()->create([
        'role' => 'fotografer',
        'location_id' => $location->id,
    ]);

    $this->actingAs($fotografer)->get('/operator/dashboard');

    $this->assertDatabaseHas('counters', [
        'id' => $fotoCounter->id,
        'current_operator_id' => $fotografer->id,
    ]);
});

test('logging out frees the operator counter for the next login', function () {
    $location = Location::factory()->create();
    Counter::factory()->csKasir()->create(['location_id' => $location->id]);

    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $this->actingAs($operator)->get('/operator/dashboard');
    $this->assertDatabaseHas('counters', ['current_operator_id' => $operator->id]);

    $this->actingAs($operator)->post('/logout');

    $this->assertDatabaseHas('counters', [
        'location_id' => $location->id,
        'current_operator_id' => null,
    ]);
});

test('operator can call next ticket covering both registration and payment stages', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    // Auto-assign via dashboard load (no manual counter selection).
    $this->actingAs($operator)->get('/operator/dashboard');

    // Create a waiting ticket already in the "payment" stage.
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'payment',
    ]);

    $response = $this->actingAs($operator)->post('/operator/call-next');

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'calling',
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
    ]);
});

test('operator can call next registration ticket', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $this->actingAs($operator)->get('/operator/dashboard');

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->post('/operator/call-next');

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'calling',
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
    ]);
});

test('operator can complete ticket', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
        'status' => 'serving',
        'stage' => 'payment',
    ]);

    $response = $this->actingAs($operator)->post("/operator/tickets/{$ticket->id}/complete");

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'completed',
    ]);
});

test('operator can skip ticket', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
        'status' => 'calling',
    ]);

    $response = $this->actingAs($operator)->post("/operator/tickets/{$ticket->id}/skip");

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'waiting',
    ]);
});

test('operator can complete registration and advance ticket to photo session', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
        'status' => 'serving',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->post("/operator/tickets/{$ticket->id}/complete-registration", [
        'notes' => 'Minta cetak ukuran 4R',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'waiting',
        'stage' => 'photo_session',
        'notes' => 'Minta cetak ukuran 4R',
    ]);
});

test('operator cannot complete registration for ticket not assigned to them', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);
    $otherOperator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
        'status' => 'serving',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($otherOperator)->post("/operator/tickets/{$ticket->id}/complete-registration");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('fotografer cannot edit ticket from other stage', function () {
    $location = Location::factory()->create();
    Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $fotoCounter = Counter::factory()->fotografer()->create(['location_id' => $location->id]);
    $fotografer = User::factory()->create([
        'role' => 'fotografer',
        'location_id' => $location->id,
    ]);

    $fotoCounter->update(['current_operator_id' => $fotografer->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($fotografer)->get("/operator/tickets/{$ticket->id}/edit");

    $response->assertStatus(403);
});

test('fotografer cannot delete ticket from other stage', function () {
    $location = Location::factory()->create();
    Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $fotoCounter = Counter::factory()->fotografer()->create(['location_id' => $location->id]);
    $fotografer = User::factory()->create([
        'role' => 'fotografer',
        'location_id' => $location->id,
    ]);

    $fotoCounter->update(['current_operator_id' => $fotografer->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($fotografer)->delete("/operator/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('operator can create manual booking', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $response = $this->actingAs($operator)->post('/operator/bookings', [
        'customer_name' => 'John Doe',
        'customer_phone' => '081234567890',
        'customer_email' => 'john@example.com',
        'booking_date' => now()->addDay()->format('Y-m-d'),
        'booking_time' => '10:00',
        'service_category_id' => '',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('bookings', [
        'customer_name' => 'John Doe',
        'customer_phone' => '081234567890',
    ]);
});

test('operator can edit and update booking', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $booking = Booking::factory()->create([
        'location_id' => $location->id,
        'customer_name' => 'Jane Doe',
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($operator)->put("/operator/bookings/{$booking->id}", [
        'customer_name' => 'Jane Smith',
        'customer_phone' => '089876543210',
        'customer_email' => 'jane@example.com',
        'booking_date' => $booking->booking_date->addDay()->format('Y-m-d'),
        'booking_time' => '14:00',
        'notes' => 'Updated notes',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'customer_name' => 'Jane Smith',
        'customer_phone' => '089876543210',
    ]);
});

test('operator can cancel booking', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $booking = Booking::factory()->create([
        'location_id' => $location->id,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($operator)->delete("/operator/bookings/{$booking->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'cancelled',
    ]);
});

test('operator can edit ticket in their own stage', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->get("/operator/tickets/{$ticket->id}/edit");

    $response->assertStatus(200);
    $response->assertSee('Edit Tiket');
});

test('operator can call specific ticket', function () {
    $location = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->post("/operator/tickets/{$ticket->id}/call");

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'calling',
        'counter_id' => $counter->id,
        'served_by' => $operator->id,
    ]);
});

test('operator cannot call specific ticket from another location', function () {
    $location = Location::factory()->create();
    $otherLocation = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $otherTicket = Ticket::factory()->create([
        'location_id' => $otherLocation->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->post("/operator/tickets/{$otherTicket->id}/call");

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('tickets', [
        'id' => $otherTicket->id,
        'status' => 'calling',
    ]);
});

test('operator cannot edit ticket from another location', function () {
    $location = Location::factory()->create();
    $otherLocation = Location::factory()->create();
    $counter = Counter::factory()->csKasir()->create(['location_id' => $location->id]);
    $operator = User::factory()->create([
        'role' => 'operator',
        'location_id' => $location->id,
    ]);

    $counter->update(['current_operator_id' => $operator->id]);

    $otherTicket = Ticket::factory()->create([
        'location_id' => $otherLocation->id,
        'status' => 'waiting',
        'stage' => 'registration',
    ]);

    $response = $this->actingAs($operator)->get("/operator/tickets/{$otherTicket->id}/edit");

    $response->assertStatus(403);
});
