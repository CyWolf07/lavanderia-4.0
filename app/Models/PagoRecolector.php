<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo: PagoRecolector
 *
 * Representa el registro de la comisión del 30% calculada para un recolector
 * en una quincena específica. Se actualiza automáticamente cada vez que se
 * procesa el pago de una factura en AdminController::updateFacturaEstado().
 *
 * @property int    $id
 * @property int    $recolector_id
 * @property string $quincena          Periodo (ej: "2026/06/QUINCENA2")
 * @property float  $total_facturas    Suma de facturas pagadas en la quincena
 * @property float  $porcentaje        Porcentaje de comisión (default 30.00)
 * @property float  $monto_comision    total_facturas * porcentaje / 100
 * @property int    $cantidad_facturas Número de facturas contabilizadas
 * @property bool   $pagado_al_recolector
 * @property ?\Carbon\Carbon $pagado_en
 */
class PagoRecolector extends Model
{
    use HasFactory;

    protected $table = 'pagos_recolector';

    protected $fillable = [
        'recolector_id',
        'quincena',
        'total_facturas',
        'porcentaje',
        'monto_comision',
        'cantidad_facturas',
        'pagado_al_recolector',
        'pagado_en',
    ];

    protected function casts(): array
    {
        return [
            'total_facturas'       => 'decimal:2',
            'porcentaje'           => 'decimal:2',
            'monto_comision'       => 'decimal:2',
            'pagado_al_recolector' => 'boolean',
            'pagado_en'            => 'datetime',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function recolector()
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Filtrar por quincena exacta */
    public function scopeDeQuincena($query, string $quincena)
    {
        return $query->where('quincena', $quincena);
    }

    /** Solo los pendientes de desembolso */
    public function scopePendientesDePago($query)
    {
        return $query->where('pagado_al_recolector', false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Recalcula y guarda (upsert) el pago de comisión de un recolector
     * para una quincena dada, basándose en todas sus facturas pagadas
     * en esa quincena (campo quincena_pago de facturas_recolector).
     *
     * @param  int    $recolectorId
     * @param  string $quincena     Periodo (ej: "2026/06/QUINCENA2")
     * @param  float  $porcentaje   Porcentaje de comisión (default 30)
     * @return static
     */
    public static function recalcular(int $recolectorId, string $quincena, float $porcentaje = 30.0): static
    {
        $facturas = FacturaRecolector::query()
            ->where('recolector_id', $recolectorId)
            ->where('estado_factura', 'pagado')
            ->where('quincena_pago', $quincena)
            ->get();

        $totalFacturas    = (float) $facturas->sum('total');
        $cantidadFacturas = $facturas->count();
        $montoComision    = round($totalFacturas * $porcentaje / 100, 2);

        return static::updateOrCreate(
            ['recolector_id' => $recolectorId, 'quincena' => $quincena],
            [
                'total_facturas'    => $totalFacturas,
                'porcentaje'        => $porcentaje,
                'monto_comision'    => $montoComision,
                'cantidad_facturas' => $cantidadFacturas,
            ]
        );
    }
}
