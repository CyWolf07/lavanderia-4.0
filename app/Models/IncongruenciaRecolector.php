<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncongruenciaRecolector extends Model
{
    use HasFactory;

    protected $table = 'incongruencias_recolector';

    protected $fillable = [
        'factura_recolector_id',
        'recolector_id',
        'cliente_id',
        'titulo',
        'detalle',
        'estado',
        'detectada_en',
    ];

    protected function casts(): array
    {
        return [
            'detectada_en' => 'datetime',
        ];
    }

    public function factura()
    {
        return $this->belongsTo(FacturaRecolector::class, 'factura_recolector_id');
    }

    public function recolector()
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
