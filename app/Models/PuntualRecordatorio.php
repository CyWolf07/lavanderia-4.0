<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PuntualRecordatorio extends Model
{
    protected $table = 'puntual_recordatorios';

    protected $guarded = ['id'];

    public function factura()
    {
        return $this->belongsTo(FacturaRecolector::class, 'factura_recolector_id');
    }

    public function scopeVigentes($query)
    {
        return $query->whereHas('factura', fn ($factura) => $factura->noCanceladas()
            ->whereHas('recolector', fn ($user) => $user->where('activo', true))
            ->whereNull('entregado_en')
            ->whereColumn('facturas_recolector.recolector_id', 'puntual_recordatorios.user_id')
            ->whereDate('facturas_recolector.fecha_entrega', '=', DB::raw('puntual_recordatorios.fecha_entrega')));
    }
}
