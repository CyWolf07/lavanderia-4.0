<?php

namespace App\Services;

use App\Models\FacturaRecolector;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;

class FacturaRecolectorAuditService
{
    public function detectarIncongruencias(FacturaRecolector $factura): array
    {
        $factura->loadMissing(['cliente', 'recolector', 'detalles.prenda']);

        $incongruencias = [];

        // ── 1. Verificar que total_prendas coincide con suma de detalles ─────
        $sumatoriaPrendas = (int) $factura->detalles->sum('cantidad');
        if ((int) $factura->total_prendas !== $sumatoriaPrendas) {
            $incongruencias[] = [
                'titulo' => 'Total de prendas inconsistente',
                'detalle' => sprintf(
                    'La factura #%d registra %d prendas, pero la sumatoria de detalles es %d.',
                    $factura->id,
                    (int) $factura->total_prendas,
                    $sumatoriaPrendas
                ),
            ];
        }

        // ── 2. Verificar que total monetario coincide con suma de subtotales ─
        $sumatoriaTotal = (float) $factura->detalles->sum('subtotal');
        if (abs((float) $factura->total - $sumatoriaTotal) > 0.01) {
            $incongruencias[] = [
                'titulo' => 'Total monetario inconsistente',
                'detalle' => sprintf(
                    'La factura #%d tiene total %s, pero la suma de subtotales es %s.',
                    $factura->id,
                    number_format((float) $factura->total, 2, '.', ''),
                    number_format($sumatoriaTotal, 2, '.', '')
                ),
            ];
        }

        // ── 3. Verificar datos del cliente ────────────────────────────────────
        $cliente = $factura->cliente;
        if ($cliente) {
            $this->compararCampoCliente(
                $incongruencias, 'N° de cliente',
                (string) ($factura->numero_cliente ?? ''),
                (string) ($cliente->numero_cliente ?? ''),
                $factura->id
            );
            $this->compararCampoCliente(
                $incongruencias, 'Celular',
                (string) ($factura->celular ?? ''),
                (string) ($cliente->celular ?? ''),
                $factura->id
            );
            $this->compararCampoCliente(
                $incongruencias, 'Dirección',
                (string) ($factura->direccion ?? ''),
                (string) ($cliente->direccion ?? ''),
                $factura->id
            );
        }

        // ── 4. Verificar cada detalle de prenda ───────────────────────────────
        foreach ($factura->detalles as $detalle) {
            $prenda = $detalle->prenda;
            if (! $prenda instanceof RecolectorPrenda) {
                $incongruencias[] = [
                    'titulo' => 'Prenda inexistente en detalle',
                    'detalle' => sprintf(
                        'La factura #%d incluye un detalle sin prenda válida (detalle #%d).',
                        $factura->id,
                        $detalle->id
                    ),
                ];

                continue;
            }

            if (trim((string) $detalle->prenda_nombre) !== trim((string) $prenda->nombre)) {
                $incongruencias[] = [
                    'titulo' => 'Nombre de prenda no concuerda',
                    'detalle' => sprintf(
                        'Factura #%d: detalle "%s" no coincide con prenda registrada "%s".',
                        $factura->id,
                        $detalle->prenda_nombre,
                        $prenda->nombre
                    ),
                ];
            }

            if (! $factura->recolector?->puedeEditarPrecios() && abs((float) $detalle->valor_unitario - (float) $prenda->precio) > 0.01) {
                $incongruencias[] = [
                    'titulo' => 'Precio no permitido para recolector',
                    'detalle' => sprintf(
                        'Factura #%d: valor unitario %s no coincide con precio oficial %s para la prenda "%s".',
                        $factura->id,
                        number_format((float) $detalle->valor_unitario, 2, '.', ''),
                        number_format((float) $prenda->precio, 2, '.', ''),
                        $prenda->nombre
                    ),
                ];
            }
        }

        // ── 5. Comparar prendas del recolector con lo que registró el lavandero
        //       en producción el mismo día de ingreso de la factura ─────────────
        $this->compararConProduccionLavandero($incongruencias, $factura);

        return $incongruencias;
    }

    /**
     * Compara las prendas que entregó el recolector (detalles de la factura)
     * con las prendas que marcó el lavandero (usuario) en producción
     * en el mismo día/fecha de ingreso de la factura.
     *
     * Si el total de prendas del recolector es mayor al total ingresado
     * por los lavanderos ese día, se genera incongruencia.
     */
    private function compararConProduccionLavandero(array &$incongruencias, FacturaRecolector $factura): void
    {
        $fechaIngreso = optional($factura->fecha_ingreso)->toDateString();
        if (! $fechaIngreso) {
            return;
        }

        // Total de prendas que registró el recolector para este cliente
        $totalRecolector = (int) $factura->total_prendas;

        // Total de prendas que registraron TODOS los lavanderos (usuarios) en esa misma fecha
        $totalLavanderos = (int) Produccion::whereDate('fecha', $fechaIngreso)->sum('cantidad');

        if ($totalLavanderos === 0) {
            // No hay producción registrada por ningún lavandero en esa fecha
            $incongruencias[] = [
                'titulo' => 'Sin producción de lavandero el día de ingreso',
                'detalle' => sprintf(
                    'Factura #%d: el recolector registró %d prendas el %s, pero ningún lavandero (usuario) registró producción ese día.',
                    $factura->id,
                    $totalRecolector,
                    $fechaIngreso
                ),
            ];
            return;
        }

        // Si el recolector entregó más prendas de las que procesaron los lavanderos
        if ($totalRecolector > $totalLavanderos) {
            $incongruencias[] = [
                'titulo' => 'Recolector entregó más prendas de las procesadas',
                'detalle' => sprintf(
                    'Factura #%d (%s): recolector entregó %d prendas pero los lavanderos solo procesaron %d prendas en total ese día.',
                    $factura->id,
                    $fechaIngreso,
                    $totalRecolector,
                    $totalLavanderos
                ),
            ];
        }

        // Comparar tipos de prendas: recolector usa RecolectorPrenda, lavandero usa Prenda
        // Si hay prendas del recolector con nombres que no coinciden con ninguna prenda registrada ese día
        $prendasLavandero = Produccion::with('prenda')
            ->whereDate('fecha', $fechaIngreso)
            ->get()
            ->groupBy(fn ($p) => strtolower(trim($p->prenda?->nombre ?? '')))
            ->map(fn ($items) => (int) $items->sum('cantidad'));

        foreach ($factura->detalles as $detalle) {
            $nombreRecolector = strtolower(trim($detalle->prenda_nombre ?? ''));
            if ($nombreRecolector === '') {
                continue;
            }

            $cantidadLavandero = $prendasLavandero->get($nombreRecolector, null);

            if ($cantidadLavandero === null) {
                // Esta prenda específica no aparece en la producción del día
                $incongruencias[] = [
                    'titulo' => 'Prenda del recolector no registrada por lavandero',
                    'detalle' => sprintf(
                        'Factura #%d: "%s" (x%d) entregada por el recolector, pero ningún lavandero registró esta prenda el %s.',
                        $factura->id,
                        $detalle->prenda_nombre,
                        $detalle->cantidad,
                        $fechaIngreso
                    ),
                ];
            } elseif ($detalle->cantidad > $cantidadLavandero) {
                // El recolector dice haber entregado más de lo que procesó el lavandero
                $incongruencias[] = [
                    'titulo' => 'Cantidad de prenda excede producción del lavandero',
                    'detalle' => sprintf(
                        'Factura #%d: "%s" — recolector: x%d, lavandero ese día: x%d.',
                        $factura->id,
                        $detalle->prenda_nombre,
                        $detalle->cantidad,
                        $cantidadLavandero
                    ),
                ];
            }
        }
    }

    private function compararCampoCliente(array &$incongruencias, string $campo, string $facturaValor, string $registroValor, int $facturaId): void
    {
        $valorFactura = trim($facturaValor);
        $valorRegistro = trim($registroValor);

        if ($valorFactura !== $valorRegistro) {
            $incongruencias[] = [
                'titulo' => $campo.' no concuerda',
                'detalle' => sprintf(
                    'Factura #%d: %s en factura "%s" difiere del cliente registrado "%s".',
                    $facturaId,
                    $campo,
                    $valorFactura !== '' ? $valorFactura : 'vacío',
                    $valorRegistro !== '' ? $valorRegistro : 'vacío'
                ),
            ];
        }
    }
}

