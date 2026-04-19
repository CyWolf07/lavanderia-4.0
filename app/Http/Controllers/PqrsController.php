<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pqrs;

/**
 * PqrsController
 *
 * Gestiona el módulo de Peticiones, Quejas, Reclamos y Sugerencias (PQRS).
 * Cualquier usuario autenticado puede radicar y consultar sus PQRS.
 * La eliminación y edición solo deben usarse desde roles admin (controlado en rutas).
 */
class PqrsController extends Controller
{
    /**
     * Muestra el listado de todos los PQRS radicados.
     * Se muestran los más recientes primero (latest = orden DESC por created_at).
     */
    public function index()
    {
        // Obtiene todos los registros PQRS ordenados del más nuevo al más antiguo
        $pqrsList = Pqrs::latest()->get();

        return view('pqrs.index', compact('pqrsList'));
    }

    /**
     * Guarda un nuevo PQRS en la base de datos.
     * Valida que todos los campos requeridos estén presentes y con formato correcto.
     */
    public function store(Request $request)
    {
        // Validación de los campos del formulario de radicación
        $request->validate([
            'tipo'       => 'required|string',              // Tipo: Petición, Queja, Reclamo o Sugerencia
            'nombre'     => 'required|string|max:255',      // Nombre completo del solicitante
            'correo'     => 'required|email|max:255',       // Correo electrónico válido
            'descripcion'=> 'required|string',              // Descripción detallada de la solicitud
        ]);

        // Crea el registro con todos los campos validados
        Pqrs::create($request->only(['tipo', 'nombre', 'correo', 'descripcion']));

        return redirect()->route('pqrs.index')->with('success', 'PQRS registrado exitosamente.');
    }

    /**
     * Muestra el formulario de edición de un PQRS existente.
     * Busca el registro por ID o lanza 404 si no existe.
     */
    public function edit($id)
    {
        // Busca el PQRS por su ID; si no existe devuelve error 404
        $pqrs = Pqrs::findOrFail($id);

        // Necesitamos la lista completa para renderizar el layout de la vista
        $pqrsList = Pqrs::latest()->get();

        return view('pqrs.edit', compact('pqrs', 'pqrsList'));
    }

    /**
     * Actualiza un PQRS existente con los nuevos datos enviados del formulario.
     */
    public function update(Request $request, $id)
    {
        // Valida los mismos campos que en el store
        $request->validate([
            'tipo'       => 'required|string',
            'nombre'     => 'required|string|max:255',
            'correo'     => 'required|email|max:255',
            'descripcion'=> 'required|string',
        ]);

        // Busca y actualiza el registro
        $pqrs = Pqrs::findOrFail($id);
        $pqrs->update($request->only(['tipo', 'nombre', 'correo', 'descripcion']));

        return redirect()->route('pqrs.index')->with('success', 'PQRS actualizado correctamente.');
    }

    /**
     * Elimina permanentemente un PQRS de la base de datos.
     * Esta acción es irreversible.
     */
    public function destroy($id)
    {
        // Busca el PQRS o lanza 404
        $pqrs = Pqrs::findOrFail($id);
        $pqrs->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }
}
