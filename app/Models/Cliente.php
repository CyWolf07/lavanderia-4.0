<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'numero_cliente',
        'celular',
        'direccion',
        'barrio',
        'activo',
        'recolector_id',
        'latitud',
        'longitud',
        'codigo_postal',
    ];

    protected function casts(): array
    {
        return [
            'activo'    => 'boolean',
            'latitud'   => 'decimal:7',
            'longitud'  => 'decimal:7',
        ];
    }

    /**
     * Siguiente número de cliente disponible.
     * Usa COUNT de registros existentes + 1 para que los clientes
     * eliminados (pruebas, errores) no desfasen la secuencia.
     */
    public static function siguienteNumero(): int
    {
        return DB::table('clientes')->count() + 1;
    }

    /**
     * Reordena todos los numero_cliente en orden ascendente según
     * la fecha de creación. Llamar tras eliminar clientes de prueba.
     */
    public static function reordenarNumeracion(): void
    {
        $clientes = DB::table('clientes')->orderBy('created_at')->pluck('id');
        foreach ($clientes as $index => $id) {
            DB::table('clientes')->where('id', $id)->update(['numero_cliente' => $index + 1]);
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function facturasRecolector()
    {
        return $this->hasMany(FacturaRecolector::class);
    }

    public function recolector()
    {
        return $this->belongsTo(User::class, 'recolector_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /** Solo clientes asignados a un recolector específico. */
    public function scopeDeRecolector($query, int $recolectorId)
    {
        return $query->where('recolector_id', $recolectorId);
    }

    /** Clientes visibles para un recolector (propios + sin asignar). */
    public function scopeVisiblesParaRecolector($query, int $recolectorId)
    {
        return $query->where(function ($q) use ($recolectorId) {
            $q->where('recolector_id', $recolectorId)
              ->orWhereNull('recolector_id');
        });
    }
}
