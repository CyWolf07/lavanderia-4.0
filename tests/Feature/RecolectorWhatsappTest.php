<?php

use App\Models\Cliente;
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
