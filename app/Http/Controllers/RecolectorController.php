<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\RecolectorPrenda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecolectorController extends Controller
{
    public function index()
    {
        $clientes = Cliente::activos()->orderBy('nombre')->get();
        $prendas = RecolectorPrenda::activas()->orderBy('nombre')->get();
        $siguienteNumeroFactura = ((int) FacturaRecolector::max('id')) + 1;

        $facturas = FacturaRecolector::with(['cliente', 'detalles'])
            ->where('recolector_id', Auth::id())
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->get();

        return view('recolector.index', [
            'clientes' => $clientes,
            'prendas' => $prendas,
            'facturas' => $facturas,
            'user' => Auth::user(),
            'fechaIngreso' => now(),
            'clientePreseleccionado' => session('cliente_creado_id'),
            'puedeEditarPrecios' => Auth::user()->puedeEditarPrecios(),
            'siguienteNumeroFactura' => $siguienteNumeroFactura,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha_entrega' => ['nullable', 'date', 'after_or_equal:today'],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['string'],
            'items' => ['nullable', 'array'],
            'items.*.prenda_id' => ['nullable', 'integer'],
            'items.*.cantidad' => ['nullable', 'integer', 'min:0'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $recolector = $request->user();

        $itemsSeleccionados = collect($request->input('items', []))
            ->filter(fn ($item) => $this->itemSeleccionado($item))
            ->values();

        if ($itemsSeleccionados->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Debes seleccionar al menos una prenda con cantidad.',
            ]);
        }

        if ($itemsSeleccionados->contains(fn ($item) => (int) ($item['cantidad'] ?? 0) < 1)) {
            throw ValidationException::withMessages([
                'items' => 'Cada prenda seleccionada debe tener una cantidad mayor a cero.',
            ]);
        }

        $itemsSeleccionados = $itemsSeleccionados
            ->map(function ($item) {
                return [
                    'prenda_id' => (int) ($item['prenda_id'] ?? 0),
                    'cantidad' => (int) ($item['cantidad'] ?? 0),
                    'precio_unitario' => isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : null,
                ];
            })
            ->filter(fn (array $item) => $item['prenda_id'] > 0 && $item['cantidad'] > 0)
            ->values();

        if ($itemsSeleccionados->pluck('prenda_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'No puedes registrar la misma prenda dos veces en una factura.',
            ]);
        }

        $prendas = RecolectorPrenda::activas()
            ->whereIn('id', $itemsSeleccionados->pluck('prenda_id'))
            ->get()
            ->keyBy('id');

        if ($prendas->count() !== $itemsSeleccionados->pluck('prenda_id')->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Una de las prendas seleccionadas no existe o esta inhabilitada.',
            ]);
        }

        $cliente = Cliente::activos()->find($data['cliente_id']);

        if (! $cliente) {
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente seleccionado no esta disponible.',
            ]);
        }

        if ($recolector->puedeEditarPrecios() && $itemsSeleccionados->contains(fn (array $item) => ($item['precio_unitario'] ?? 0) < 0)) {
            throw ValidationException::withMessages([
                'items' => 'El precio unitario no puede ser negativo.',
            ]);
        }

        $fechaIngreso = now();
        $fechaEntrega = ! empty($data['fecha_entrega'])
            ? Carbon::parse($data['fecha_entrega'])
            : $fechaIngreso->copy()->addDays(2);

        $detalles = $itemsSeleccionados->map(function (array $item) use ($prendas, $recolector) {
            $prenda = $prendas->get($item['prenda_id']);
            $valorUnitario = $recolector->puedeEditarPrecios()
                ? (float) ($item['precio_unitario'] ?? $prenda->precio)
                : (float) $prenda->precio;
            $subtotal = $valorUnitario * $item['cantidad'];

            return [
                'recolector_prenda_id' => $prenda->id,
                'prenda_nombre' => $prenda->nombre,
                'valor_unitario' => $valorUnitario,
                'cantidad' => $item['cantidad'],
                'subtotal' => $subtotal,
            ];
        });

        $totalPrendas = (int) $detalles->sum('cantidad');
        $totalFactura = $detalles->sum('subtotal');

        DB::transaction(function () use ($cliente, $fechaIngreso, $fechaEntrega, $data, $detalles, $totalPrendas, $totalFactura) {
            $factura = FacturaRecolector::create([
                'recolector_id' => Auth::id(),
                'cliente_id' => $cliente->id,
                'fecha_ingreso' => $fechaIngreso,
                'fecha_entrega' => $fechaEntrega->toDateString(),
                'direccion' => $cliente->direccion,
                'nit_cedula' => $cliente->nit_cedula,
                'celular' => $cliente->celular,
                'observaciones' => array_values($data['observaciones'] ?? []),
                'total_prendas' => $totalPrendas,
                'total' => $totalFactura,
            ]);

            $factura->detalles()->createMany($detalles->all());
        });

        return redirect()->route('recolector.index')->with('success', 'Factura del recolector registrada correctamente.');
    }

    private function itemSeleccionado(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        return filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || in_array(($item['selected'] ?? null), ['1', 1, true, 'true', 'on'], true);
    }
}
