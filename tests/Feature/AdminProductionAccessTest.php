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

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('Usuarios registrados')
        ->assertSeeText('3')
        ->assertSeeText('Registros activos')
        ->assertSeeText('2')
        ->assertSeeText('Ingreso activo')
        ->assertSeeText('$ 50.000');
});
