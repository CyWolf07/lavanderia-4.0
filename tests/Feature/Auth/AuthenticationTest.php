<?php

use App\Models\EnterpriseAccessControl;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EnterpriseCodeService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

test('enterprise code screen is shown before login', function () {
    $response = $this->get('/login');

    $response->assertRedirect(route('enterprise-code.create'));
});

test('login screen can be rendered after enterprise code validation', function () {
    $response = $this->withSession(['enterprise_code_validated' => true])->get('/login');

    $response->assertStatus(200);
});

test('enterprise code is static until programmer regenerates it', function () {
    $codes = app(EnterpriseCodeService::class);

    $currentCode = $codes->current();

    expect($codes->current())->toBe($currentCode);
    $this->assertDatabaseMissing('system_settings', [
        'key' => 'enterprise_code',
    ]);

    $newCode = $codes->regenerate();

    expect($newCode)->not->toBe($currentCode)
        ->and($codes->current())->toBe($newCode);
    $this->assertDatabaseHas('system_settings', [
        'key' => 'enterprise_code',
        'value' => $newCode,
    ]);
});

test('enterprise code validates device before showing login', function () {
    SystemSetting::create([
        'key' => 'enterprise_code',
        'value' => 'Abc123!@#',
    ]);

    $response = $this->post(route('enterprise-code.store'), [
        'codigo_empresarial' => 'Abc123!@#',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertTrue(session('enterprise_code_validated'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['enterprise_code_validated' => true])->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate using formatted cedula', function () {
    $user = User::factory()->create([
        'cedula' => '1000000003',
        'activo' => true,
    ]);

    $response = $this->withSession(['enterprise_code_validated' => true])->post('/login', [
        'login' => '1.000.000.003',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->withSession(['enterprise_code_validated' => true])->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('device is blocked after three failed login attempts', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->withCookie('lavanderia_device_id', 'device-login-test')
            ->withSession(['enterprise_code_validated' => true])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
    }

    $this->assertDatabaseHas('enterprise_access_controls', [
        'device_id' => 'device-login-test',
        'area' => EnterpriseAccessControl::AREA_LOGIN,
        'attempts' => 3,
    ]);

    expect(EnterpriseAccessControl::where('device_id', 'device-login-test')->first()->locked_at)->not->toBeNull();
});

test('device is blocked after three failed enterprise code attempts', function () {
    SystemSetting::create([
        'key' => 'enterprise_code',
        'value' => 'Abc123!@#',
    ]);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->withCookie('lavanderia_device_id', 'device-code-test')
            ->post(route('enterprise-code.store'), [
                'codigo_empresarial' => 'incorrecto',
            ]);
    }

    $this->assertDatabaseHas('enterprise_access_controls', [
        'device_id' => 'device-code-test',
        'area' => EnterpriseAccessControl::AREA_CODE,
        'attempts' => 3,
    ]);

    expect(EnterpriseAccessControl::where('device_id', 'device-code-test')->first()->locked_at)->not->toBeNull();
});

test('programmer can regenerate enterprise code and is sent back to validation', function () {
    $programmer = User::factory()->create([
        'rol' => 'programador',
        'activo' => true,
    ]);

    SystemSetting::create([
        'key' => 'enterprise_code',
        'value' => 'Old123!@#',
    ]);

    $response = $this->actingAs($programmer)
        ->withSession(['enterprise_code_validated' => true])
        ->post(route('admin.codigo-empresarial.regenerate'));

    $response->assertRedirect(route('enterprise-code.create'));
    $this->assertGuest();

    $newCode = SystemSetting::where('key', 'enterprise_code')->value('value');

    expect($newCode)->not->toBe('Old123!@#');

    $this->post(route('enterprise-code.store'), [
        'codigo_empresarial' => 'Old123!@#',
    ])->assertSessionHasErrors('codigo_empresarial');

    $this->post(route('enterprise-code.store'), [
        'codigo_empresarial' => $newCode,
    ])->assertRedirect(route('login'));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('logout does not fail with csrf page expired for any role', function (string $rol) {
    $this->withMiddleware(ValidateCsrfToken::class);

    $user = User::factory()->create([
        'rol' => $rol,
        'activo' => true,
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
})->with(['admin', 'programador', 'usuario', 'recolector']);
