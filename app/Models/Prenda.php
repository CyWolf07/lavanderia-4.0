<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prenda extends Model
{
    use HasFactory;

    protected $table = 'prendas';
    protected $fillable = ['nombre', 'tipo', 'precio', 'activo'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
