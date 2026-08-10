<?php

namespace App\Http\Controllers;

use App\Models\Prenda;
use App\Services\PrendasLavanderoSyncService;
use Database\Seeders\LavanderoPrendasEquivalenciasSeeder;
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
            ->where(fn ($query) => $query
                ->where('activo', true)
                ->orWhereIn('id', LavanderoPrendasEquivalenciasSeeder::PRENDAS_BASE_VISIBLES)
                ->orWhereHas('equivalenciasRecolector'))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('prendas.index-v2', compact('prendas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);

        $data['nombre'] = trim($data['nombre']);
        $data['tipo'] = filled($data['tipo'] ?? null) ? trim($data['tipo']) : 'LAVADO';

        $this->validarPrendaUnica($data['nombre'], $data['tipo']);

        $prenda = new Prenda($data + ['activo' => true]);

        if ((float) $data['precio'] > self::MAX_PRECIO_PRENDA_REGULAR && ! $this->permitePrecioAlto($prenda)) {
            throw ValidationException::withMessages([
                'precio' => 'El valor de lavandero solo puede superar $15.000 en articulos grandes como muebles, sofas, colchones, tapetes, alfombras, cortinas, edredones, cobijas, cubrelechos o plumones.',
            ]);
        }

        $prenda->save();

        return redirect()->route('prendas.index')->with('success', 'Prenda de lavandero creada correctamente.');
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

    private function validarPrendaUnica(string $nombre, ?string $tipo, ?int $exceptoId = null): void
    {
        $nombreNormalizado = strtolower(trim($nombre));
        $tipoNormalizado = strtolower(trim((string) $tipo));

        $existe = Prenda::query()
            ->when($exceptoId, fn ($query) => $query->whereKeyNot($exceptoId))
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNormalizado])
            ->whereRaw("LOWER(TRIM(COALESCE(tipo, ''))) = ?", [$tipoNormalizado])
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una prenda de lavandero con ese nombre y tipo.',
            ]);
        }
    }
}
