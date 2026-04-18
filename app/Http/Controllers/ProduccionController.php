<?php

namespace App\Http\Controllers;

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
            'user'
        ));
    }

    public function store(Request $request)
    {
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

    public function cerrar()
    {
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
        $registros = HistorialProduccion::with('user')
            ->where('periodo', $periodo)
            ->orderBy('user_id')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        abort_if($registros->isEmpty(), 404);

        return view('admin.reporte-periodo', [
            'periodo' => $periodo,
            'registrosPorUsuario' => $registros->groupBy('user_id'),
            'totalGeneral' => $registros->sum('total'),
            'totalPrendas' => $registros->sum('cantidad'),
            'autoPrint' => request()->boolean('imprimir'),
        ]);
    }
}

