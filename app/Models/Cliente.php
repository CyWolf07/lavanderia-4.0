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
     * Usa MAX + 1 para continuar desde el último número asignado.
     */
    public static function siguienteNumero(): int
    {
        $max = DB::table('clientes')->max('numero_cliente');
        return ($max ?? 0) + 1;
    }

    /**
     * Reordena todos los numero_cliente en orden ascendente según
     * la fecha de creación. Llamar tras eliminar clientes de prueba.
     */
    public static function reordenarNumeracion(): void
    {
        DB::transaction(function () {
            $clientes = self::orderBy('id')->get();
            
            // Paso 1: Mover todos a números negativos temporales para liberar el espacio positivo
            foreach ($clientes as $cliente) {
                // Usamos queries directos para evitar validaciones de modelo o eventos si los hay
                DB::table('clientes')->where('id', $cliente->id)->update(['numero_cliente' => -$cliente->id]);
            }

            // Paso 2: Asignar secuencialmente desde 1
            foreach ($clientes as $index => $cliente) {
                DB::table('clientes')->where('id', $cliente->id)->update(['numero_cliente' => $index + 1]);
            }
        });
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
