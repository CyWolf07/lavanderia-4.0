<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\FacturaRecolectorDetalle;
use App\Models\IncongruenciaProduccion;
use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;
use App\Models\SystemSetting;
use App\Models\User;

it('allows standard users to manually register daily washed garments', function () {
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

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Manual',
        'celular' => '3001234567',
        'direccion' => 'Calle 12',
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 10000,
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 101,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 3,
        'total' => 30000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $factura->id,
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_nombre' => 'Camisa',
        'valor_unitario' => 10000,
        'cantidad' => 3,
        'subtotal' => 30000,
    ]);

    $this->actingAs($usuario)
        ->post(route('produccion.store'), [
            'fecha' => now()->toDateString(),
            'items' => [
                ['prenda_id' => $prenda->id, 'cantidad' => 3],
            ],
        ])
        ->assertRedirect(route('produccion.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('producciones', [
        'user_id' => $usuario->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 3,
        'cantidad_validada' => 3,
        'total' => 21000,
        'total_validado' => 21000,
        'estado_validacion' => 'validado',
    ]);
});

it('shows washer garments from collector catalog while preserving washer values', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Lavado',
        'precio' => 7000,
        'activo' => true,
    ]);

    Prenda::create([
        'nombre' => 'Prenda fuera de recolector',
        'tipo' => 'Lavado',
        'precio' => 5000,
        'activo' => true,
    ]);

    RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Lavado',
        'precio' => 12000,
        'activo' => true,
    ]);

    RecolectorPrenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Lavado',
        'precio' => 15000,
        'activo' => true,
    ]);

    $this->actingAs($usuario)
        ->get(route('produccion.index'))
        ->assertOk()
        ->assertSee('Camisa')
        ->assertSee('Pantalon')
        ->assertSee('$ 7.000')
        ->assertDontSee('Prenda fuera de recolector');

    $camisa = Prenda::where('nombre', 'Camisa')->where('tipo', 'Lavado')->first();
    $pantalon = Prenda::where('nombre', 'Pantalon')->where('tipo', 'Lavado')->first();

    expect((float) $camisa->precio)->toBe(7000.0)
        ->and((float) $pantalon->precio)->toBe(0.0)
        ->and(Prenda::where('nombre', 'Prenda fuera de recolector')->first()->activo)->toBeFalse();
});

it('flags surplus manual production and only pays validated garments', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Sobrante',
        'celular' => '3001234567',
        'direccion' => 'Calle 12',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Unidad',
        'precio' => 8000,
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Unidad',
        'precio' => 12000,
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 102,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 2,
        'total' => 24000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $factura->id,
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_nombre' => 'Pantalon',
        'valor_unitario' => 12000,
        'cantidad' => 2,
        'subtotal' => 24000,
    ]);

    $this->actingAs($usuario)
        ->post(route('produccion.store'), [
            'fecha' => now()->toDateString(),
            'items' => [
                ['prenda_id' => $prenda->id, 'cantidad' => 5],
            ],
        ])
        ->assertRedirect(route('produccion.index'));

    $produccion = Produccion::first();

    expect($produccion->cantidad)->toBe(5)
        ->and($produccion->cantidad_validada)->toBe(2)
        ->and((float) $produccion->total_validado)->toBe(16000.0)
        ->and($produccion->estado_validacion)->toBe('incongruente');

    $this->assertDatabaseHas('incongruencias_produccion', [
        'produccion_id' => $produccion->id,
        'tipo' => 'sobrante',
        'diferencia' => 3,
        'estado' => 'pendiente',
    ]);
});

it('allows admins to approve surplus production for payroll', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Blusa',
        'tipo' => 'Unidad',
        'precio' => 9000,
        'activo' => true,
    ]);

    $produccion = Produccion::create([
        'user_id' => $usuario->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 4,
        'cantidad_validada' => 1,
        'total' => 36000,
        'total_validado' => 9000,
        'fecha' => now()->toDateString(),
        'estado_validacion' => 'incongruente',
    ]);

    $incongruencia = IncongruenciaProduccion::create([
        'produccion_id' => $produccion->id,
        'user_id' => $usuario->id,
        'prenda_id' => $prenda->id,
        'fecha' => now()->toDateString(),
        'prenda_nombre' => 'Blusa',
        'tipo' => 'sobrante',
        'cantidad_recibida' => 1,
        'cantidad_reportada' => 4,
        'diferencia' => 3,
        'detalle' => 'Sobrante aprobado en prueba.',
        'estado' => 'pendiente',
        'detectada_en' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.incongruencias-produccion.aprobar', $incongruencia))
        ->assertRedirect();

    expect($produccion->fresh()->estado_validacion)->toBe('aprobado')
        ->and($produccion->fresh()->cantidad_validada)->toBe(4)
        ->and((float) $produccion->fresh()->total_validado)->toBe(36000.0)
        ->and($incongruencia->fresh()->estado)->toBe('aprobada');
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
        ->assertSee('Camisa')
        ->assertSee('Registro manual del dia')
        ->assertDontSee('Cliente Lavado')
        ->assertDontSee('Calle 12')
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

it('shows advanced order workflow only when admin enables it', function () {
    SystemSetting::setValue('produccion_interfaz_lavandero', 'avanzada');

    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Avanzado',
        'celular' => '3001234567',
        'direccion' => 'Calle 12',
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 10000,
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 321,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 2,
        'total' => 20000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolectorDetalle::create([
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
        ->assertSee('#000321')
        ->assertSee('Guardar prendas lavadas')
        ->assertDontSee('Registro manual del dia');
});

it('creates missing washer garments from collector list without using collector prices', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Sin Precio',
        'celular' => '3001234567',
        'direccion' => 'Calle 99',
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Chaqueta especial',
        'tipo' => 'Unidad',
        'precio' => 25000,
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 456,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 1,
        'total' => 25000,
        'estado_factura' => 'pendiente',
    ]);

    $detalle = FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $factura->id,
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_nombre' => 'Chaqueta especial',
        'valor_unitario' => 25000,
        'cantidad' => 1,
        'subtotal' => 25000,
    ]);

    $this->actingAs($usuario)
        ->patch(route('produccion.ordenes.lavado', $factura), [
            'detalles' => [$detalle->id],
        ])
        ->assertRedirect(route('produccion.index'))
        ->assertSessionHas('success');

    $prendaLavandero = Prenda::where('nombre', 'Chaqueta especial')->first();

    expect($prendaLavandero)->not->toBeNull()
        ->and((float) $prendaLavandero->precio)->toBe(0.0)
        ->and(Produccion::count())->toBe(1);

    $detalle->refresh();

    expect($detalle->lavado_por)->toBe($usuario->id)
        ->and($detalle->lavado_en)->not->toBeNull()
        ->and($detalle->produccion_id)->not->toBeNull();

    $this->assertDatabaseHas('producciones', [
        'id' => $detalle->produccion_id,
        'user_id' => $usuario->id,
        'prenda_id' => $prendaLavandero->id,
        'cantidad' => 1,
        'total' => 0,
        'total_validado' => 0,
    ]);
});

it('uses explicit collector-to-washer garment equivalences when names differ', function () {
    $usuario = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Equivalencia',
        'celular' => '3001234567',
        'direccion' => 'Calle 80',
        'activo' => true,
    ]);

    $prendaProduccion = Prenda::create([
        'nombre' => 'Abrigo',
        'tipo' => 'Unidad',
        'precio' => 22000,
        'activo' => true,
    ]);

    $prendaRecolector = RecolectorPrenda::create([
        'nombre' => 'Abrigo o gabardina',
        'tipo' => 'Lavado',
        'precio' => 35000,
        'activo' => true,
    ]);

    PrendaEquivalencia::create([
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_id' => $prendaProduccion->id,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 789,
        'recolector_id' => $recolector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDay()->toDateString(),
        'total_prendas' => 1,
        'total' => 35000,
        'estado_factura' => 'pendiente',
    ]);

    $detalle = FacturaRecolectorDetalle::create([
        'factura_recolector_id' => $factura->id,
        'recolector_prenda_id' => $prendaRecolector->id,
        'prenda_nombre' => 'Abrigo o gabardina',
        'valor_unitario' => 35000,
        'cantidad' => 1,
        'subtotal' => 35000,
    ]);

    $this->actingAs($usuario)
        ->patch(route('produccion.ordenes.lavado', $factura), [
            'detalles' => [$detalle->id],
        ])
        ->assertRedirect(route('produccion.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('producciones', [
        'user_id' => $usuario->id,
        'prenda_id' => $prendaProduccion->id,
        'cantidad' => 1,
        'total' => 22000,
        'total_validado' => 22000,
    ]);
});
