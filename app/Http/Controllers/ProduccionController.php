<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\HistorialProduccion;
use App\Models\Prenda;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $ordenesPendientes = collect();

        if ($user->tieneRol('usuario')) {
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
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $prendas = Prenda::activas()->orderBy('nombre')->get();

        $porDia = Produccion::query()
            ->where('user_id', $user->id)
            ->selectRaw(
                $user->tieneRol('usuario')
                    ? 'fecha as dia, SUM(cantidad) as total_prendas'
                    : 'fecha as dia, SUM(total) as total'
            )
            ->groupBy('fecha')
            ->orderByDesc('fecha')
            ->get();

        $totalQuincena = Produccion::where('user_id', $user->id)->sum('total');

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
            'ordenesPendientes'
        ));
    }

    public function store(Request $request)
    {
        if ($request->user()->tieneRol('usuario')) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Debes registrar la produccion desde las ordenes de pedido asignadas.');
        }

        $request->validate([
            'prenda_id' => ['required', 'exists:prendas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $prenda = Prenda::activas()->find($request->integer('prenda_id'));

        if (! $prenda) {
            return redirect()->route('produccion.index')->with('error', 'La prenda seleccionada no está disponible.');
        }

        $cantidad = $request->integer('cantidad');

        Produccion::create([
            'user_id' => Auth::id(),
            'prenda_id' => $prenda->id,
            'cantidad' => $cantidad,
            'total' => $prenda->precio * $cantidad,
            'fecha' => now()->toDateString(),
        ]);

        return redirect()->route('produccion.index')->with('success', 'Producción registrada correctamente.');
    }

    public function guardarLavado(Request $request, FacturaRecolector $facturaRecolector)
    {
        abort_unless($request->user()->tieneRol('usuario'), 403);
        abort_if($facturaRecolector->estaCancelada(), 404);

        $data = $request->validate([
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*' => ['integer'],
        ]);

        $detalles = $facturaRecolector->detalles()
            ->with('prenda')
            ->whereNull('lavado_en')
            ->whereIn('id', $data['detalles'])
            ->get();

        if ($detalles->isEmpty()) {
            return redirect()
                ->route('produccion.index')
                ->with('error', 'Selecciona al menos una prenda pendiente de esta orden.');
        }

        DB::transaction(function () use ($detalles, $request) {
            foreach ($detalles as $detalle) {
                $prenda = $this->resolverPrendaProduccion($detalle);

                $produccion = Produccion::create([
                    'user_id' => $request->user()->id,
                    'prenda_id' => $prenda->id,
                    'cantidad' => $detalle->cantidad,
                    'total' => (float) $prenda->precio * (int) $detalle->cantidad,
                    'fecha' => now()->toDateString(),
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

        $producciones = Produccion::with(['user', 'prenda'])
            ->orderBy('user_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        if ($producciones->isEmpty()) {
            return redirect()->route('admin.dashboard')->with('error', 'No hay registros activos para cerrar.');
        }

        $fechaBase = Carbon::parse(optional($producciones->sortByDesc('fecha')->first())->fecha ?? now());
        $periodo = HistorialProduccion::periodoDesdeFecha($fechaBase);

        DB::transaction(function () use ($producciones, $periodo) {
            foreach ($producciones as $produccion) {
                HistorialProduccion::create([
                    'user_id' => $produccion->user_id,
                    'prenda_id' => $produccion->prenda_id,
                    'prenda_nombre' => $produccion->prenda->nombre ?? 'Prenda eliminada',
                    'precio_unitario' => $produccion->cantidad > 0 ? ($produccion->total / $produccion->cantidad) : 0,
                    'cantidad' => $produccion->cantidad,
                    'total' => $produccion->total,
                    'fecha' => optional($produccion->fecha)->toDateString() ?? now()->toDateString(),
                    'periodo' => $periodo['periodo'],
                    'anio' => $periodo['anio'],
                    'mes' => $periodo['mes'],
                    'quincena' => $periodo['quincena'],
                    'cerrado_por' => Auth::id(),
                ]);
            }

            Produccion::query()->delete();
        });

        return redirect()->route('admin.reportes.periodo', [
            'periodo' => $periodo['periodo'],
            'imprimir' => 1,
        ])->with('success', 'Quincena cerrada, respaldada e informe listo para imprimir.');
    }

    public function reportePeriodo(string $periodo)
    {
        [$inicioPeriodo, $finPeriodo] = $this->rangoParaPeriodo($periodo);

        $registros = HistorialProduccion::with('user')
            ->where('periodo', $periodo)
            ->orderBy('user_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        abort_if($registros->isEmpty(), 404);

        $facturasRecolector = FacturaRecolector::with(['recolector', 'cliente', 'detalles'])
            ->where('estado_factura', 'pagado')
            ->whereBetween('updated_at', [$inicioPeriodo, $finPeriodo])
            ->orderBy('updated_at')
            ->orderBy('recolector_id')
            ->orderBy('id')
            ->get();

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
                    'total'  => $total,
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

    private function resolverPrendaProduccion($detalle): Prenda
    {
        $nombre = trim((string) $detalle->prenda_nombre);

        $prenda = Prenda::query()
            ->whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
            ->first();

        if ($prenda) {
            return $prenda;
        }

        return Prenda::create([
            'nombre' => $nombre !== '' ? $nombre : 'Prenda sin nombre',
            'tipo' => $detalle->prenda?->tipo ?? 'Orden recolector',
            'precio' => $detalle->prenda?->precio ?? 0,
            'activo' => true,
        ]);
    }
}
