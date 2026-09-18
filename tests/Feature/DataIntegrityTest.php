<?php

use App\Models\{AuditEvent, Cliente, FacturaRecolector, PagoRecolector, Prenda, Produccion, RecolectorPrenda, User};
use App\Services\NumeroOrdenService;

function integrityOrder(): FacturaRecolector
{
    $recolector = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $cliente = Cliente::create(['nombre' => 'Cliente auditoria', 'activo' => true, 'recolector_id' => $recolector->id]);
    $factura = FacturaRecolector::create([
        'recolector_id' => $recolector->id, 'cliente_id' => $cliente->id,
        'numero_orden' => 5000, 'fecha_ingreso' => now(), 'fecha_entrega' => now()->addDays(2),
        'total' => 10000, 'total_prendas' => 1, 'estado_factura' => 'pendiente',
    ]);
    $prenda = RecolectorPrenda::create(['nombre' => 'Camisa auditoria', 'precio' => 10000, 'activo' => true]);
    $factura->detalles()->create([
        'recolector_prenda_id' => $prenda->id, 'prenda_nombre' => $prenda->nombre,
        'cantidad' => 1, 'valor_unitario' => 10000, 'subtotal' => 10000, 'color_prenda' => 'Azul',
    ]);
    return $factura;
}

function integrityEdit(FacturaRecolector $factura): array
{
    return [
        'cliente_id' => $factura->cliente_id,
        'items' => [['prenda_id' => $factura->detalles()->first()->recolector_prenda_id, 'cantidad' => 1, 'precio_unitario' => 11000]],
    ];
}

it('prevents deleting a client with invoices', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    $this->actingAs($admin)->delete(route('clientes.destroy', $factura->cliente_id))->assertSessionHasErrors('cliente');
    $this->assertDatabaseHas('facturas_recolector', ['id' => $factura->id]);
    $this->assertDatabaseHas('clientes', ['id' => $factura->cliente_id]);
});

it('protects financial users through both account deletion routes', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $factura->recolector_id))->assertSessionHasErrors('user');
    $this->actingAs($factura->recolector)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'user');
    $this->assertAuthenticatedAs($factura->recolector);
    $this->assertDatabaseHas('facturas_recolector', ['id' => $factura->id]);
});

it('protects washer production when deleting a garment', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $prenda = Prenda::create(['nombre' => 'Camisa', 'precio' => 1000, 'activo' => true]);
    $produccion = Produccion::create(['user_id' => $admin->id, 'prenda_id' => $prenda->id, 'cantidad' => 1, 'total' => 1000, 'fecha' => today()]);
    $this->actingAs($admin)->delete(route('prendas.destroy', $prenda))->assertSessionHasErrors('prenda');
    $this->assertDatabaseHas('producciones', ['id' => $produccion->id]);
});

it('rejects client delegation to inactive or non collector users', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    foreach ([$admin, User::factory()->create(['rol' => 'recolector', 'activo' => false])] as $invalid) {
        $this->actingAs($admin)->patch(route('clientes.delegar', $factura->cliente_id), ['recolector_id' => $invalid->id])->assertSessionHasErrors('recolector_id');
    }
    $this->assertDatabaseHas('clientes', ['id' => $factura->cliente_id, 'recolector_id' => $factura->recolector_id]);
});

it('rejects selected invoice rows without a valid garment instead of saving empty invoices', function () {
    $factura = integrityOrder();
    $this->actingAs($factura->recolector)->post(route('recolector.facturas.store'), [
        'cliente_id' => $factura->cliente_id,
        'items' => [['selected' => true, 'cantidad' => 1, 'colores' => ['Azul']]],
    ])->assertSessionHasErrors('items');
    $this->assertDatabaseCount('facturas_recolector', 1);
});

it('keeps colors laundry links and delivery date when editing an invoice', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    $detalle = $factura->detalles()->first();
    $detalle->update(['lavado_por' => $admin->id, 'lavado_en' => now()]);
    $this->actingAs($admin)->put(route('admin.facturas-recolector.update', $factura), integrityEdit($factura))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('factura_recolector_detalles', ['id' => $detalle->id, 'color_prenda' => 'Azul', 'lavado_por' => $admin->id, 'subtotal' => 11000]);
    expect($detalle->fresh()->lavado_en)->not->toBeNull();
    expect($factura->fresh()->fecha_entrega->toDateString())->toBe($factura->fecha_entrega->toDateString());
});

it('rejects quantity changes to already washed details without partial writes', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    $factura->detalles()->first()->update(['lavado_por' => $admin->id, 'lavado_en' => now()]);
    $data = integrityEdit($factura);
    $data['items'][0]['cantidad'] = 2;
    $this->actingAs($admin)->put(route('admin.facturas-recolector.update', $factura), $data)->assertSessionHasErrors('items');
    expect((float) $factura->fresh()->total)->toBe(10000.0);
});

it('rejects duplicate garment lines in admin invoice edits', function () {
    $factura = integrityOrder();
    $admin = User::factory()->create(['rol' => 'admin']);
    $data = integrityEdit($factura);
    $data['items'][] = $data['items'][0];
    $this->actingAs($admin)->put(route('admin.facturas-recolector.update', $factura), $data)->assertSessionHasErrors('items.0.prenda_id');
});

it('recalculates commission and records correct audit status after deleting paid invoice', function () {
    $factura = integrityOrder();
    $factura->update(['estado_factura' => 'pagado', 'quincena_pago' => '2026/09/QUINCENA1']);
    PagoRecolector::recalcular($factura->recolector_id, $factura->quincena_pago);
    $admin = User::factory()->create(['rol' => 'admin']);
    $this->actingAs($admin)->delete(route('admin.facturas-recolector.destroy', $factura))->assertSessionHasNoErrors();
    expect((float) PagoRecolector::first()->monto_comision)->toBe(0.0);
    expect(AuditEvent::first()->metadata['estado'])->toBe('pagado');
});

it('allocates order numbers above historical invoices without existing blocks', function () {
    $factura = integrityOrder();
    $service = app(NumeroOrdenService::class);
    expect($service->peekSiguiente($factura->recolector_id))->toBe(5001);
    expect($service->obtenerSiguiente($factura->recolector_id))->toBe(5001);
    expect($service->obtenerSiguiente($factura->recolector_id))->toBe(5002);
});

it('keeps PQRS private and assigns ownership from the authenticated session', function () {
    $owner = User::factory()->create(['rol' => 'usuario']);
    $other = User::factory()->create(['rol' => 'recolector']);
    $data = ['tipo' => 'Queja', 'nombre' => 'Solicitante', 'correo' => 'prueba@example.test', 'descripcion' => 'Detalle privado de auditoria'];
    $this->actingAs($owner)->post(route('pqrs.store'), $data + ['user_id' => $other->id])->assertSessionHasNoErrors();
    $pqrs = \App\Models\Pqrs::firstOrFail();
    expect($pqrs->user_id)->toBe($owner->id);
    $this->actingAs($owner)->get(route('pqrs.index'))->assertSee($data['descripcion']);
    $this->actingAs($other)->get(route('pqrs.index'))->assertDontSee($data['descripcion']);
    $this->actingAs($other)->put(route('pqrs.update', $pqrs), $data)->assertForbidden();
    $this->actingAs($other)->delete(route('pqrs.destroy', $pqrs))->assertForbidden();
    $pqrs->update(['user_id' => null]);
    $this->actingAs($owner)->get(route('pqrs.index'))->assertDontSee($data['descripcion']);
    $admin = User::factory()->create(['rol' => 'admin']);
    $this->actingAs($admin)->get(route('pqrs.index'))->assertSee($data['descripcion']);
});

it('closes production once and preserves its source identifier', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $prenda = Prenda::create(['nombre' => 'Prenda cierre', 'precio' => 1200, 'activo' => true]);
    $produccion = Produccion::create(['user_id' => $admin->id, 'prenda_id' => $prenda->id, 'cantidad' => 2, 'total' => 2400, 'fecha' => today()]);
    $this->actingAs($admin)->post(route('produccion.cerrar'))->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('produccion.cerrar'))->assertRedirect();
    $this->assertDatabaseCount('historial_producciones', 1);
    $this->assertDatabaseHas('historial_producciones', ['produccion_origen_id' => $produccion->id, 'total' => 2400]);
    $this->assertDatabaseCount('producciones', 0);
});
