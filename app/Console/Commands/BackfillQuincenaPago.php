<?php

namespace App\Console\Commands;

use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\PagoRecolector;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillQuincenaPago extends Command
{
    protected $signature   = 'facturas:backfill-quincena-pago
                                {--dry-run : Muestra cuántas facturas se afectarán sin modificar nada}
                                {--recalcular-pagos : Recalcula los registros de PagoRecolector tras el backfill}';

    protected $description = 'Asigna quincena_pago y fecha_pago a facturas pagadas que no los tienen, '
                           . 'usando updated_at como proxy de la fecha de cobro.';

    public function handle(): int
    {
        $dryRun           = $this->option('dry-run');
        $recalcularPagos  = $this->option('recalcular-pagos');

        // ── 1. Facturas sin quincena_pago ──────────────────────────────────────
        $sinQuincena = FacturaRecolector::query()
            ->where('estado_factura', 'pagado')
            ->whereNull('quincena_pago')
            ->get();

        $this->info("Facturas pagadas sin quincena_pago: {$sinQuincena->count()}");

        if ($sinQuincena->isEmpty()) {
            $this->line('  → Nada que actualizar.');
        } elseif ($dryRun) {
            $this->warn('  → [dry-run] Se asignarían quincenas basadas en updated_at.');
        } else {
            $sinQuincena->each(function (FacturaRecolector $f) {
                $fechaProxy = $f->updated_at ?? $f->fecha_ingreso ?? now();
                $periodo    = Gasto::periodoDesdeFecha(Carbon::parse($fechaProxy));

                $f->update([
                    'quincena_pago' => $periodo['periodo'],
                    'fecha_pago'    => $fechaProxy,
                ]);
            });

            $this->info("  → {$sinQuincena->count()} facturas actualizadas con quincena_pago.");
        }

        // ── 2. Facturas pagadas sin fecha_pago (ya tienen quincena_pago) ────────
        $sinFechaPago = FacturaRecolector::query()
            ->where('estado_factura', 'pagado')
            ->whereNotNull('quincena_pago')
            ->whereNull('fecha_pago')
            ->get();

        $this->info("Facturas pagadas sin fecha_pago: {$sinFechaPago->count()}");

        if ($sinFechaPago->isEmpty()) {
            $this->line('  → Nada que actualizar.');
        } elseif ($dryRun) {
            $this->warn('  → [dry-run] Se asignaría fecha_pago = updated_at.');
        } else {
            $sinFechaPago->each(function (FacturaRecolector $f) {
                $f->update(['fecha_pago' => $f->updated_at ?? now()]);
            });

            $this->info("  → {$sinFechaPago->count()} facturas actualizadas con fecha_pago.");
        }

        // ── 3. Recalcular PagoRecolector si se pidió ───────────────────────────
        if ($recalcularPagos && ! $dryRun) {
            $this->info('Recalculando registros de PagoRecolector...');

            $quincenas = FacturaRecolector::query()
                ->where('estado_factura', 'pagado')
                ->whereNotNull('quincena_pago')
                ->selectRaw('recolector_id, quincena_pago')
                ->distinct()
                ->get();

            $quincenas->each(function ($row) {
                PagoRecolector::recalcular(
                    recolectorId: (int) $row->recolector_id,
                    quincena:     $row->quincena_pago,
                    porcentaje:   30.0
                );
            });

            $this->info("  → {$quincenas->count()} combinaciones recolector/quincena recalculadas.");
        }

        $this->newLine();
        $this->info('¡Backfill completado!');

        return self::SUCCESS;
    }
}
