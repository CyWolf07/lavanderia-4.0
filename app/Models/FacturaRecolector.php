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
