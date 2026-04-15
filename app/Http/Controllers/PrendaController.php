<?php

namespace App\Http\Controllers;

use App\Models\Prenda;
use Illuminate\Http\Request;

class PrendaController extends Controller
{
    public function index()
    {
        $prendas = Prenda::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('prendas.index-v2', compact('prendas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'nullable|string|max:50',
            'precio' => 'required|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        Prenda::create($data);

        return redirect()->route('prendas.index')->with('success', 'Prenda agregada correctamente.');
    }

    public function update(Request $request, Prenda $prenda)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'nullable|string|max:50',
            'precio' => 'required|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $data['activo'] = $request->boolean('activo', $prenda->activo);

        $prenda->update($data);

        return redirect()->route('prendas.index')->with('success', 'Prenda actualizada correctamente.');
    }

    public function destroy(Prenda $prenda)
    {
        $prenda->delete();
        return redirect()->route('prendas.index')->with('success', 'Prenda eliminada correctamente.');
    }

    public function toggleStatus(Prenda $prenda)
    {
        $prenda->activo = ! $prenda->activo;
        $prenda->save();

        return back()->with(
            'success',
            $prenda->activo ? 'Prenda habilitada correctamente.' : 'Prenda inhabilitada correctamente.'
        );
    }
}
