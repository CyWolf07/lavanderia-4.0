<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncongruenciaProduccion extends Model
{
    use HasFactory;

    protected $table = 'incongruencias_produccion';

    protected $fillable = [
        'produccion_id',
        'user_id',
        'prenda_id',
        'fecha',
        'prenda_nombre',
        'tipo',
        'cantidad_recibida',
        'cantidad_reportada',
        'diferencia',
        'detalle',
        'estado',
        'detectada_en',
        'aprobado_por',
        'aprobado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'detectada_en' => 'datetime',
            'aprobado_en' => 'datetime',
        ];
    }

    public function produccion()
    {
        return $this->belongsTo(Produccion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
