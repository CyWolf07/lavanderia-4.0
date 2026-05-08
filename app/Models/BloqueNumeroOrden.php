<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueNumeroOrden extends Model
{
    protected $table = 'bloques_numero_orden';

    protected $fillable = [
        'recolector_id',
        'mes',
        'anio',
        'inicio',
        'fin',
        'siguiente',
    ];

    public function recolector()
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    /** Indica si el bloque tiene números disponibles. */
    public function tieneDisponibles(): bool
    {
        return $this->siguiente <= $this->fin;
    }
}
