<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\User;

function crearOrdenParaImpresion(User $recolector): FacturaRecolector
{
    $cliente = Cliente::create([
        'nombre' => 'Cliente Impresión',
        'celular' => '3001234567',
        'direccion' => 'Calle 10',
        'barrio' => 'Centro',
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 42,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-08-08 09:30:00',
        'fecha_entrega' => '2026-08-10',
        'observaciones' => ['Manchado', 'Lavado especial'],
        'total_prendas' => 2,
        'total' => 24000,
        'estado_factura' => 'pendiente',
    ]);

    $factura->detalles()->create([
        'prenda_nombre' => 'Camisa',
        'valor_unitario' => 12000,
        'cantidad' => 2,
        'color_prenda' => 'Azul, Blanco',
        'subtotal' => 24000,
    ]);

    return $factura;
}

it('allows admin programmer and owner collector to print an order', function (string $role) {
    $collector = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = crearOrdenParaImpresion($collector);
    $user = $role === 'recolector'
        ? $collector
        : User::factory()->create(['rol' => $role, 'activo' => true]);

    $this->actingAs($user)
        ->get(route('ordenes.imprimir', $order))
        ->assertOk()
        ->assertSeeText('Cliente Impresión')
        ->assertSeeText('Camisa')
        ->assertSeeText('Azul, Blanco')
        ->assertSeeText('Manchado, Lavado especial')
        ->assertSeeText('$ 24.000');
})->with(['admin', 'programador', 'recolector']);

it('prevents a collector from printing another collectors order', function () {
    $owner = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $other = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = crearOrdenParaImpresion($owner);

    $this->actingAs($other)
        ->get(route('ordenes.imprimir', $order))
        ->assertForbidden();
});
