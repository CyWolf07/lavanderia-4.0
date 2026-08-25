<?php

use App\Models\HistorialProduccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('historial_producciones', 'produccion_origen_id')) {
            return;
        }

        $periodoActual = HistorialProduccion::periodoDesdeFecha(now())['periodo'];
        $idsHistorialRestaurados = [];

        DB::table('historial_producciones')
            ->where('periodo', $periodoActual)
            ->whereNull('cerrado_por')
            ->whereNotNull('produccion_origen_id')
            ->orderBy('id')
            ->chunkById(100, function ($registros) use (&$idsHistorialRestaurados) {
                foreach ($registros as $registro) {
                    $produccionOrigenId = (int) $registro->produccion_origen_id;

                    if ($produccionOrigenId <= 0) {
                        continue;
                    }

                    if (DB::table('producciones')->where('id', $produccionOrigenId)->exists()) {
                        $idsHistorialRestaurados[] = $registro->id;
                        continue;
                    }

                    if ($registro->prenda_id === null) {
                        continue;
                    }

                    DB::table('producciones')->insert([
                        'id' => $produccionOrigenId,
                        'user_id' => $registro->user_id,
                        'prenda_id' => $registro->prenda_id,
                        'cantidad' => $registro->cantidad,
                        'cantidad_validada' => $registro->cantidad,
                        'total' => $registro->total,
                        'total_validado' => $registro->total,
                        'fecha' => $registro->fecha,
                        'estado_validacion' => 'validado',
                        'validado_en' => $registro->created_at ?? now(),
                        'created_at' => $registro->created_at ?? now(),
                        'updated_at' => $registro->updated_at ?? now(),
                    ]);

                    $idsHistorialRestaurados[] = $registro->id;
                }
            });

        if ($idsHistorialRestaurados !== []) {
            DB::table('historial_producciones')
                ->whereIn('id', $idsHistorialRestaurados)
                ->delete();
        }
    }

    public function down(): void
    {
        //
    }
};
