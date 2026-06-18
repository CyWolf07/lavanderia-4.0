<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\FacturaRecolectorDetalle;
use App\Models\Prenda;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;
use App\Models\User;

it('does not allow standard users to create free production records', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 7000,
        'activo' => true,
    ]);

    $this->actingAs($usuario)
        ->post(route('produccion.store'), [
            'prenda_id' => $prenda->id,
            'cantidad' => 3,
        ])
        ->assertRedirect(route('produccion.index'))
        ->assertSessionHas('error');

    expect(Produccion::count())->toBe(0);
});

it('lets standard users mark order garments as washed and hides completed orders', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Lavado',
        'celular' => '3001234567',
        'direccion' => 'Calle 12',
        'activo' => true,
    ]);

    $prendaProduccion = Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 7000,
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 10000,
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 123,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 2,
        'total' => 20000,
        'estado_factura' => 'pendiente',
    ]);

    $detalle = FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $factura->id,
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_nombre' => 'Camisa',
        'valor_unitario' => 10000,
        'cantidad' => 2,
        'subtotal' => 20000,
    ]);

    $this->actingAs($usuario)
        ->get(route('produccion.index'))
        ->assertOk()
        ->assertSee('#000123')
        ->assertSee('Camisa')
        ->assertDontSee('Cliente Lavado')
        ->assertDontSee('Calle 12')
        ->assertDontSee('Valor')
        ->assertDontSee('$ 20.000')
        ->assertDontSee('$ 10.000');

    $this->actingAs($usuario)
        ->patch(route('produccion.ordenes.lavado', $factura), [
            'detalles' => [$detalle->id],
        ])
        ->assertRedirect(route('produccion.index'))
        ->assertSessionHas('success');

    $detalle->refresh();

    expect($detalle->lavado_por)->toBe($usuario->id)
        ->and($detalle->lavado_en)->not->toBeNull()
        ->and($detalle->produccion_id)->not->toBeNull();

    $this->assertDatabaseHas('producciones', [
        'id' => $detalle->produccion_id,
        'user_id' => $usuario->id,
        'prenda_id' => $prendaProduccion->id,
        'cantidad' => 2,
        'total' => 14000,
    ]);

    $this->actingAs($usuario)
        ->get(route('produccion.index'))
        ->assertOk()
        ->assertDontSee('#000123');
});
