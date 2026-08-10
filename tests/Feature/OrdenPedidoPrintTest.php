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

it('keeps collector invoice status modals inside the alpine component', function () {
    $collector = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = crearOrdenParaImpresion($collector);

    $html = $this->actingAs($collector)
        ->get(route('recolector.index'))
        ->assertOk()
        ->assertSee('Imprimir orden')
        ->assertSee(route('ordenes.imprimir', ['facturaRecolector' => $order, 'imprimir' => 1]), false)
        ->getContent();

    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);

    foreach (['modalEstatus', 'modalOrdenes'] as $modalState) {
        $modal = $xpath->query("//*[@x-show='{$modalState}']")->item(0);

        expect($modal)->not->toBeNull();

        $ancestor = $modal->parentNode;
        while ($ancestor instanceof DOMElement && ! $ancestor->hasAttribute('x-data')) {
            $ancestor = $ancestor->parentNode;
        }

        expect($ancestor)->toBeInstanceOf(DOMElement::class)
            ->and($ancestor->hasAttribute('x-data'))->toBeTrue();
    }
});

it('allows the collector to mark an own pending invoice as paid', function () {
    $collector = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = crearOrdenParaImpresion($collector);

    $this->actingAs($collector)
        ->from(route('recolector.index'))
        ->patch(route('recolector.facturas.estatus', $order), [
            'estado_factura' => 'pagado',
            'metodo_pago' => 'efectivo',
        ])
        ->assertRedirect(route('recolector.index'))
        ->assertSessionHas('success');

    expect($order->fresh()->estado_factura)->toBe('pagado')
        ->and($order->fresh()->metodo_pago)->toBe('efectivo')
        ->and($order->fresh()->quincena_pago)->not->toBeNull();
});

it('allows an administrator to cancel a pending collector invoice', function () {
    $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    $collector = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = crearOrdenParaImpresion($collector);

    $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->patch(route('admin.facturas-recolector.estatus', $order), [
            'estado_factura' => 'cancelado',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    expect($order->fresh()->estado_factura)->toBe('cancelado');
});
