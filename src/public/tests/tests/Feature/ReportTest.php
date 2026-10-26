<?php

use App\Models\Feedback;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;

test('admin can access reports page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/reports');

    $response->assertStatus(200);
    $response->assertSee('Laporan');
});

test('non-admin cannot access reports', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $response = $this->actingAs($viewer)->get('/admin/reports');

    $response->assertStatus(403);
});

test('reports show today period by default', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create(['code' => 'RPT']);

    Ticket::factory()->count(3)->create([
        'location_id' => $location->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get('/admin/reports');

    $response->assertStatus(200);
    $response->assertSee('3'); // total tickets
});

test('reports can filter by period', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/reports?period=week');

    $response->assertStatus(200);
});

test('reports can filter by location', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create(['code' => 'RP2']);

    $response = $this->actingAs($admin)->get("/admin/reports?location_id={$location->id}");

    $response->assertStatus(200);
});

test('reports show feedback statistics', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create(['code' => 'RP3']);

    $ticket1 = Ticket::factory()->create(['location_id' => $location->id, 'status' => 'completed']);
    $ticket2 = Ticket::factory()->create(['location_id' => $location->id, 'status' => 'completed']);

    Feedback::factory()->create(['ticket_id' => $ticket1->id, 'rating' => 5]);
    Feedback::factory()->create(['ticket_id' => $ticket2->id, 'rating' => 3]);

    $response = $this->actingAs($admin)->get('/admin/reports');

    $response->assertStatus(200);
    $response->assertSee('Feedback Pelanggan');
});

test('reports show operator performance', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create(['code' => 'RP4']);
    $operator = User::factory()->create([
        'role' => 'operator',
        'name' => 'Operator Test',
        'location_id' => $location->id,
    ]);

    Ticket::factory()->count(5)->create([
        'location_id' => $location->id,
        'served_by' => $operator->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get('/admin/reports');

    $response->assertStatus(200);
    $response->assertSee('Operator Test');
});
