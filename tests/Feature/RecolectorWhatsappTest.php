<?php

use App\Models\Cliente;
use App\Models\FacturaRecolectorDetalle;
use App\Models\RecolectorPrenda;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('saves collector order and sends WhatsApp business message when requested', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [
                ['id' => 'wamid.test'],
            ],
        ]),
    ]);

    config([
        'services.whatsapp.enabled' => true,
        'services.whatsapp.api_version' => 'v20.0',
        'services.whatsapp.phone_number_id' => '123456789',
        'services.whatsapp.token' => 'test-token',
    ]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente WhatsApp',
        'nit_cedula' => '901123123',
        'celular' => '300 123 4567',
        'direccion' => 'Calle 1 #2-3',
        'activo' => true,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Cobija',
        'tipo' => 'Hogar',
        'precio' => 28000,
        'activo' => true,
    ]);

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'enviar_whatsapp' => '1',
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 2,
                    'precio_unitario' => 28000,
                    'colores' => ['Blanco', 'Azul'],
                ],
            ],
        ])
        ->assertRedirect(route('recolector.index'))
        ->assertSessionHas('success', 'Orden guardada y mensaje de WhatsApp enviado correctamente.');

    $this->assertDatabaseHas('facturas_recolector', [
        'cliente_id' => $cliente->id,
        'recolector_id' => $recolector->id,
        'celular' => '300 123 4567',
        'total_prendas' => 2,
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://graph.facebook.com/v20.0/123456789/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '573001234567'
            && str_contains($request['text']['body'], 'tu orden de lavanderia');
    });
});

it('saves collector order when WhatsApp business is not configured', function () {
    Http::fake();

    config(['services.whatsapp.enabled' => false]);

    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Sin Configuracion',
        'nit_cedula' => '901123124',
        'celular' => '3001234568',
        'direccion' => 'Calle 4 #5-6',
        'activo' => true,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Edredon',
        'tipo' => 'Hogar',
        'precio' => 45000,
        'activo' => true,
    ]);

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'enviar_whatsapp' => '1',
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 1,
                    'precio_unitario' => 45000,
                    'colores' => ['Gris'],
                ],
            ],
        ])
        ->assertRedirect(route('recolector.index'))
        ->assertSessionHas('success', 'Orden guardada correctamente.')
        ->assertSessionHas('error', 'La automatizacion de WhatsApp Business no esta habilitada.');

    $this->assertDatabaseHas('facturas_recolector', [
        'cliente_id' => $cliente->id,
        'recolector_id' => $recolector->id,
    ]);

    Http::assertNothingSent();
});

it('uses fixed garment price when collector is not allowed to edit prices', function () {
    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'puede_editar_precios' => false,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Precio Fijo',
        'celular' => '3001234568',
        'direccion' => 'Calle 10',
        'activo' => true,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Chaqueta',
        'tipo' => 'Unidad',
        'precio' => 50000,
        'activo' => true,
    ]);

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 2,
                    'precio_unitario' => 10000,
                    'colores' => ['Negro'],
                ],
            ],
        ])
        ->assertRedirect(route('recolector.index'));

    $detalle = FacturaRecolectorDetalle::firstOrFail();

    expect((float) $detalle->valor_unitario)->toBe(50000.0)
        ->and((float) $detalle->subtotal)->toBe(100000.0);
});

it('uses custom garment price when admin allows collector price editing', function () {
    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'puede_editar_precios' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Precio Especial',
        'celular' => '3001234569',
        'direccion' => 'Calle 11',
        'activo' => true,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Vestido',
        'tipo' => 'Unidad',
        'precio' => 40000,
        'activo' => true,
    ]);

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 2,
                    'precio_unitario' => 25000,
                    'colores' => ['Rojo'],
                ],
            ],
        ])
        ->assertRedirect(route('recolector.index'));

    $detalle = FacturaRecolectorDetalle::firstOrFail();

    expect((float) $detalle->valor_unitario)->toBe(25000.0)
        ->and((float) $detalle->subtotal)->toBe(50000.0);
});

it('requires at least one garment color and stores multiple colors per item', function () {
    $recolector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente Colores',
        'celular' => '3001234570',
        'direccion' => 'Calle 12',
        'activo' => true,
    ]);

    $prenda = RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 12000,
        'activo' => true,
    ]);

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 1,
                    'precio_unitario' => 12000,
                ],
            ],
        ])
        ->assertSessionHasErrors('items');

    $this->actingAs($recolector)
        ->post(route('recolector.facturas.store'), [
            'cliente_id' => $cliente->id,
            'fecha_entrega' => now()->addDay()->toDateString(),
            'items' => [
                [
                    'selected' => '1',
                    'prenda_id' => $prenda->id,
                    'cantidad' => 1,
                    'precio_unitario' => 12000,
                    'colores' => ['Blanco', 'Azul'],
                ],
            ],
        ])
        ->assertRedirect(route('recolector.index'));

    $this->assertDatabaseHas('factura_recolector_detalles', [
        'recolector_prenda_id' => $prenda->id,
        'color_prenda' => 'Blanco, Azul',
    ]);
});
