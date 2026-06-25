<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaRecolector extends Model
{
    use HasFactory;

    protected $table = 'facturas_recolector';

    protected $fillable = [
        'numero_orden',
        'recolector_id',
        'cliente_id',
        'fecha_ingreso',
        'fecha_entrega',
        'direccion',
        'numero_cliente',
        'celular',
        'observaciones',
        'total_prendas',
        'total',
        'estado_factura',
        'metodo_pago',
        'quincena_origen',   // quincena en que se creó la factura (inmutable)
        'quincena_pago',     // quincena activa cuando se marcó como pagado
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_entrega' => 'date',
            'observaciones' => 'array',
            'total'         => 'decimal:2',
        ];
    }

    // ── Scopes adicionales ────────────────────────────────────────────────────

    /** Facturas pagadas en una quincena concreta (por quincena_pago). */
    public function scopePagadasEnQuincena($query, string $quincena)
    {
        return $query
            ->where('estado_factura', 'pagado')
            ->where('quincena_pago', $quincena);
    }

    /** Facturas creadas en una quincena concreta (por quincena_origen). */
    public function scopeCreadasEnQuincena($query, string $quincena)
    {
        return $query->where('quincena_origen', $quincena);
    }

    public function estaPagada(): bool
    {
        return $this->estado_factura === 'pagado';
    }

    public function estaCancelada(): bool
    {
        return $this->estado_factura === 'cancelado';
    }

    public function scopeNoCanceladas($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('estado_factura')
                ->orWhere('estado_factura', '!=', 'cancelado');
        });
    }

    public function recolector()
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(FacturaRecolectorDetalle::class, 'factura_recolector_id');
    }

    public function incongruencias()
    {
        return $this->hasMany(IncongruenciaRecolector::class, 'factura_recolector_id');
    }
}
