<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\HistorialProduccion;
use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;
use App\Models\Rol;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('allows administrators to access production from the admin panel', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ingresar a produccion');

    $this->actingAs($admin)
        ->get(route('produccion.index'))
        ->assertOk();
});

it('lets admins choose the washer interface mode', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.produccion.interfaz'), [
            'modo' => 'avanzada',
        ])
        ->assertRedirect();

    expect(SystemSetting::getValue('produccion_interfaz_lavandero'))->toBe('avanzada');
});

it('does not count active production from previous quincenas in current dashboard totals', function () {
    $this->travelTo(Carbon::parse('2026-07-20 10:00:00'));

    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Lavado',
        'precio' => 7000,
        'activo' => true,
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 2,
        'cantidad_validada' => 2,
        'total' => 14000,
        'total_validado' => 14000,
        'fecha' => '2026-07-10',
        'estado_validacion' => 'validado',
    ]);

    $actual = Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 3,
        'cantidad_validada' => 3,
        'total' => 21000,
        'total_validado' => 21000,
        'fecha' => '2026-07-20',
        'estado_validacion' => 'validado',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect((float) $response->viewData('pagoUsuarios'))->toBe(21000.0)
        ->and($response->viewData('ultimasProducciones')->pluck('id')->all())->toBe([$actual->id]);
});

it('closes active washer records into the quincena that matches each production date', function () {
    $this->travelTo(Carbon::parse('2026-07-20 10:00:00'));

    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Lavado',
        'precio' => 8000,
        'activo' => true,
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 1,
        'cantidad_validada' => 1,
        'total' => 8000,
        'total_validado' => 8000,
        'fecha' => '2026-07-10',
        'estado_validacion' => 'validado',
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 2,
        'cantidad_validada' => 2,
        'total' => 16000,
        'total_validado' => 16000,
        'fecha' => '2026-07-20',
        'estado_validacion' => 'validado',
    ]);

    $this->actingAs($admin)
        ->post(route('produccion.cerrar'))
        ->assertRedirect(route('admin.reportes.periodo', [
            'periodo' => '2026/07/QUINCENA2',
            'imprimir' => 1,
        ]));

    expect(Produccion::count())->toBe(0);

    $periodos = HistorialProduccion::query()
        ->orderBy('periodo')
        ->pluck('periodo')
        ->all();

    expect($periodos)->toBe([
        '2026/07/QUINCENA1',
        '2026/07/QUINCENA2',
    ]);
});

it('allows administrators identified by rol id to access the admin panel', function () {
    $rolAdmin = Rol::firstOrCreate([
        'nombre' => 'Admin',
    ], [
        'descripcion' => 'Administrador',
    ]);

    $admin = User::factory()->create([
        'rol' => '',
        'rol_id' => $rolAdmin->id,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('shows active admin totals from production and collector invoices', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Unidad',
        'precio' => 10000,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente prueba',
        'celular' => '3001234567',
        'direccion' => 'Calle 1',
        'barrio' => 'Centro',
        'activo' => true,
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 2,
        'total' => 20000,
        'fecha' => now()->toDateString(),
    ]);

    FacturaRecolector::create([
        'numero_orden' => 1,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 3,
        'total' => 30000,
        'estado_factura' => 'pendiente',
    ]);

    FacturaRecolector::create([
        'numero_orden' => 2,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 5,
        'total' => 70000,
        'estado_factura' => 'cancelado',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('Lavanderos registrados')
        ->assertSeeText('3')
        ->assertSeeText('Registros activos')
        ->assertSeeText('2')
        ->assertSeeText('Ingreso activo')
        ->assertSeeText('$ 30.000')
        ->assertSeeText('Pago Lavanderos')
        ->assertSeeText('$ 20.000')
        ->assertDontSeeText('$ 120.000');
});

it('shows washer payments from reported totals in admin dashboard and printable reports', function () {
    $this->travelTo(Carbon::parse('2026-07-20 10:00:00'));

    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
        'name' => 'Lavandero Registro',
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Chaqueta',
        'tipo' => 'Lavado',
        'precio' => 9000,
        'activo' => true,
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 4,
        'cantidad_validada' => 1,
        'total' => 36000,
        'total_validado' => 9000,
        'fecha' => '2026-07-20',
        'estado_validacion' => 'incongruente',
    ]);

    $dashboard = $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect((float) $dashboard->viewData('pagoUsuarios'))->toBe(36000.0)
        ->and((float) $dashboard->viewData('produccionUsuariosPorDia')->first()['total'])->toBe(36000.0);

    $reporte = $this->actingAs($admin)
        ->get(route('admin.reportes.impresion', ['tipo_reporte' => 'general']))
        ->assertOk();

    expect((float) $reporte->viewData('totalGeneralUsuarios'))->toBe(36000.0)
        ->and((int) $reporte->viewData('totalPrendasUsuarios'))->toBe(4);
});

it('closes washer records using reported totals instead of validation totals', function () {
    $this->travelTo(Carbon::parse('2026-07-20 10:00:00'));

    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Chaqueta',
        'tipo' => 'Lavado',
        'precio' => 9000,
        'activo' => true,
    ]);

    Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 4,
        'cantidad_validada' => 1,
        'total' => 36000,
        'total_validado' => 9000,
        'fecha' => '2026-07-20',
        'estado_validacion' => 'incongruente',
    ]);

    $this->actingAs($admin)
        ->post(route('produccion.cerrar'))
        ->assertRedirect(route('admin.reportes.periodo', [
            'periodo' => '2026/07/QUINCENA2',
            'imprimir' => 1,
        ]));

    $historial = HistorialProduccion::firstOrFail();

    expect((int) $historial->cantidad)->toBe(4)
        ->and((float) $historial->total)->toBe(36000.0)
        ->and((float) $historial->precio_unitario)->toBe(9000.0);
});

it('shows reports for collector-only periods with detailed expenses', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'name' => 'Recolector Prueba',
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente informe',
        'celular' => '3001112233',
        'direccion' => 'Calle 9',
        'barrio' => 'Centro',
        'activo' => true,
    ]);

    FacturaRecolector::create([
        'numero_orden' => 77,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'total_prendas' => 4,
        'total' => 69000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
        'quincena_pago' => '2026/07/QUINCENA1',
        'quincena_origen' => '2026/07/QUINCENA1',
    ]);

    Gasto::create([
        'user_id' => $admin->id,
        'concepto' => 'Compra de detergente',
        'monto' => 12000,
        'fecha' => '2026-07-05',
        'periodo' => '2026/07/QUINCENA1',
        'anio' => 2026,
        'mes' => 7,
        'quincena' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reportes.periodo', '2026/07/QUINCENA1'))
        ->assertOk()
        ->assertSeeText('Recolector Prueba')
        ->assertSeeText('$ 69.000')
        ->assertSeeText('Gastos especificos')
        ->assertSeeText('Compra de detergente')
        ->assertSeeText('$ 12.000');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('2026/07/QUINCENA1')
        ->assertSeeText('Solo recolectores')
        ->assertSeeText('Ver informe');
});

it('does not overwrite edited base admin credentials when database seeder runs again', function () {
    $rolAdmin = Rol::firstOrCreate([
        'nombre' => 'Admin',
    ], [
        'descripcion' => 'Administrador',
    ]);

    $admin = User::factory()->create([
        'name' => 'Admin Editado',
        'email' => 'admin.editado@lavanderia.com',
        'cedula' => '1999999999',
        'rol' => 'admin',
        'rol_id' => $rolAdmin->id,
        'password' => Hash::make('clave-editada'),
        'activo' => true,
    ]);

    $this->seed(DatabaseSeeder::class);

    expect($admin->fresh()->email)->toBe('admin.editado@lavanderia.com')
        ->and(Hash::check('clave-editada', $admin->fresh()->password))->toBeTrue();

    $this->assertDatabaseMissing('users', [
        'email' => 'admin@lavanderia.com',
    ]);
});

it('keeps collector garment catalog unique and shared', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    RecolectorPrenda::create([
        'nombre' => 'Camisa',
        'tipo' => 'Lavado',
        'precio' => 9000,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('recolector-prendas.store'), [
            'nombre' => ' camisa ',
            'tipo' => ' lavado ',
            'precio' => 9500,
            'activo' => true,
        ])
        ->assertSessionHasErrors('nombre');

    expect(RecolectorPrenda::count())->toBe(1);
});

it('allows privileged users to create washer garments outside the collector catalog', function (string $role) {
    $user = User::factory()->create([
        'rol' => $role,
        'activo' => true,
    ]);

    $this->actingAs($user)
        ->get(route('prendas.index'))
        ->assertOk()
        ->assertSeeText('Nueva prenda lavandero');

    $this->actingAs($user)
        ->post(route('prendas.store'), [
            'nombre' => 'Chaqueta Premium',
            'tipo' => 'Lavado especial',
            'precio' => 14000,
        ])
        ->assertRedirect(route('prendas.index'));

    $this->assertDatabaseHas('prendas', [
        'nombre' => 'Chaqueta Premium',
        'tipo' => 'Lavado especial',
        'precio' => 14000,
        'activo' => true,
    ]);
})->with(['admin', 'programador']);

it('creates collector garments as active even when an inactive value is submitted', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('recolector-prendas.store'), [
            'nombre' => 'Tapete Activo',
            'tipo' => 'Lavado',
            'precio' => 22000,
            'activo' => false,
        ])
        ->assertRedirect(route('recolector-prendas.index'));

    $this->assertDatabaseHas('recolector_prendas', [
        'nombre' => 'Tapete Activo',
        'activo' => true,
    ]);
});

it('lets privileged users enable disable and delete garments from washer management', function (string $role) {
    $user = User::factory()->create([
        'rol' => $role,
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Blusa Estado',
        'tipo' => 'Lavado',
        'precio' => 9000,
        'activo' => true,
    ]);

    $recolectorPrenda = RecolectorPrenda::create([
        'nombre' => 'Blusa Estado',
        'tipo' => 'Lavado',
        'precio' => 20000,
        'activo' => true,
    ]);

    PrendaEquivalencia::create([
        'recolector_prenda_id' => $recolectorPrenda->id,
        'prenda_id' => $prenda->id,
    ]);

    $this->actingAs($user)
        ->get(route('prendas.index'))
        ->assertOk()
        ->assertSeeText('Habilitar')
        ->assertSeeText('Deshabilitar')
        ->assertSeeText('Borrar');

    $this->actingAs($user)
        ->patch(route('prendas.inhabilitar', $prenda))
        ->assertRedirect();

    expect($prenda->fresh()->activo)->toBeFalse()
        ->and($recolectorPrenda->fresh()->activo)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('prendas.habilitar', $prenda))
        ->assertRedirect();

    expect($prenda->fresh()->activo)->toBeTrue()
        ->and($recolectorPrenda->fresh()->activo)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('prendas.destroy', $prenda))
        ->assertRedirect();

    $this->assertDatabaseMissing('prendas', [
        'id' => $prenda->id,
    ]);

    $this->assertDatabaseMissing('recolector_prendas', [
        'id' => $recolectorPrenda->id,
    ]);
})->with(['admin', 'programador']);

it('does not allow collectors to enable or disable catalog garments', function () {
    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Blusa Protegida',
        'tipo' => 'Lavado',
        'precio' => 9000,
        'activo' => false,
    ]);

    $recolectorPrenda = RecolectorPrenda::create([
        'nombre' => 'Cobija Protegida',
        'tipo' => 'Lavado',
        'precio' => 20000,
        'activo' => false,
    ]);

    $this->actingAs($collector)
        ->patch(route('prendas.habilitar', $prenda))
        ->assertForbidden();

    $this->actingAs($collector)
        ->patch(route('prendas.inhabilitar', $prenda))
        ->assertForbidden();

    $this->actingAs($collector)
        ->patch(route('recolector-prendas.habilitar', $recolectorPrenda))
        ->assertForbidden();

    $this->actingAs($collector)
        ->patch(route('recolector-prendas.inhabilitar', $recolectorPrenda))
        ->assertForbidden();

    expect($prenda->fresh()->activo)->toBeFalse();
    expect($recolectorPrenda->fresh()->activo)->toBeFalse();
});

it('does not change garment status when editing catalog data', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Pantalon Inactivo',
        'tipo' => 'Lavado',
        'precio' => 12000,
        'activo' => false,
    ]);

    $recolectorPrenda = RecolectorPrenda::create([
        'nombre' => 'Edredon Inactivo',
        'tipo' => 'Lavado',
        'precio' => 24000,
        'activo' => false,
    ]);

    $this->actingAs($admin)
        ->put(route('prendas.update', $prenda), [
            'nombre' => 'Pantalon Editado',
            'tipo' => 'Lavado premium',
            'precio' => 13000,
        ])
        ->assertRedirect(route('prendas.index'));

    expect($prenda->fresh()->activo)->toBeFalse();

    $this->actingAs($admin)
        ->put(route('recolector-prendas.update', $recolectorPrenda), [
            'nombre' => 'Edredon Editado',
            'tipo' => 'Lavado premium',
            'precio' => 25000,
        ])
        ->assertRedirect(route('recolector-prendas.index'));

    expect($recolectorPrenda->fresh()->activo)->toBeFalse();
});

it('rejects high washer prices for regular garments', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Abrigo',
        'tipo' => 'Lavado seco',
        'precio' => 2400,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('prendas.update', $prenda), [
            'precio' => 45000,
        ])
        ->assertSessionHasErrors('precio');

    expect((float) $prenda->fresh()->precio)->toBe(2400.0);
});

it('allows high washer prices for large garments', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'MUEBLE',
        'tipo' => 'Lavado',
        'precio' => 7000,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('prendas.update', $prenda), [
            'precio' => 25000,
        ])
        ->assertRedirect(route('prendas.index'));

    expect((float) $prenda->fresh()->precio)->toBe(25000.0);
});

it('shows collector invoice status only for the authenticated collector', function () {
    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $otherCollector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente recolector',
        'celular' => '3002223344',
        'direccion' => 'Calle 7',
        'barrio' => 'Centro',
        'activo' => true,
    ]);

    foreach (['pendiente', 'pagado', 'cancelado'] as $index => $estado) {
        FacturaRecolector::create([
            'numero_orden' => 200 + $index,
            'recolector_id' => $collector->id,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now(),
            'fecha_entrega' => now()->addDays(2)->toDateString(),
            'total_prendas' => 1,
            'total' => 10000,
            'estado_factura' => $estado,
            'metodo_pago' => $estado === 'pagado' ? 'efectivo' : null,
        ]);
    }

    FacturaRecolector::create([
        'numero_orden' => 300,
        'recolector_id' => $otherCollector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(2)->toDateString(),
        'total_prendas' => 1,
        'total' => 10000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
    ]);

    $response = $this->actingAs($collector)
        ->get(route('recolector.index'))
        ->assertOk()
        ->assertSeeText('Pagadas')
        ->assertSeeText('Canceladas');

    $resumen = $response->viewData('facturaStatusResumen');

    expect($resumen->get('pendiente')->cantidad)->toBe(1)
        ->and($resumen->get('pagado')->cantidad)->toBe(1)
        ->and($resumen->get('cancelado')->cantidad)->toBe(1);

    expect($response->viewData('facturas')->pluck('numero_orden')->all())
        ->toContain(200, 201, 202)
        ->not->toContain(300);
});

it('counts paid and canceled invoices in the active quincena status summary', function () {
    $this->travelTo(Carbon::parse('2026-07-10 10:00:00'));

    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente estatus',
        'celular' => '3004445566',
        'direccion' => 'Calle 10',
        'barrio' => 'Centro',
        'activo' => true,
    ]);

    FacturaRecolector::create([
        'numero_orden' => 101,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-06-20 08:00:00',
        'fecha_entrega' => '2026-07-12',
        'total_prendas' => 2,
        'total' => 25000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
        'quincena_pago' => '2026/07/QUINCENA1',
        'quincena_origen' => '2026/06/QUINCENA2',
    ]);

    FacturaRecolector::create([
        'numero_orden' => 102,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-06-22 08:00:00',
        'fecha_entrega' => '2026-07-12',
        'total_prendas' => 1,
        'total' => 15000,
        'estado_factura' => 'cancelado',
        'quincena_origen' => '2026/06/QUINCENA2',
    ]);

    FacturaRecolector::create([
        'numero_orden' => 103,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-07-05 08:00:00',
        'fecha_entrega' => '2026-07-12',
        'total_prendas' => 3,
        'total' => 30000,
        'estado_factura' => 'pendiente',
        'quincena_origen' => '2026/07/QUINCENA1',
    ]);

    FacturaRecolector::create([
        'numero_orden' => 104,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-06-10 08:00:00',
        'fecha_entrega' => '2026-06-12',
        'total_prendas' => 2,
        'total' => 22000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
        'quincena_pago' => '2026/06/QUINCENA2',
        'quincena_origen' => '2026/06/QUINCENA1',
        'updated_at' => '2026-06-20 10:00:00',
        'created_at' => '2026-06-10 10:00:00',
    ]);

    $canceladaAnterior = FacturaRecolector::create([
        'numero_orden' => 105,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => '2026-06-10 08:00:00',
        'fecha_entrega' => '2026-06-12',
        'total_prendas' => 2,
        'total' => 22000,
        'estado_factura' => 'cancelado',
        'quincena_origen' => '2026/06/QUINCENA1',
    ]);
    $canceladaAnterior->timestamps = false;
    $canceladaAnterior->forceFill([
        'updated_at' => '2026-06-20 10:00:00',
        'created_at' => '2026-06-10 10:00:00',
    ])->save();

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    $resumen = $response->viewData('facturaStatusResumen');
    expect((int) $resumen->get('pagado')->cantidad)->toBe(1);
    expect((int) $resumen->get('cancelado')->cantidad)->toBe(1);
    expect((int) $resumen->get('pendiente')->cantidad)->toBe(1);

    $ordenes = $response->viewData('ultimasFacturasRecolector')->pluck('numero_orden')->all();
    expect($ordenes)->toContain(101, 102, 103);
    expect($ordenes)->not->toContain(104, 105);
});

it('allows programmers to delete paid collector invoices from the status table', function () {
    $programmer = User::factory()->create([
        'rol' => 'programador',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Cliente pago',
        'celular' => '3009876543',
        'direccion' => 'Calle 2',
        'barrio' => 'Sur',
        'activo' => true,
    ]);

    $factura = FacturaRecolector::create([
        'numero_orden' => 9,
        'recolector_id' => $collector->id,
        'cliente_id' => $cliente->id,
        'fecha_ingreso' => now(),
        'fecha_entrega' => now()->addDays(3)->toDateString(),
        'total_prendas' => 1,
        'total' => 15000,
        'estado_factura' => 'pagado',
        'metodo_pago' => 'efectivo',
    ]);

    $this->actingAs($programmer)
        ->delete(route('admin.facturas-recolector.destroy', $factura))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseMissing('facturas_recolector', [
        'id' => $factura->id,
    ]);

    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $programmer->id,
        'auditable_type' => FacturaRecolector::class,
        'auditable_id' => $factura->id,
        'action' => 'factura_recolector.deleted',
    ]);
});

it('allows admins to toggle collector price editing permission', function () {
    $admin = User::factory()->create([
        'rol' => 'admin',
        'activo' => true,
    ]);

    $collector = User::factory()->create([
        'rol' => 'recolector',
        'activo' => true,
        'puede_editar_precios' => false,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.usuarios.toggle-precios', $collector))
        ->assertRedirect();

    expect($collector->fresh()->puede_editar_precios)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.usuarios.toggle-precios', $collector))
        ->assertRedirect();

    expect($collector->fresh()->puede_editar_precios)->toBeFalse();
});

it('allows privileged users to delete selected active production records in bulk', function (string $role) {
    $user = User::factory()->create([
        'rol' => $role,
        'activo' => true,
    ]);

    $worker = User::factory()->create([
        'rol' => 'usuario',
        'activo' => true,
    ]);

    $prenda = Prenda::create([
        'nombre' => 'Pantalon',
        'tipo' => 'Unidad',
        'precio' => 12000,
    ]);

    $selected = collect(range(1, 2))->map(fn () => Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 1,
        'total' => 12000,
        'fecha' => now()->toDateString(),
    ]));

    $kept = Produccion::create([
        'user_id' => $worker->id,
        'prenda_id' => $prenda->id,
        'cantidad' => 3,
        'total' => 36000,
        'fecha' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->delete(route('admin.produccion.destroy-bulk'), [
            'produccion_ids' => $selected->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.dashboard'));

    foreach ($selected as $produccion) {
        $this->assertDatabaseMissing('producciones', [
            'id' => $produccion->id,
        ]);
    }

    $this->assertDatabaseHas('producciones', [
        'id' => $kept->id,
    ]);
})->with(['admin', 'programador']);
