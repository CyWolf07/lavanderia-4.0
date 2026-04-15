<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    public function index()
    {
        $mensajes = Mensaje::all();
        return view('mensajes.index', compact('mensajes'));
    }

    public function create()
    {
        return view('mensajes.create');
    }

    public function store(Request $request)
    {
        Mensaje::create($request->all());

        return redirect()->route('mensajes.index')
            ->with('success', 'Mensaje creado correctamente');
    }

    public function edit($id)
    {
        $mensaje = Mensaje::findOrFail($id);
        return view('mensajes.edit', compact('mensaje'));
    }

    public function update(Request $request, $id)
    {
        $mensaje = Mensaje::findOrFail($id);
        $mensaje->update($request->all());

        return redirect()->route('mensajes.index')
            ->with('success', 'Mensaje actualizado');
    }

    public function destroy($id)
    {
        $mensaje = Mensaje::findOrFail($id);
        $mensaje->delete();

        return redirect()->route('mensajes.index')
            ->with('success', 'Mensaje eliminado');
    }
}
