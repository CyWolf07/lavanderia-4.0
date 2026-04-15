<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Pqrs;

class PqrsController extends Controller
{
    public function index()
    {
        $pqrsList = Pqrs::latest()->get();
        return view('pqrs.index', compact('pqrsList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'descripcion' => 'required|string',
        ]);

        Pqrs::create($request->all());

        return redirect()->route('pqrs.index')->with('success', 'PQRS registrado exitosamente.');
    }

    public function destroy($id)
    {
    $pqrs = Pqrs::findOrFail($id);
    $pqrs->delete();

    return back()->with('success', 'Registro eliminado correctamente');
    }
}
