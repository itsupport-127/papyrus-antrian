<?php

use App\Models\Feedback;
use App\Models\Location;
use App\Models\Ticket;

test('can submit feedback for completed ticket', function () {
    $location = Location::factory()->create(['code' => 'FB1']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 5,
        'comment' => 'Pelayanan sangat baik!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('feedbacks', [
        'ticket_id' => $ticket->id,
        'rating' => 5,
        'comment' => 'Pelayanan sangat baik!',
    ]);
});

test('cannot submit feedback for non-completed ticket', function () {
    $location = Location::factory()->create(['code' => 'FB2']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'waiting',
    ]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 4,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $this->assertDatabaseMissing('feedbacks', [
        'ticket_id' => $ticket->id,
    ]);
});

test('cannot submit duplicate feedback', function () {
    $location = Location::factory()->create(['code' => 'FB3']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    Feedback::factory()->create(['ticket_id' => $ticket->id]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 3,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(Feedback::where('ticket_id', $ticket->id)->count())->toBe(1);
});

test('feedback requires valid rating', function () {
    $location = Location::factory()->create(['code' => 'FB4']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 6,
    ]);

    $response->assertSessionHasErrors('rating');
});

test('feedback rating must be at least 1', function () {
    $location = Location::factory()->create(['code' => 'FB5']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 0,
    ]);

    $response->assertSessionHasErrors('rating');
});

test('feedback comment is optional', function () {
    $location = Location::factory()->create(['code' => 'FB6']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    $response = $this->post("/tickets/{$ticket->id}/feedback", [
        'rating' => 4,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('feedbacks', [
        'ticket_id' => $ticket->id,
        'rating' => 4,
        'comment' => null,
    ]);
});

test('ticket show page displays feedback form for completed ticket', function () {
    $location = Location::factory()->create(['code' => 'FB7']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
        'ticket_number' => 'FB7-001',
    ]);

    $response = $this->get("/tickets/{$ticket->id}");

    $response->assertStatus(200);
    $response->assertSee('Berikan Penilaian');
});

test('ticket show page displays existing feedback', function () {
    $location = Location::factory()->create(['code' => 'FB8']);
    $ticket = Ticket::factory()->create([
        'location_id' => $location->id,
        'status' => 'completed',
        'ticket_number' => 'FB8-001',
    ]);

    Feedback::factory()->create([
        'ticket_id' => $ticket->id,
        'rating' => 5,
        'comment' => 'Luar biasa bagus!',
    ]);

    $response = $this->get("/tickets/{$ticket->id}");

    $response->assertStatus(200);
    $response->assertSee('Luar biasa bagus!');
});
