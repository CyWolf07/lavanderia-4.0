<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', compact('clientes'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $data['activo'] = $request->boolean('activo', true);

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente agregado correctamente.');
    }

    public function storeFromRecolector(Request $request)
    {
        $data = $this->validatedData($request);
        $data['activo'] = true;

        $cliente = Cliente::create($data);

        return redirect()
            ->route('recolector.index')
            ->with('success', 'Cliente creado correctamente desde Recolector.')
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

    private function validatedData(Request $request, ?Cliente $cliente = null): array
    {
        $uniqueNit = Rule::unique('clientes', 'nit_cedula');

        if ($cliente) {
            $uniqueNit = $uniqueNit->ignore($cliente->id);
        }

        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'nit_cedula' => ['required', 'string', 'max:50', $uniqueNit],
            'celular' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }
}

