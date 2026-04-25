<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'concepto',
        'monto',
        'fecha',
        'periodo',
        'anio',
        'mes',
        'quincena',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
