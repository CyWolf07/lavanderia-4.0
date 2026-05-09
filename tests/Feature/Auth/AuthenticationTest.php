<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate using formatted cedula', function () {
    $user = User::factory()->create([
        'cedula' => '1000000003',
        'activo' => true,
    ]);

    $response = $this->post('/login', [
        'login' => '1.000.000.003',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('logout does not fail with csrf page expired for any role', function (string $rol) {
    $this->withMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $user = User::factory()->create([
        'rol' => $rol,
        'activo' => true,
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
})->with(['admin', 'programador', 'usuario', 'recolector']);
