<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\HistorialProduccion;
use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\Produccion;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DashboardCacheService;
use App\Services\PrendasLavanderoSyncService;
use App\Services\ProduccionValidationService;
use Carbon\Carbon;
use Database\Seeders\LavanderoPrendasEquivalenciasSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        app(PrendasLavanderoSyncService::class)->sync();
        $modoInterfazLavandero = SystemSetting::getValue('produccion_interfaz_lavandero', 'basica');
        $ordenesPendientes = collect();
        [$inicioQuincena, $finQuincena] = $this->rangoQuincenaActual();

        if ($user->tieneRol('usuario') && $modoInterfazLavandero === 'avanzada') {
            $ordenesPendientes = FacturaRecolector::query()
                ->with([
                    'recolector',
                    'detalles' => fn ($query) => $query
                        ->whereNull('lavado_en')
                        ->orderBy('id'),
                ])
                ->whereHas('detalles', fn ($query) => $query->whereNull('lavado_en'))
                ->noCanceladas()
                ->orderBy('numero_orden')
                ->orderBy('id')
                ->get();
        }

        $producciones = Produccion::with('prenda')
            ->where('user_id', $user->id)
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $prendas = Prenda::activas()
            ->where(fn ($query) => $query
                ->whereIn('id', LavanderoPrendasEquivalenciasSeeder::PRENDAS_BASE_VISIBLES)
                ->orWhereHas('equivalenciasRecolector'))
            ->orderBy('nombre')
            ->get();

        $porDia = Produccion::query()
            ->where('user_id', $user->id)
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->selectRaw(
                $user->tieneRol('usuario')
                    ? 'fecha as dia, SUM(cantidad_validada) as total_prendas'
                    : 'fecha as dia, SUM(total_validado) as total'
            )
            ->groupBy('fecha')
            ->orderByDesc('fecha')
            ->get();

        $totalQuincena = Produccion::query()
            ->where('user_id', $user->id)
            ->pagables()
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->sum('total_validado');

        $historialQuincenas = HistorialProduccion::query()
            ->selectRaw('periodo, SUM(total) as total_periodo, SUM(cantidad) as total_prendas, MAX(fecha) as ultima_fecha')
            ->where('user_id', $user->id)
            ->groupBy('periodo')
            ->orderByDesc('periodo')
            ->get();

        return view('produccion.index', compact(
            'producciones',
            'prendas',
            'porDia',
            'totalQuincena',
            'historialQuincenas',
            'user',
            'modoInterfazLavandero',
            'ordenesPendientes'
        ));
    }

    public function store(Request $request, ProduccionValidationService $validationService)
    {
        app(PrendasLavanderoSyncService::class)->sync();

        $data = $request->validate([
            'fecha' => ['nullable', 'date', 'before_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.prenda_id' => ['required', 'integer', 'exists:prendas,id'],
            'items.*.cantidad' => ['nullable', 'integer', 'min:0'],
        ]);

        $fecha = Carbon::parse($data['fecha'] ?? now())->toDateString();
        $items = collect($data['items'])
            ->map(fn (array $item) => [
                'prenda_id' => (int) $item['prenda_id'],
                'cantidad' => (int) ($item['cantidad'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['cantidad'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Registra al menos una prenda con cantidad mayor a cero.');
        }

        if ($items->pluck('prenda_id')->duplicates()->isNotEmpty()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'No puedes registrar la misma prenda dos veces en el mismo cierre.');
        }

        $prendas = Prenda::activas()
            ->where(fn ($query) => $query
                ->whereIn('id', LavanderoPrendasEquivalenciasSeeder::PRENDAS_BASE_VISIBLES)
                ->orWhereHas('equivalenciasRecolector'))
            ->whereIn('id', $items->pluck('prenda_id'))
            ->get()
            ->keyBy('id');

        if ($prendas->count() !== $items->count()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Una de las prendas seleccionadas no existe o esta inhabilitada.');
        }

        $userId = (int) Auth::id();

        DB::transaction(function () use ($fecha, $items, $prendas, $userId) {
            $idsSeleccionados = $items->pluck('prenda_id')->all();

            Produccion::query()
                ->where('user_id', $userId)
                ->whereDate('fecha', $fecha)
                ->whereNotIn('prenda_id', $idsSeleccionados)
                ->where('estado_validacion', '!=', 'aprobado')
                ->delete();

            foreach ($items as $item) {
                $prenda = $prendas->get($item['prenda_id']);
                $total = (float) $prenda->precio * (int) $item['cantidad'];

                Produccion::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'prenda_id' => $prenda->id,
                        'fecha' => $fecha,
                    ],
                    [
                        'cantidad' => $item['cantidad'],
                        'total' => $total,
                        'cantidad_validada' => 0,
                        'total_validado' => 0,
                        'estado_validacion' => 'pendiente',
                        'validado_en' => null,
                    ]
                );
            }
        });

        $validationService->recalcularFecha($fecha);
        app(DashboardCacheService::class)->flushProduccion();

        return redirect()->route('produccion.index')->with('success', 'Registro diario guardado y validado correctamente.');
    }

    public function guardarLavado(Request $request, FacturaRecolector $facturaRecolector)
    {
        abort_unless($request->user()->tieneRol('usuario'), 403);
        abort_if($facturaRecolector->estaCancelada(), 404);
        app(PrendasLavanderoSyncService::class)->sync();

        $data = $request->validate([
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*' => ['integer'],
        ]);

        $detalles = $facturaRecolector->detalles()
            ->whereNull('lavado_en')
            ->whereIn('id', $data['detalles'])
            ->get();

        if ($detalles->isEmpty()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Selecciona al menos una prenda pendiente de esta orden.');
        }

        $prendasPorDetalle = collect();
        $prendasFaltantes = collect();

        foreach ($detalles as $detalle) {
            $prenda = $this->resolverPrendaProduccion($detalle);

            if (! $prenda) {
                $prendasFaltantes->push($detalle->prenda_nombre ?: 'Prenda sin nombre');

                continue;
            }

            $prendasPorDetalle->put($detalle->id, $prenda);
        }

        if ($prendasFaltantes->isNotEmpty()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Estas prendas no tienen precio configurado en la tabla prendas: '.$prendasFaltantes->unique()->join(', '));
        }

        DB::transaction(function () use ($detalles, $request, $prendasPorDetalle) {
            foreach ($detalles as $detalle) {
                $prenda = $prendasPorDetalle->get($detalle->id);

                $produccion = Produccion::create([
                    'user_id' => $request->user()->id,
                    'prenda_id' => $prenda->id,
                    'cantidad' => $detalle->cantidad,
                    'cantidad_validada' => $detalle->cantidad,
                    'total' => (float) $prenda->precio * (int) $detalle->cantidad,
                    'total_validado' => (float) $prenda->precio * (int) $detalle->cantidad,
                    'fecha' => now()->toDateString(),
                    'estado_validacion' => 'validado',
                    'validado_en' => now(),
                ]);

                $detalle->update([
                    'lavado_por' => $request->user()->id,
                    'lavado_en' => now(),
                    'produccion_id' => $produccion->id,
                ]);
            }
        });

        return redirect()
            ->route('produccion.index')
            ->with('success', 'Prendas marcadas como lavadas correctamente.');
    }

    public function cerrar()
    {
        abort_unless(Auth::user()?->tieneRol('admin', 'programador'), 403);

        $periodoActual = HistorialProduccion::periodoDesdeFecha(now());
        $producciones = Produccion::with(['user', 'prenda'])
            ->pagables()
            ->orderBy('user_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        if ($producciones->isEmpty()) {
            $tieneDatosPeriodo = FacturaRecolector::query()
                ->pagadasEnQuincena($periodoActual['periodo'])
                ->exists()
                || Gasto::query()->where('periodo', $periodoActual['periodo'])->exists();

            if ($tieneDatosPeriodo) {
                return redirect()->route('admin.reportes.periodo', [
                    'periodo' => $periodoActual['periodo'],
                    'imprimir' => 1,
                ])->with('success', 'Informe de quincena listo para imprimir.');
            }

            return redirect()->route('admin.dashboard')->with('error', 'No hay registros activos para cerrar.');
        }

        $periodosCerrados = collect();

        DB::transaction(function () use ($producciones, &$periodosCerrados) {
            $periodosCerrados = $producciones
                ->groupBy(fn (Produccion $produccion) => HistorialProduccion::periodoDesdeFecha(
                    Carbon::parse($produccion->fecha ?? now())
                )['periodo'])
                ->keys()
                ->values();

            foreach ($producciones as $produccion) {
                $fecha = Carbon::parse($produccion->fecha ?? now());
                $periodo = HistorialProduccion::periodoDesdeFecha($fecha);

                HistorialProduccion::create([
                    'user_id' => $produccion->user_id,
                    'prenda_id' => $produccion->prenda_id,
                    'prenda_nombre' => $produccion->prenda?->nombre ?? 'Prenda eliminada',
                    'precio_unitario' => $produccion->cantidad_validada > 0 ? ($produccion->total_validado / $produccion->cantidad_validada) : 0,
                    'cantidad' => $produccion->cantidad_validada,
                    'total' => $produccion->total_validado,
                    'fecha' => $fecha->toDateString(),
                    'periodo' => $periodo['periodo'],
                    'anio' => $periodo['anio'],
                    'mes' => $periodo['mes'],
                    'quincena' => $periodo['quincena'],
                    'cerrado_por' => Auth::id(),
                ]);
            }

            Produccion::query()->whereIn('id', $producciones->pluck('id'))->delete();
        });

        // Invalidar caché del dashboard (quincena cerrada, historial cambió)
        app(DashboardCacheService::class)->flush();

        $periodoDestino = $periodosCerrados->contains($periodoActual['periodo'])
            ? $periodoActual['periodo']
            : (string) $periodosCerrados->sortDesc()->first();

        return redirect()->route('admin.reportes.periodo', [
            'periodo' => $periodoDestino,
            'imprimir' => 1,
        ])->with('success', 'Quincena cerrada, respaldada e informe listo para imprimir.');
    }

    public function editHistorial(HistorialProduccion $historialProduccion)
    {
        abort_unless(auth()->user()?->tieneRol('admin', 'programador'), 403);
        $historialProduccion->load(['user', 'prenda', 'cerradoPor']);
        $usuarios = User::orderBy('name')->get();
        $prendas = Prenda::orderBy('nombre')->get();

        return view('admin.historial-edit', [
            'registro' => $historialProduccion,
            'usuarios' => $usuarios,
            'prendas' => $prendas,
        ]);
    }

    public function updateHistorial(Request $request, HistorialProduccion $historialProduccion)
    {
        abort_unless(auth()->user()?->tieneRol('admin', 'programador'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'prenda_id' => ['required', 'exists:prendas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
        ]);

        $prenda = Prenda::findOrFail($data['prenda_id']);
        $total = $data['cantidad'] * $prenda->precio;

        $historialProduccion->update([
            'user_id' => $data['user_id'],
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre,
            'precio_unitario' => $prenda->precio,
            'cantidad' => $data['cantidad'],
            'total' => $total,
            'fecha' => $data['fecha'],
        ]);

        // Invalidar caché del dashboard (historial actualizado)
        app(DashboardCacheService::class)->flushProduccion();

        return redirect()
            ->route('admin.reportes.periodo', $historialProduccion->periodo)
            ->with('success', 'Registro histórico actualizado correctamente.');
    }

    public function reportePeriodo(string $periodo)
    {

        $registros = HistorialProduccion::with('user')
            ->where('periodo', $periodo)
            ->orderBy('user_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $facturasRecolector = FacturaRecolector::with(['recolector', 'cliente', 'detalles'])
            ->pagadasEnQuincena($periodo)
            ->orderBy('updated_at')
            ->orderBy('recolector_id')
            ->orderBy('id')
            ->get();

        $gastosDetalle = Gasto::with('user')
            ->where('periodo', $periodo)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        abort_if($registros->isEmpty() && $facturasRecolector->isEmpty() && $gastosDetalle->isEmpty(), 404);

        $gastosPeriodo = Gasto::query()
            ->where('periodo', $periodo)
            ->sum('monto');

        // Panel: Órdenes Pagadas
        $ordenesPagadas = $facturasRecolector;
        $ordenesPagadasTotal = (float) $ordenesPagadas->sum('total');

        // Total Neto = Órdenes Pagadas - Gastos
        $totalNeto = $ordenesPagadasTotal - (float) $gastosPeriodo;

        // Panel 30%
        $resumen30Recolectores = $ordenesPagadas
            ->groupBy('recolector_id')
            ->map(function ($facturas) {
                $rec = $facturas->first()->recolector;
                $total = (float) $facturas->sum('total');

                return [
                    'nombre' => $rec?->name ?? 'Sin nombre',
                    'total' => $total,
                    'pago30' => round($total * 0.30),
                ];
            })->values();
        $total30 = $resumen30Recolectores->sum('pago30');

        $pagoUsuarios = $registros->sum('total');

        // Ganancia = Total Neto - Pago Usuarios - Pago Recolectores
        $ganancia = $totalNeto - $pagoUsuarios - $total30;

        return view('admin.reporte-periodo', [
            'periodo' => $periodo,
            'registrosPorUsuario' => $registros->groupBy('user_id'),
            'totalGeneral' => $pagoUsuarios,
            'totalPrendas' => $registros->sum('cantidad'),
            'facturasRecolector' => $facturasRecolector,
            'totalFacturasPeriodo' => $facturasRecolector->sum('total'),
            'totalPrendasFacturas' => $facturasRecolector->sum('total_prendas'),
            'gastosPeriodo' => $gastosPeriodo,
            'gastosDetalle' => $gastosDetalle,
            'ordenesPagadasTotal' => $ordenesPagadasTotal,
            'totalNeto' => $totalNeto,
            'resumen30Recolectores' => $resumen30Recolectores,
            'total30' => $total30,
            'ganancia' => $ganancia,
            'autoPrint' => request()->boolean('imprimir'),
        ]);
    }

    private function rangoParaPeriodo(string $periodo): array
    {
        preg_match('/^(\d{4})\/(\d{2})\/QUINCENA([12])$/', $periodo, $matches);

        if (count($matches) !== 4) {
            $ahora = now();

            return [$ahora->copy()->startOfMonth()->startOfDay(), $ahora->copy()->endOfMonth()->endOfDay()];
        }

        $anio = (int) $matches[1];
        $mes = (int) $matches[2];
        $quincena = (int) $matches[3];

        $base = Carbon::create($anio, $mes, 1);

        if ($quincena === 1) {
            return [
                $base->copy()->startOfMonth()->startOfDay(),
                $base->copy()->day(15)->endOfDay(),
            ];
        }

        return [
            $base->copy()->day(16)->startOfDay(),
            $base->copy()->endOfMonth()->endOfDay(),
        ];
    }

    private function rangoQuincenaActual(): array
    {
        $hoy = now();

        if ($hoy->day <= 15) {
            return [
                $hoy->copy()->startOfMonth()->startOfDay(),
                $hoy->copy()->startOfMonth()->day(15)->endOfDay(),
            ];
        }

        return [
            $hoy->copy()->startOfMonth()->day(16)->startOfDay(),
            $hoy->copy()->endOfMonth()->endOfDay(),
        ];
    }

    private function resolverPrendaProduccion($detalle): ?Prenda
    {
        if ($detalle->recolector_prenda_id) {
            $prendaId = PrendaEquivalencia::query()
                ->where('recolector_prenda_id', $detalle->recolector_prenda_id)
                ->value('prenda_id');

            if ($prendaId) {
                return Prenda::query()->find($prendaId);
            }
        }

        $nombre = trim((string) $detalle->prenda_nombre);

        return Prenda::query()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower($nombre)])
            ->first();
    }
}
