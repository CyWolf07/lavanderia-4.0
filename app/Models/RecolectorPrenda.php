<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecolectorPrenda extends Model
{
    use HasFactory;

    protected $table = 'recolector_prendas';

    protected $fillable = [
        'nombre',
        'tipo',
        'precio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function detallesFactura()
    {
        return $this->hasMany(FacturaRecolectorDetalle::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
