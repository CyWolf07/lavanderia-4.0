<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaRecolectorDetalle extends Model
{
    use HasFactory;

    protected $table = 'factura_recolector_detalles';

    protected $fillable = [
        'factura_recolector_id',
        'recolector_prenda_id',
        'prenda_nombre',
        'valor_unitario',
        'cantidad',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'valor_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function factura()
    {
        return $this->belongsTo(FacturaRecolector::class, 'factura_recolector_id');
    }

    public function prenda()
    {
        return $this->belongsTo(RecolectorPrenda::class, 'recolector_prenda_id');
    }
}
