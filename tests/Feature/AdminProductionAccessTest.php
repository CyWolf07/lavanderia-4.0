<?php

use App\Models\User;

it('allows administrators to access production from the admin panel', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ingresar a produccion');

    $this->actingAs($admin)
        ->get(route('produccion.index'))
        ->assertOk();
});
