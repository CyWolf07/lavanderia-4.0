<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\User;

it('paginates and filters admin invoices without reducing dashboard totals', function () {
    $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    $recolector = User::factory()->create(['rol' => 'recolector', 'name' => 'Recolector Norte']);
    $cliente = Cliente::create(['nombre' => 'Cliente Centro', 'activo' => true]);
    foreach (range(1, 25) as $numero) {
        FacturaRecolector::create([
            'numero_orden' => $numero, 'recolector_id' => $recolector->id, 'cliente_id' => $cliente->id,
            'fecha_ingreso' => now(), 'fecha_entrega' => now()->addDay(),
            'total' => 10000, 'total_prendas' => 1, 'estado_factura' => 'pendiente',
        ]);
    }
    $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    expect($response->viewData('ultimasFacturasRecolector')->count())->toBe(20)
        ->and($response->viewData('ultimasFacturasRecolector')->total())->toBe(25)
        ->and((float) $response->viewData('ingresoFacturasPorDia')->sum('total'))->toBe(250000.0);

    $this->get(route('admin.dashboard', ['facturas_page' => 2]))->assertOk()
        ->assertViewHas('ultimasFacturasRecolector', fn ($rows) => $rows->count() === 5);

    $filtered = $this->get(route('admin.dashboard', ['buscar_factura' => '#000025', 'estado_factura' => 'pendiente']))->assertOk();
    expect($filtered->viewData('ultimasFacturasRecolector')->total())->toBe(1)
        ->and((int) $filtered->viewData('facturaStatusResumen')['pendiente']->cantidad)->toBe(25)
        ->and((float) $filtered->viewData('ingresoFacturasPorDia')->sum('total'))->toBe(250000.0);

    foreach (['cliente centro', 'recolector norte'] as $busqueda) {
        $this->get(route('admin.dashboard', ['buscar_factura' => $busqueda]))->assertOk()
            ->assertViewHas('ultimasFacturasRecolector', fn ($rows) => $rows->total() === 25);
    }
    $this->get(route('admin.dashboard', ['estado_factura' => 'cancelado']))->assertOk()
        ->assertViewHas('ultimasFacturasRecolector', fn ($rows) => $rows->total() === 0);
});
