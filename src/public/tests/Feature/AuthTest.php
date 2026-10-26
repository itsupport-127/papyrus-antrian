<?php

use App\Models\User;

test('can view login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Masuk');
});

test('can view register page', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Daftar');
});

test('can register new user', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => 'viewer',
    ]);
});

test('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'user@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin is redirected to admin dashboard after login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
});

test('can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout');

    $this->assertGuest();
});
