<?php

namespace App\Http\Controllers;

use App\Models\Prenda;
use App\Services\PrendasLavanderoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrendaController extends Controller
{
    private const MAX_PRECIO_PRENDA_REGULAR = 15000;

    public function index()
    {
        app(PrendasLavanderoSyncService::class)->sync();

        $prendas = Prenda::query()
            ->whereHas('equivalenciasRecolector')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('prendas.index-v2', compact('prendas'));
    }

    public function store(Request $request)
    {
        throw ValidationException::withMessages([
            'nombre' => 'Las prendas del lavandero se generan desde el listado del recolector. Ajusta solo el valor de pago.',
        ]);
    }

    public function update(Request $request, Prenda $prenda)
    {
        $data = $request->validate([
            'precio' => 'required|numeric|min:0',
        ]);

        if ((float) $data['precio'] > self::MAX_PRECIO_PRENDA_REGULAR && ! $this->permitePrecioAlto($prenda)) {
            throw ValidationException::withMessages([
                'precio' => 'El valor de lavandero solo puede superar $15.000 en articulos grandes como muebles, sofas, colchones, tapetes, alfombras, cortinas, edredones, cobijas, cubrelechos o plumones.',
            ]);
        }

        $prenda->update(['precio' => $data['precio']]);

        return redirect()->route('prendas.index')->with('success', 'Valor de lavandero actualizado correctamente.');
    }

    public function destroy(Prenda $prenda)
    {
        DB::transaction(function () use ($prenda) {
            $prenda->load('equivalenciasRecolector.recolectorPrenda');

            foreach ($prenda->equivalenciasRecolector as $equivalencia) {
                $equivalencia->recolectorPrenda?->delete();
            }

            $prenda->delete();
        });

        return redirect()->route('prendas.index')->with('success', 'Prenda eliminada correctamente.');
    }

    public function toggleStatus(Prenda $prenda)
    {
        return $prenda->activo
            ? $this->inhabilitar($prenda)
            : $this->habilitar($prenda);
    }

    public function habilitar(Prenda $prenda)
    {
        $this->actualizarEstadoConCatalogoRecolector($prenda, true);

        return back()->with('success', 'Prenda habilitada correctamente.');
    }

    public function inhabilitar(Prenda $prenda)
    {
        $this->actualizarEstadoConCatalogoRecolector($prenda, false);

        return back()->with('success', 'Prenda deshabilitada correctamente.');
    }

    private function actualizarEstadoConCatalogoRecolector(Prenda $prenda, bool $activo): void
    {
        DB::transaction(function () use ($prenda, $activo) {
            $prenda->load('equivalenciasRecolector.recolectorPrenda');

            foreach ($prenda->equivalenciasRecolector as $equivalencia) {
                $equivalencia->recolectorPrenda?->update(['activo' => $activo]);
            }

            $prenda->update(['activo' => $activo]);
        });
    }

    private function permitePrecioAlto(Prenda $prenda): bool
    {
        $texto = strtolower(Str::ascii(trim($prenda->nombre.' '.$prenda->tipo)));

        foreach ([
            'mueble',
            'sofa',
            'colchon',
            'tapete',
            'alfombra',
            'cortina',
            'edredon',
            'cobija',
            'cubrelecho',
            'plumon',
        ] as $permitido) {
            if (str_contains($texto, $permitido)) {
                return true;
            }
        }

        return false;
    }
}
