<?php

namespace App\Services;

use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\RecolectorPrenda;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrendasLavanderoSyncService
{
    public function sync(): void
    {
        DB::transaction(function () {
            $prendasLavandero = Prenda::query()->get();
            $preciosPorClave = $this->preciosLavanderoPorClave($prendasLavandero);
            $idsSincronizados = [];

            $recolectorPrendas = RecolectorPrenda::query()
                ->orderByDesc('activo')
                ->orderBy('nombre')
                ->orderBy('tipo')
                ->orderBy('id')
                ->get()
                ->unique(fn (RecolectorPrenda $prenda) => $this->clave($prenda->nombre, $prenda->tipo));

            foreach ($recolectorPrendas as $recolectorPrenda) {
                $clave = $this->clave($recolectorPrenda->nombre, $recolectorPrenda->tipo);
                $prendaPorEquivalencia = $this->buscarPrendaPorEquivalencia($prendasLavandero, $recolectorPrenda->id);
                $prenda = $prendaPorEquivalencia ?? $this->buscarPrendaPorClave($prendasLavandero, $clave);
                $precio = $preciosPorClave->get($clave, $prenda?->precio ?? 0);

                if (! $prenda) {
                    $prenda = Prenda::create([
                        'nombre' => trim($recolectorPrenda->nombre),
                        'tipo' => $this->normalizarTipo($recolectorPrenda->tipo),
                        'precio' => $precio,
                        'activo' => (bool) $recolectorPrenda->activo,
                    ]);

                    $prendasLavandero->push($prenda);
                } elseif (! $this->esEquivalenciaAgrupada($prendaPorEquivalencia, $recolectorPrenda)) {
                    $prenda->update([
                        'nombre' => trim($recolectorPrenda->nombre),
                        'tipo' => $this->normalizarTipo($recolectorPrenda->tipo),
                        'precio' => $precio,
                        'activo' => (bool) $recolectorPrenda->activo,
                    ]);
                }

                $idsSincronizados[] = $prenda->id;

                PrendaEquivalencia::updateOrCreate(
                    ['recolector_prenda_id' => $recolectorPrenda->id],
                    ['prenda_id' => $prenda->id],
                );
            }

            Prenda::query()
                ->whereNotIn('id', $idsSincronizados ?: [0])
                ->update(['activo' => false]);
        });
    }

    private function preciosLavanderoPorClave(Collection $prendas): Collection
    {
        return $prendas
            ->sortByDesc('activo')
            ->unique(fn (Prenda $prenda) => $this->clave($prenda->nombre, $prenda->tipo))
            ->mapWithKeys(fn (Prenda $prenda) => [
                $this->clave($prenda->nombre, $prenda->tipo) => $prenda->precio ?? 0,
            ]);
    }

    private function buscarPrendaPorClave(Collection $prendas, string $clave): ?Prenda
    {
        return $prendas->first(fn (Prenda $prenda) => $this->clave($prenda->nombre, $prenda->tipo) === $clave);
    }

    private function buscarPrendaPorEquivalencia(Collection $prendas, int $recolectorPrendaId): ?Prenda
    {
        $prendaId = PrendaEquivalencia::query()
            ->where('recolector_prenda_id', $recolectorPrendaId)
            ->value('prenda_id');

        if (! $prendaId) {
            return null;
        }

        return $prendas->firstWhere('id', $prendaId) ?? Prenda::query()->find($prendaId);
    }

    private function esEquivalenciaAgrupada(?Prenda $prenda, RecolectorPrenda $recolectorPrenda): bool
    {
        if (! $prenda) {
            return false;
        }

        return $this->clave($prenda->nombre, $prenda->tipo) !== $this->clave($recolectorPrenda->nombre, $recolectorPrenda->tipo);
    }

    private function clave(?string $nombre, ?string $tipo): string
    {
        return strtolower(trim((string) $nombre)).'|'.strtolower(trim((string) $tipo));
    }

    private function normalizarTipo(?string $tipo): ?string
    {
        $tipo = trim((string) $tipo);

        return $tipo === '' ? null : $tipo;
    }
}
