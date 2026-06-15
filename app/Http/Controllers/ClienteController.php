<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('recolector')
            ->orderByDesc('activo')
            ->orderBy('numero_cliente')
            ->get();

        $siguienteNumero = Cliente::siguienteNumero();
        $barriosCP = \App\Http\Controllers\MapaClientesController::BARRIOS_CP;

        return view('clientes.index', compact('clientes', 'siguienteNumero', 'barriosCP'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['activo'] = $request->boolean('activo', true);
        $data['numero_cliente'] = Cliente::siguienteNumero();

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente agregado correctamente.');
    }

    public function storeFromRecolector(Request $request)
    {
        $data = $this->validatedData($request);
        $data['activo'] = true;
        $data['numero_cliente'] = Cliente::siguienteNumero();
        $data['recolector_id'] = Auth::id();

        $cliente = Cliente::create($data);

        return redirect()
            ->route('recolector.index')
            ->with('success', 'Cliente creado correctamente.')
            ->with('cliente_creado_id', $cliente->id);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $this->validatedData($request, $cliente);
        $data['activo'] = $request->boolean('activo', $cliente->activo);

        $cliente->update($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }

    public function toggleStatus(Cliente $cliente)
    {
        $cliente->activo = ! $cliente->activo;
        $cliente->save();

        return back()->with(
            'success',
            $cliente->activo ? 'Cliente habilitado correctamente.' : 'Cliente inhabilitado correctamente.'
        );
    }

    /** Admin: asignar/reasignar cliente a un recolector (o desasignar con null). */
    public function delegarRecolector(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'recolector_id' => ['nullable', 'exists:users,id'],
        ]);

        $cliente->update(['recolector_id' => $data['recolector_id'] ?? null]);

        return back()->with('success', 'Cliente asignado correctamente.');
    }

    private function validatedData(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'celular'   => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'barrio'    => ['required', 'string', 'max:100'],
            'activo'    => ['nullable', 'boolean'],
        ]);
    }
}
