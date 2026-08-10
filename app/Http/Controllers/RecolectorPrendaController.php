<?php

namespace App\Http\Controllers;

use App\Models\RecolectorPrenda;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecolectorPrendaController extends Controller
{
    public function index()
    {
        $prendas = RecolectorPrenda::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('recolector-prendas.index-v2', compact('prendas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);

        $this->validarPrendaUnica($data['nombre'], $data['tipo'] ?? null);

        $data['nombre'] = trim($data['nombre']);
        $data['tipo'] = filled($data['tipo'] ?? null) ? trim($data['tipo']) : null;
        $data['activo'] = true;

        RecolectorPrenda::create($data);

        return redirect()->route('recolector-prendas.index')->with('success', 'Prenda de recolector agregada correctamente.');
    }

    public function update(Request $request, RecolectorPrenda $recolectorPrenda)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);

        $this->validarPrendaUnica($data['nombre'], $data['tipo'] ?? null, $recolectorPrenda->id);

        $data['nombre'] = trim($data['nombre']);
        $data['tipo'] = filled($data['tipo'] ?? null) ? trim($data['tipo']) : null;

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

    public function habilitar(RecolectorPrenda $recolectorPrenda)
    {
        $recolectorPrenda->update(['activo' => true]);

        return back()->with('success', 'Prenda del recolector habilitada correctamente.');
    }

    public function inhabilitar(RecolectorPrenda $recolectorPrenda)
    {
        $recolectorPrenda->update(['activo' => false]);

        return back()->with('success', 'Prenda del recolector inhabilitada correctamente.');
    }

    private function validarPrendaUnica(string $nombre, ?string $tipo, ?int $exceptoId = null): void
    {
        $nombreNormalizado = strtolower(trim($nombre));
        $tipoNormalizado = strtolower(trim((string) $tipo));

        $existe = RecolectorPrenda::query()
            ->when($exceptoId, fn ($query) => $query->whereKeyNot($exceptoId))
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNormalizado])
            ->whereRaw("LOWER(TRIM(COALESCE(tipo, ''))) = ?", [$tipoNormalizado])
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una prenda de recolector con ese nombre y tipo.',
            ]);
        }
    }
}
