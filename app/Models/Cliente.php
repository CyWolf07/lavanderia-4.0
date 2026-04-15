<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'nit_cedula',
        'celular',
        'direccion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function facturasRecolector()
    {
        return $this->hasMany(FacturaRecolector::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
