<?php

namespace App\Http\Controllers;

use App\Models\RecolectorPrenda;
use Illuminate\Http\Request;

class RecolectorPrendaController extends Controller
{
    public function index()
    {
        $prendas = RecolectorPrenda::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('recolector-prendas.index', compact('prendas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);

        RecolectorPrenda::create($data);

        return redirect()->route('recolector-prendas.index')->with('success', 'Prenda de recolector agregada correctamente.');
    }

    public function update(Request $request, RecolectorPrenda $recolectorPrenda)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', $recolectorPrenda->activo);

        $recolectorPrenda->update($data);

        return redirect()->route('recolector-prendas.index')->with('success', 'Prenda de recolector actualizada correctamente.');
    }

    public function destroy(RecolectorPrenda $recolectorPrenda)
    {
        $recolectorPrenda->delete();

        return redirect()->route('recolector-prendas.index')->with('success', 'Prenda de recolector eliminada correctamente.');
    }

    public function toggleStatus(RecolectorPrenda $recolectorPrenda)
    {
        $recolectorPrenda->activo = ! $recolectorPrenda->activo;
        $recolectorPrenda->save();

        return back()->with(
            'success',
            $recolectorPrenda->activo
                ? 'Prenda del recolector habilitada correctamente.'
                : 'Prenda del recolector inhabilitada correctamente.'
        );
    }
}

