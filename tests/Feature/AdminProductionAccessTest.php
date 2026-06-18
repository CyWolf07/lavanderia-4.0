<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\Prenda;
use App\Models\Produccion;
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

it('shows active admin totals from production and collector invoices', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 10000,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente prueba',
        'nit_cedula' => '900123456',
        'celular' => '3001234567',
        'direccion' => 'Calle 1',
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 2,
        'total' => 20000,
        'fecha' => now()->toDateString(),
    ]);

    FacturaRecolector::create([
        'numero_orden' => 1,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 3,
        'total' => 30000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolector::create([
        'numero_orden' => 2,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 5,
        'total' => 70000,
        'estado_factura' => 'cancelado',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('Lavanderos registrados')
        ->assertSeeText('3')
        ->assertSeeText('Registros activos')
        ->assertSeeText('2')
        ->assertSeeText('Ingreso activo')
        ->assertSeeText('$ 30.000')
        ->assertSeeText('Pago Lavanderos')
        ->assertSeeText('$ 20.000')
        ->assertDontSeeText('$ 120.000');
});

it('allows programmers to delete paid collector invoices from the status table', function () {
    $programmer = User::factory()->create([
        'rol' => 'programador',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente pago',
        'nit_cedula' => '900987654',
        'celular' => '3009876543',
        'direccion' => 'Calle 2',
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 9,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 1,
        'total' => 15000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
    ]);

    $this->actingAs($programmer)
        ->delete(route('admin.facturas-recolector.destroy', $factura))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseMissing('facturas_recolector', [
        'id' => $factura->id,
    ]);
});

it('allows admins to toggle collector price editing permission', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'puede_editar_precios' => false,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.usuarios.toggle-precios', $collector))
        ->assertRedirect();

    expect($collector->fresh()->puede_editar_precios)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.usuarios.toggle-precios', $collector))
        ->assertRedirect();

    expect($collector->fresh()->puede_editar_precios)->toBeFalse();
});

it('allows privileged users to delete selected active production records in bulk', function (string $role) {
    $user = User::factory()->create([
        'rol' => $role,
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Unidad',
        'precio' => 12000,
    ]);

    $selected = collect(range(1, 2))->map(fn () => Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 1,
        'total' => 12000,
        'fecha' => now()->toDateString(),
    ]));

    $kept = Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 3,
        'total' => 36000,
        'fecha' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->delete(route('admin.produccion.destroy-bulk'), [
            'produccion_ids' => $selected->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.dashboard'));

    foreach ($selected as $produccion) {
        $this->assertDatabaseMissing('producciones', [
            'id' => $produccion->id,
        ]);
    }

    $this->assertDatabaseHas('producciones', [
        'id' => $kept->id,
    ]);
})->with(['admin', 'programador']);
