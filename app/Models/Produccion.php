<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    use HasFactory;

    protected $table = 'producciones';

    protected $fillable = [
        'user_id',
        'prenda_id',
        'cantidad',
        'cantidad_validada',
        'total',
        'total_validado',
        'fecha',
        'estado_validacion',
        'validado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'total' => 'decimal:2',
            'total_validado' => 'decimal:2',
            'validado_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Produccion $produccion) {
            if ($produccion->cantidad_validada === null) {
                $produccion->cantidad_validada = (int) $produccion->cantidad;
            }

            if ($produccion->total_validado === null) {
                $produccion->total_validado = (float) ($produccion->total ?? 0);
            }

            if (blank($produccion->estado_validacion)) {
                $produccion->estado_validacion = 'validado';
            }

            if (in_array($produccion->estado_validacion, ['validado', 'aprobado'], true) && $produccion->validado_en === null) {
                $produccion->validado_en = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    public function incongruencias()
    {
        return $this->hasMany(IncongruenciaProduccion::class);
    }

    public function scopePagables($query)
    {
        return $query->whereIn('estado_validacion', ['validado', 'aprobado']);
    }
}
