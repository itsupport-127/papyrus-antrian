<?php

use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\User;

test('admin can view service categories', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create();
    ServiceCategory::factory()->count(3)->create(['location_id' => $location->id]);

    $response = $this->actingAs($admin)->get("/admin/locations/{$location->id}/categories");

    $response->assertStatus(200);
    $response->assertSee('Kategori Layanan');
});

test('admin can create service category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create();

    $response = $this->actingAs($admin)->post("/admin/locations/{$location->id}/categories", [
        'name' => 'Informasi Umum',
        'description' => 'Layanan informasi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('service_categories', [
        'location_id' => $location->id,
        'name' => 'Informasi Umum',
        'description' => 'Layanan informasi',
        'is_active' => true,
    ]);
});

test('admin can toggle service category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ServiceCategory::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->post("/admin/categories/{$category->id}/toggle");

    $response->assertRedirect();

    $this->assertDatabaseHas('service_categories', [
        'id' => $category->id,
        'is_active' => false,
    ]);
});

test('admin can delete service category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ServiceCategory::factory()->create();

    $response = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('service_categories', [
        'id' => $category->id,
    ]);
});

test('service category name is required', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $location = Location::factory()->create();

    $response = $this->actingAs($admin)->post("/admin/locations/{$location->id}/categories", [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('non-admin cannot manage service categories', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $location = Location::factory()->create();

    $response = $this->actingAs($operator)->get("/admin/locations/{$location->id}/categories");

    $response->assertStatus(403);
});

test('ticket creation page shows service categories', function () {
    $location = Location::factory()->create([
        'code' => 'SC1',
        'open_time' => '00:00',
        'close_time' => '23:59',
    ]);
    ServiceCategory::factory()->create([
        'location_id' => $location->id,
        'name' => 'Pembayaran Tagihan',
    ]);

    $response = $this->get("/locations/{$location->id}/ticket");

    $response->assertStatus(200);
    $response->assertSee('Pembayaran Tagihan');
});

test('can create ticket with service category', function () {
    $location = Location::factory()->create([
        'code' => 'SC2',
        'open_time' => '00:00',
        'close_time' => '23:59',
    ]);
    $category = ServiceCategory::factory()->create(['location_id' => $location->id]);

    $response = $this->post("/locations/{$location->id}/ticket", [
        'customer_name' => 'John',
        'customer_phone' => '08123456789',
        'customer_email' => 'john@example.com',
        'service_category_id' => $category->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tickets', [
        'location_id' => $location->id,
        'service_category_id' => $category->id,
        'customer_name' => 'John',
    ]);
});
