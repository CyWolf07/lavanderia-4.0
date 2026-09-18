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

    protected static function booted(): void
    {
        static::deleting(function (Prenda $prenda) {
            if ($prenda->producciones()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'prenda' => 'La prenda tiene produccion registrada. Inhabilitala en lugar de eliminarla.',
                ]);
            }
        });
    }

    public function equivalenciasRecolector()
    {
        return $this->hasMany(PrendaEquivalencia::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
