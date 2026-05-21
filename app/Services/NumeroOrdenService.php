<?php

namespace App\Services;

use App\Models\BloqueNumeroOrden;
use App\Models\FacturaRecolector;
use Illuminate\Support\Facades\DB;

class NumeroOrdenService
{
    private const BLOQUE_TAMAÑO = 600;

    /**
     * Devuelve y consume el siguiente número de orden para el recolector dado.
     * Si el bloque activo está agotado (o no existe), crea uno nuevo.
     */
    public function obtenerSiguiente(int $recolectorId): int
    {
        return DB::transaction(function () use ($recolectorId) {
            // Bloque activo: el más reciente donde aún hay números disponibles
            $bloque = BloqueNumeroOrden::where('recolector_id', $recolectorId)
                ->whereColumn('siguiente', '<=', 'fin')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $bloque) {
                $bloque = $this->crearNuevoBloque($recolectorId);
            }

            $numero = $bloque->siguiente;
            $bloque->siguiente += 1;
            $bloque->save();

            return $numero;
        });
    }

    /**
     * Consulta cuál sería el próximo número sin consumirlo (para mostrar en formulario).
     */
    public function peekSiguiente(int $recolectorId): int
    {
        $bloque = BloqueNumeroOrden::where('recolector_id', $recolectorId)
            ->whereColumn('siguiente', '<=', 'fin')
            ->orderByDesc('id')
            ->first();

        if (! $bloque) {
            // Calcular cuál sería el inicio del nuevo bloque
            $maxFin = BloqueNumeroOrden::max('fin') ?? 0;

            return $maxFin + 1;
        }

        return $bloque->siguiente;
    }

    /**
     * Reajusta el consecutivo global tras eliminar la orden con el número dado.
     * Decrementa en 1 todos los números de orden posteriores y ajusta los bloques.
     */
    public function reajustar(int $numeroOrdenEliminado): void
    {
        DB::transaction(function () use ($numeroOrdenEliminado) {

            // 1. Decrementar todos los numero_orden > eliminado
            FacturaRecolector::where('numero_orden', '>', $numeroOrdenEliminado)
                ->decrement('numero_orden');

            // 2. Ajustar bloques con inicio > eliminado (todo el bloque se desplaza -1)
            $bloquesPosteriores = BloqueNumeroOrden::where('inicio', '>', $numeroOrdenEliminado)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($bloquesPosteriores as $bloque) {
                $bloque->inicio -= 1;
                $bloque->fin -= 1;
                $bloque->siguiente -= 1;
                $bloque->save();
            }

            // 3. Ajustar el bloque que CONTENÍA el número eliminado
            $bloqueAfectado = BloqueNumeroOrden::where('inicio', '<=', $numeroOrdenEliminado)
                ->where('fin', '>=', $numeroOrdenEliminado)
                ->lockForUpdate()
                ->first();

            if ($bloqueAfectado) {
                $bloqueAfectado->fin -= 1;

                // Si el puntero ya había pasado el número eliminado, también lo desplazamos
                if ($bloqueAfectado->siguiente > $numeroOrdenEliminado) {
                    $bloqueAfectado->siguiente -= 1;
                }

                $bloqueAfectado->save();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function crearNuevoBloque(int $recolectorId): BloqueNumeroOrden
    {
        // El inicio del nuevo bloque es el máximo fin global + 1
        $maxFin = BloqueNumeroOrden::max('fin') ?? 0;
        $inicio = $maxFin + 1;
        $fin = $inicio + self::BLOQUE_TAMAÑO - 1;   // +599 → 600 números

        return BloqueNumeroOrden::create([
            'recolector_id' => $recolectorId,
            'mes' => now()->month,
            'anio' => now()->year,
            'inicio' => $inicio,
            'fin' => $fin,
            'siguiente' => $inicio,
        ]);
    }
}
