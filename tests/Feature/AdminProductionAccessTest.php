<?php

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\Prenda;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;
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
