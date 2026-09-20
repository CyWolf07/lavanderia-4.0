<?php

use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\RecolectorPrenda;
use App\Models\User;
use App\Services\PrendasLavanderoSyncService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RecolectorPrendasSeeder;

it('keeps a new washer garment visible through disable enable and reload', function () {
    $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    $this->actingAs($admin)->post(route('prendas.store'), [
        'nombre' => 'Uniforme nuevo', 'precio' => 1200,
    ])->assertSessionHasNoErrors()->assertRedirect(route('prendas.index'));

    $prenda = Prenda::where('nombre', 'Uniforme nuevo')->sole();
    expect($prenda->activo)->toBeTrue();
    $this->get(route('prendas.index'))->assertOk()->assertSee('Uniforme nuevo');
    $this->patch(route('prendas.inhabilitar', $prenda))->assertSessionHasNoErrors();
    $this->get(route('prendas.index'))->assertOk()->assertSee('Uniforme nuevo');
    expect($prenda->fresh()->activo)->toBeFalse();
    $this->patch(route('prendas.habilitar', $prenda))->assertSessionHasNoErrors();
    $this->get(route('prendas.index'))->assertOk();

    $lavandero = User::factory()->create(['rol' => 'usuario', 'activo' => true]);
    $this->actingAs($lavandero)->get(route('produccion.index'))
        ->assertOk()->assertViewHas('prendas', fn ($prendas) => $prendas->contains('id', $prenda->id));
    expect($prenda->fresh()->activo)->toBeTrue();
});

it('preserves new and edited collector garments after repeated startup seeding', function () {
    $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
    $this->actingAs($admin)->post(route('recolector-prendas.store'), [
        'nombre' => 'Uniforme especial nuevo', 'tipo' => 'LAVADO', 'precio' => 18000,
    ])->assertSessionHasNoErrors();
    $nuevo = RecolectorPrenda::where('nombre', 'Uniforme especial nuevo')->sole();
    $this->seed(RecolectorPrendasSeeder::class);
    $base = RecolectorPrenda::where('nombre', 'BATAS')->sole();
    $base->update(['precio' => 19500, 'activo' => false]);
    $this->seed(RecolectorPrendasSeeder::class);
    app(PrendasLavanderoSyncService::class)->sync();

    expect($nuevo->fresh()->activo)->toBeTrue()
        ->and((float) $nuevo->fresh()->precio)->toBe(18000.0)
        ->and($base->fresh()->activo)->toBeFalse()
        ->and((float) $base->fresh()->precio)->toBe(19500.0);
    $this->assertDatabaseHas('prendas', ['nombre' => $nuevo->nombre, 'activo' => true]);
});

it('does not overwrite existing washer ids prices or states during deployment seeding', function () {
    $prenda = Prenda::create(['nombre' => 'Prenda propia', 'tipo' => 'Especial', 'precio' => 1350, 'activo' => false]);
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);
    expect($prenda->fresh()->nombre)->toBe('Prenda propia')
        ->and($prenda->fresh()->tipo)->toBe('Especial')
        ->and((float) $prenda->fresh()->precio)->toBe(1350.0)
        ->and($prenda->fresh()->activo)->toBeFalse();
});

it('does not disable enabled washer garments linked to duplicate collector names', function () {
    foreach ([1, 2] as $numero) {
        $recolector = RecolectorPrenda::create(['nombre' => 'Duplicada', 'tipo' => 'LAVADO', 'precio' => 10000, 'activo' => true]);
        $prenda = Prenda::create(['nombre' => 'Lavado '.$numero, 'tipo' => 'LAVADO', 'precio' => 1200, 'activo' => true]);
        PrendaEquivalencia::create(['recolector_prenda_id' => $recolector->id, 'prenda_id' => $prenda->id]);
    }
    app(PrendasLavanderoSyncService::class)->sync();
    expect(Prenda::activas()->count())->toBe(2);
});
