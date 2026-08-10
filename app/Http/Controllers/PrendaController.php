<?php

namespace App\Http\Controllers;

use App\Models\Prenda;
use App\Services\PrendasLavanderoSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrendaController extends Controller
{
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

        $prenda->update(['precio' => $data['precio']]);

        return redirect()->route('prendas.index')->with('success', 'Valor de lavandero actualizado correctamente.');
    }

    public function destroy(Prenda $prenda)
    {
        throw ValidationException::withMessages([
            'nombre' => 'No se eliminan prendas del lavandero directamente. Inhabilitala desde el listado del recolector.',
        ]);
    }

    public function toggleStatus(Prenda $prenda)
    {
        throw ValidationException::withMessages([
            'nombre' => 'El estado de las prendas del lavandero se hereda del listado del recolector.',
        ]);
    }

    public function habilitar(Prenda $prenda)
    {
        throw ValidationException::withMessages([
            'nombre' => 'Habilita esta prenda desde el listado del recolector.',
        ]);
    }

    public function inhabilitar(Prenda $prenda)
    {
        throw ValidationException::withMessages([
            'nombre' => 'Inhabilita esta prenda desde el listado del recolector.',
        ]);
    }
}
