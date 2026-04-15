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
        'total',
        'fecha' // 🔥 AGREGADO para control por día y quincena
    ];

    // 🔗 RELACIÓN USUARIO
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 RELACIÓN PRENDA
    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'total' => 'decimal:2',
        ];
    }
}
