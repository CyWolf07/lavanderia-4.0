<?php

namespace App\Services;

use App\Models\FacturaRecolector;
use App\Models\IncongruenciaProduccion;
use App\Models\Prenda;
use App\Models\PrendaEquivalencia;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProduccionValidationService
{
    public function recalcularFecha(Carbon|string $fecha): void
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        DB::transaction(function () use ($fecha) {
            IncongruenciaProduccion::query()
                ->whereDate('fecha', $fecha)
                ->where('estado', 'pendiente')
                ->delete();

            $recibidas = $this->prendasRecibidas($fecha);
            $reportadasPorPrenda = $this->prendasReportadas($fecha);
            $restantes = $recibidas->map(fn (array $item) => $item['cantidad']);

            $producciones = Produccion::query()
                ->with(['user', 'prenda'])
                ->whereDate('fecha', $fecha)
                ->orderBy('id')
                ->get();

            foreach ($producciones as $produccion) {
                if ($produccion->estado_validacion === 'aprobado') {
                    $this->marcarProduccion($produccion, (int) $produccion->cantidad, 'aprobado');

                    continue;
                }

                $prendaNombre = $produccion->prenda?->nombre ?? 'Sin prenda';
                $key = $this->normalizar($prendaNombre);
                $cantidadReportada = (int) $produccion->cantidad;
                $cantidadDisponible = (int) ($restantes->get($key, 0));
                $cantidadValidada = min($cantidadReportada, $cantidadDisponible);

                if ($cantidadValidada > 0) {
                    $restantes->put($key, $cantidadDisponible - $cantidadValidada);
                }

                $estado = $cantidadValidada === $cantidadReportada ? 'validado' : 'incongruente';
                $this->marcarProduccion($produccion, $cantidadValidada, $estado);

                if ($cantidadValidada < $cantidadReportada) {
                    $this->registrarSobrante(
                        produccion: $produccion,
                        prendaNombre: $prendaNombre,
                        recibidas: (int) ($recibidas->get($key)['cantidad'] ?? 0),
                        reportadas: (int) ($reportadasPorPrenda->get($key, 0)),
                        diferencia: $cantidadReportada - $cantidadValidada,
                    );
                }
            }

            foreach ($restantes as $key => $cantidadFaltante) {
                if ($cantidadFaltante <= 0) {
                    continue;
                }

                $recibida = $recibidas->get($key);
                $this->registrarFaltante(
                    fecha: $fecha,
                    prendaId: $recibida['prenda_id'] ?? null,
                    prendaNombre: $recibida['nombre'],
                    recibidas: (int) $recibida['cantidad'],
                    reportadas: (int) ($reportadasPorPrenda->get($key, 0)),
                    diferencia: (int) $cantidadFaltante,
                );
            }
        });
    }

    private function prendasRecibidas(string $fecha): Collection
    {
        return FacturaRecolector::query()
            ->with('detalles')
            ->noCanceladas()
            ->whereDate('fecha_ingreso', $fecha)
            ->get()
            ->flatMap->detalles
            ->groupBy(function ($detalle) {
                $prenda = $this->resolverPrendaProduccionPorDetalle($detalle);

                return $prenda
                    ? $this->normalizar($prenda->nombre)
                    : $this->normalizar($detalle->prenda_nombre);
            })
            ->map(function (Collection $detalles) {
                $primerDetalle = $detalles->first();
                $prenda = $this->resolverPrendaProduccionPorDetalle($primerDetalle);

                return [
                    'nombre' => $prenda?->nombre ?: ($primerDetalle->prenda_nombre ?: 'Sin prenda'),
                    'prenda_id' => $prenda?->id,
                    'cantidad' => (int) $detalles->sum('cantidad'),
                ];
            });
    }

    private function prendasReportadas(string $fecha): Collection
    {
        return Produccion::query()
            ->with('prenda')
            ->whereDate('fecha', $fecha)
            ->get()
            ->groupBy(fn (Produccion $produccion) => $this->normalizar($produccion->prenda?->nombre ?? ''))
            ->map(fn (Collection $producciones) => (int) $producciones->sum('cantidad'));
    }

    private function marcarProduccion(Produccion $produccion, int $cantidadValidada, string $estado): void
    {
        $precio = (float) ($produccion->prenda?->precio ?? 0);

        $produccion->update([
            'cantidad_validada' => $cantidadValidada,
            'total_validado' => $precio * $cantidadValidada,
            'estado_validacion' => $estado,
            'validado_en' => now(),
        ]);
    }

    private function registrarSobrante(
        Produccion $produccion,
        string $prendaNombre,
        int $recibidas,
        int $reportadas,
        int $diferencia,
    ): void {
        IncongruenciaProduccion::updateOrCreate(
            [
                'fecha' => $produccion->fecha?->toDateString(),
                'prenda_id' => $produccion->prenda_id,
                'tipo' => 'sobrante',
                'produccion_id' => $produccion->id,
            ],
            [
                'user_id' => $produccion->user_id,
                'prenda_nombre' => $prendaNombre,
                'cantidad_recibida' => $recibidas,
                'cantidad_reportada' => $reportadas,
                'diferencia' => $diferencia,
                'detalle' => sprintf(
                    '%s reporto %d unidad(es) de "%s" por encima de lo recibido para la fecha.',
                    $produccion->user?->name ?? 'Lavandero',
                    $diferencia,
                    $prendaNombre,
                ),
                'estado' => 'pendiente',
                'detectada_en' => now(),
            ],
        );
    }

    private function registrarFaltante(
        string $fecha,
        ?int $prendaId,
        string $prendaNombre,
        int $recibidas,
        int $reportadas,
        int $diferencia,
    ): void {
        IncongruenciaProduccion::updateOrCreate(
            [
                'fecha' => $fecha,
                'prenda_id' => $prendaId,
                'tipo' => 'faltante',
                'produccion_id' => null,
            ],
            [
                'user_id' => null,
                'prenda_nombre' => $prendaNombre,
                'cantidad_recibida' => $recibidas,
                'cantidad_reportada' => $reportadas,
                'diferencia' => $diferencia,
                'detalle' => sprintf(
                    'Ingresaron %d unidad(es) de "%s", pero solo se reportaron %d. Faltan %d.',
                    $recibidas,
                    $prendaNombre,
                    $reportadas,
                    $diferencia,
                ),
                'estado' => 'pendiente',
                'detectada_en' => now(),
            ],
        );
    }

    private function resolverPrendaProduccionId(?string $nombre): ?int
    {
        return Prenda::query()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$this->normalizar($nombre)])
            ->value('id');
    }

    private function resolverPrendaProduccionPorDetalle($detalle): ?Prenda
    {
        if ($detalle->recolector_prenda_id) {
            $prendaId = PrendaEquivalencia::query()
                ->where('recolector_prenda_id', $detalle->recolector_prenda_id)
                ->value('prenda_id');

            if ($prendaId) {
                return Prenda::query()->find($prendaId);
            }
        }

        return Prenda::query()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$this->normalizar($detalle->prenda_nombre)])
            ->first();
    }

    private function normalizar(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
