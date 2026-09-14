<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\PuntualRecordatorio;
use App\Models\User;
use App\Services\PuntualService;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;

function ordenPuntual(User $user, array $attributes = []): FacturaRecolector
{
    $cliente = Cliente::create(['nombre' => 'Cliente '.$user->id, 'activo' => true, 'recolector_id' => $user->id]);

    return FacturaRecolector::create(array_merge([
        'recolector_id' => $user->id, 'cliente_id' => $cliente->id,
        'numero_orden' => 100000 + $cliente->id,
        'fecha_ingreso' => now(), 'fecha_entrega' => now('America/Bogota')->addDay()->toDateString(),
        'total' => 12000, 'total_prendas' => 2, 'estado_factura' => 'pendiente',
    ], $attributes));
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-14 15:00:00', 'UTC'));
});

it('restricts all-record visibility to admin including programador restrictions', function () {
    $owner = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $other = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $own = ordenPuntual($owner);
    $foreign = ordenPuntual($other);
    foreach (['admin', 'programador', 'usuario'] as $rol) {
        $user = User::factory()->create(['rol' => $rol, 'activo' => true]);
        $response = $this->actingAs($user)->get(route('puntual.index'))->assertOk();
        $rol === 'admin'
            ? $response->assertSee('Orden #'.$own->numero_orden)->assertSee('Orden #'.$foreign->numero_orden)
            : $response->assertDontSee('Orden #'.$own->numero_orden)->assertDontSee('Orden #'.$foreign->numero_orden);
        if ($rol !== 'admin') {
            $this->patch(route('puntual.entregar', $foreign))->assertForbidden();
        }
    }
    $this->actingAs($owner)->get(route('puntual.index'))->assertSee('Orden #'.$own->numero_orden)->assertDontSee('Orden #'.$foreign->numero_orden);
    $this->get(route('puntual.index', ['orden' => $foreign->id]))->assertDontSee('Orden #'.$foreign->numero_orden);
    $this->patch(route('puntual.entregar', $foreign))->assertForbidden();
});

it('generates one reminder per delivery day and eve even for paid orders', function () {
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = ordenPuntual($user, ['estado_factura' => 'pagado']);
    $service = app(PuntualService::class);
    $service->generar();
    $service->generar();
    expect(PuntualRecordatorio::count())->toBe(1);
    expect(PuntualRecordatorio::first()->tipo)->toBe('manana');
    $this->travel(1)->days();
    $service->generar();
    $service->generar();
    expect(PuntualRecordatorio::count())->toBe(2);
    expect(PuntualRecordatorio::where('tipo', 'hoy')->count())->toBe(1);
    $this->actingAs($user)->patch(route('puntual.entregar', $order))->assertRedirect();
    expect($order->fresh()->entregado_en)->not->toBeNull();
    expect($order->fresh()->estado_factura)->toBe('pagado');
    expect(PuntualRecordatorio::vigentes()->count())->toBe(0);
});

it('uses Colombia dates around UTC midnight and ignores cancelled or inactive orders', function () {
    $this->travelTo(Carbon::parse('2026-09-15 02:00:00', 'UTC'));
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    ordenPuntual($user, ['fecha_entrega' => '2026-09-15']);
    ordenPuntual($user, ['fecha_entrega' => '2026-09-15', 'estado_factura' => 'cancelado']);
    $inactive = User::factory()->create(['rol' => 'recolector', 'activo' => false]);
    ordenPuntual($inactive, ['fecha_entrega' => '2026-09-15']);
    app(PuntualService::class)->generar();
    expect(PuntualRecordatorio::count())->toBe(1);
    expect(PuntualRecordatorio::first()->tipo)->toBe('manana');
});

it('invalidates reminders after rescheduling or reassignment and creates the new recipient reminder', function () {
    $owner = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $next = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $order = ordenPuntual($owner);
    $service = app(PuntualService::class);
    $service->generar();
    $order->update(['fecha_entrega' => '2026-09-20']);
    expect(PuntualRecordatorio::vigentes()->count())->toBe(0);
    $order->update(['fecha_entrega' => '2026-09-15', 'recolector_id' => $next->id]);
    $service->generar();
    expect(PuntualRecordatorio::vigentes()->count())->toBe(1);
    expect(PuntualRecordatorio::vigentes()->first()->user_id)->toBe($next->id);
});

it('protects read receipts and push endpoints and removes this device on logout', function () {
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $other = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    ordenPuntual($other);
    app(PuntualService::class)->generar();
    $this->actingAs($user)->patch(route('puntual.leer', PuntualRecordatorio::first()))->assertForbidden();
    $payload = ['endpoint' => 'https://127.0.0.1/internal', 'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('a', 22)]];
    $this->postJson(route('puntual.suscribir'), $payload)->assertUnprocessable();
    $payload['endpoint'] = 'https://fcm.googleapis.com/fcm/send/test';
    $this->postJson(route('puntual.suscribir'), $payload)->assertOk();
    expect(DB::table('puntual_suscripciones')->count())->toBe(1);
    $this->post(route('logout'))->assertRedirect();
    expect(DB::table('puntual_suscripciones')->count())->toBe(0);
});

it('requires active authentication', function () {
    $this->get(route('puntual.index'))->assertRedirect(route('login'));
    $user = User::factory()->create(['rol' => 'admin', 'activo' => false]);
    $this->actingAs($user)->get(route('puntual.index'))->assertStatus(302);
});

it('sends due push once and does not resend on repeated scheduler runs', function () {
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    ordenPuntual($user);
    DB::table('puntual_suscripciones')->insert([
        'user_id' => $user->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        'endpoint_hash' => hash('sha256', 'test'), 'public_key' => str_repeat('a', 87), 'auth_token' => str_repeat('a', 22),
    ]);
    config(['puntual.vapid_public_key' => 'public', 'puntual.vapid_private_key' => 'private']);
    $report = new MessageSentReport(new Request('POST', 'https://fcm.googleapis.com'), new Response(201));
    $push = Mockery::mock(WebPush::class);
    $push->shouldReceive('sendOneNotification')->once()->withArgs(function ($subscription, $payload) {
        $data = json_decode($payload, true);

        return str_contains($data['title'], 'mañana') && str_starts_with($data['url'], '/puntual?orden=');
    })->andReturn($report);
    $this->app->bind(WebPush::class, fn () => $push);
    expect(app(PuntualService::class)->enviar())->toBe(1);
    expect(app(PuntualService::class)->enviar())->toBe(0);
    expect(PuntualRecordatorio::first()->push_en)->not->toBeNull();
});

it('removes expired subscriptions and retries temporary push failures', function () {
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    ordenPuntual($user);
    foreach (['expired', 'retry'] as $endpoint) {
        DB::table('puntual_suscripciones')->insert([
            'user_id' => $user->id, 'endpoint' => 'https://fcm.googleapis.com/'.$endpoint,
            'endpoint_hash' => hash('sha256', $endpoint), 'public_key' => str_repeat('a', 87), 'auth_token' => str_repeat('a', 22),
        ]);
    }
    config(['puntual.vapid_public_key' => 'public', 'puntual.vapid_private_key' => 'private']);
    $push = Mockery::mock(WebPush::class);
    $request = new Request('POST', 'https://fcm.googleapis.com');
    $push->shouldReceive('sendOneNotification')->times(3)->andReturn(
        new MessageSentReport($request, new Response(410), false),
        new MessageSentReport($request, new Response(503), false),
        new MessageSentReport($request, new Response(201)),
    );
    $this->app->bind(WebPush::class, fn () => $push);
    expect(app(PuntualService::class)->enviar())->toBe(0);
    expect(DB::table('puntual_suscripciones')->count())->toBe(1);
    expect(app(PuntualService::class)->enviar())->toBe(1);
});

it('does not reuse encrypted notification keys from another installation', function () {
    \App\Models\SystemSetting::setValue('puntual_vapid', 'unreadable-legacy-value');
    $keys = app(\App\Services\PuntualKeys::class);
    expect($keys->obtener())->toBeNull();
    $setting = 'puntual_vapid_'.hash('sha256', config('app.url').'|'.config('app.key'));
    \App\Models\SystemSetting::setValue($setting, \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['publicKey' => 'one', 'privateKey' => 'secret'])));
    expect($keys->obtener()['publicKey'])->toBe('one');
    config(['app.url' => 'https://another-installation.example']);
    expect($keys->obtener())->toBeNull();
});

it('keeps the page available when a stored push key cannot be decrypted', function () {
    $setting = 'puntual_vapid_'.hash('sha256', config('app.url').'|'.config('app.key'));
    \App\Models\SystemSetting::setValue($setting, 'unreadable-value');
    $user = User::factory()->create(['rol' => 'recolector', 'activo' => true]);
    $this->actingAs($user)->get(route('puntual.index'))->assertOk();
    expect(app(\App\Services\PuntualService::class)->enviar())->toBe(0);
});
