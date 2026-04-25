<?php

namespace App\Services;

use App\Models\FacturaRecolector;
use App\Models\RecolectorPrenda;

class FacturaRecolectorAuditService
{
    public function detectarIncongruencias(FacturaRecolector $factura): array
    {
        $factura->loadMissing(['cliente', 'recolector', 'detalles.prenda']);

        $incongruencias = [];

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

        $cliente = $factura->cliente;
        if ($cliente) {
            $this->compararCampoCliente($incongruencias, 'NIT/Cédula', (string) ($factura->nit_cedula ?? ''), (string) ($cliente->nit_cedula ?? ''), $factura->id);
            $this->compararCampoCliente($incongruencias, 'Celular', (string) ($factura->celular ?? ''), (string) ($cliente->celular ?? ''), $factura->id);
            $this->compararCampoCliente($incongruencias, 'Dirección', (string) ($factura->direccion ?? ''), (string) ($cliente->direccion ?? ''), $factura->id);
        }

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

        return $incongruencias;
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
