<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\FacturaRecolectorDetalle;
use App\Models\RecolectorPrenda;
use App\Models\User;

it('allows a collector to print their own order', function () {
    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $otroRecolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Impresión',
        'celular' => '300 111 2233',
        'direccion' => 'Calle 10',
        'barrio' => 'Centro',
        'activo' => true,
        'recolector_id' => $recolector->id,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Ropa',
        'precio' => 12000,
        'activo' => true,
    ]);

    $facturaPropia = FacturaRecolector::create([
        'numero_orden' => 100001,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'direccion' => $cliente->direccion,
        'numero_cliente' => $cliente->numero_cliente,
        'celular' => $cliente->celular,
        'observaciones' => ['Está manchado'],
        'total_prendas' => 1,
        'total' => 12000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $facturaPropia->id,
        'recolector_prenda_id' => $prenda->id,
        'prenda_nombre' => $prenda->nombre,
        'valor_unitario' => 12000,
        'cantidad' => 1,
        'color_prenda' => 'Blanco',
        'subtotal' => 12000,
    ]);

    $facturaAjena = FacturaRecolector::create([
        'numero_orden' => 100002,
        'recolector_id' => $otroRecolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'direccion' => $cliente->direccion,
        'numero_cliente' => $cliente->numero_cliente,
        'celular' => $cliente->celular,
        'total_prendas' => 1,
        'total' => 12000,
        'estado_factura' => 'pendiente',
    ]);

    $this->actingAs($recolector)
        ->get(route('recolector.facturas.imprimir', [
            'facturaRecolector' => $facturaPropia,
            'formato' => 'ticket',
        ]))
        ->assertOk()
        ->assertSee('Vista previa de impresión')
        ->assertSee('Orden de pedido')
        ->assertSee('100001')
        ->assertSee('Cliente Impresión')
        ->assertSee('Camisa');

    $this->actingAs($recolector)
        ->get(route('recolector.facturas.imprimir', $facturaAjena))
        ->assertForbidden();
});

it('allows admin to print any collector order', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'name' => 'Recolector Uno',
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Admin',
        'celular' => '300 444 5566',
        'direccion' => 'Carrera 7',
        'barrio' => 'Norte',
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 200001,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'direccion' => $cliente->direccion,
        'numero_cliente' => $cliente->numero_cliente,
        'celular' => $cliente->celular,
        'total_prendas' => 0,
        'total' => 0,
        'estado_factura' => 'pendiente',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.facturas-recolector.imprimir', [
            'facturaRecolector' => $factura,
            'formato' => 'carta',
        ]))
        ->assertOk()
        ->assertSee('Recolector Uno')
        ->assertSee('200001');
});
