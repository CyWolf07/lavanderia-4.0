<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialProduccion extends Model
{
    use HasFactory;

    protected $table = 'historial_producciones';

    protected $fillable = [
        'user_id',
        'prenda_id',
        'prenda_nombre',
        'precio_unitario',
        'cantidad',
        'total',
        'fecha',
        'periodo',
        'anio',
        'mes',
        'quincena',
        'cerrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'precio_unitario' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public static function periodoDesdeFecha(Carbon $fecha): array
    {
        $quincena = $fecha->day <= 15 ? 1 : 2;

        return [
            'anio' => (int) $fecha->year,
            'mes' => (int) $fecha->month,
            'quincena' => $quincena,
            'periodo' => sprintf('%04d/%02d/QUINCENA%d', $fecha->year, $fecha->month, $quincena),
        ];
    }
}
