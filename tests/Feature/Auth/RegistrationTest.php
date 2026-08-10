<?php

use App\Models\User;

test('registration screen can be rendered for the first user', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen redirects to login after the first user exists', function () {
    User::factory()->create();

    $this->get('/register')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('register');
});

test('first user can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('additional public registrations are blocked', function () {
    User::factory()->create();

    $this->post('/register', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('register');

    $this->assertDatabaseMissing('users', [
        'email' => 'blocked@example.com',
    ]);
});
