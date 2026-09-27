<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\User;

it('paginates collector invoices while keeping complete totals and excluding other collectors', function () {
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $other = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $cliente = Cliente::create(['nombre' => 'Cliente prueba', 'activo' => true, 'recolector_id' => $user->id]);
    foreach (range(1, 22) as $number) {
        FacturaRecolector::create([
            'numero_orden' => $number,
            'recolector_id' => $number === 22 ? $other->id : $user->id,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now(), 'fecha_entrega' => now()->addDays(2),
            'total' => 10000, 'total_prendas' => 1, 'estado_factura' => 'pendiente',
        ]);
    }

    $response = $this->actingAs($user)->get(route('recolector.index'))->assertOk();
    expect($response->viewData('facturas')->count())->toBe(20)
        ->and($response->viewData('facturas')->total())->toBe(21)
        ->and((int) $response->viewData('facturaStatusResumen')['pendiente']->cantidad)->toBe(21)
        ->and((float) $response->viewData('facturaStatusResumen')['pendiente']->total)->toBe(210000.0);
    $this->get(route('recolector.index', ['page' => 2]))->assertOk()
        ->assertViewHas('facturas', fn ($facturas) => $facturas->count() === 1 && $facturas->first()->numero_orden === 1);
});
