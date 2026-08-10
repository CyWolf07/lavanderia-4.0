<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaEquivalencia extends Model
{
    use HasFactory;

    protected $table = 'prenda_equivalencias';

    protected $fillable = [
        'recolector_prenda_id',
        'prenda_id',
    ];

    public function recolectorPrenda()
    {
        return $this->belongsTo(RecolectorPrenda::class);
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }
}
