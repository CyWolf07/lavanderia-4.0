<?php

use App\Models\User;

it('redirects privileged users to the admin dashboard', function (string $rol) {
    $user = User::factory()->create([
        'rol' => $rol,
        'activo' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('admin.dashboard'));
})->with(['admin', 'programador']);

it('redirects collectors to the collector module', function () {
    $user = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('recolector.index'));
});

it('redirects standard users to production', function () {
    $user = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('produccion.index'));
});
