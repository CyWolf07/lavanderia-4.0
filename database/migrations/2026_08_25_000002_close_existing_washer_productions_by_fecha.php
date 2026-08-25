<?php

use App\Models\HistorialProduccion;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_producciones', function (Blueprint $table) {
            if (! Schema::hasColumn('historial_producciones', 'produccion_origen_id')) {
                $table->unsignedBigInteger('produccion_origen_id')->nullable()->after('cerrado_por')->index();
            }
        });

        Produccion::query()
            ->with('prenda')
            ->orderBy('id')
            ->chunkById(100, function ($producciones) {
                $idsMigrados = [];

                foreach ($producciones as $produccion) {
                    $fecha = Carbon::parse($produccion->fecha ?? $produccion->created_at ?? now());
                    $periodo = HistorialProduccion::periodoDesdeFecha($fecha);

                    HistorialProduccion::firstOrCreate(
                        ['produccion_origen_id' => $produccion->id],
                        [
                            'user_id' => $produccion->user_id,
                            'prenda_id' => $produccion->prenda_id,
                            'prenda_nombre' => $produccion->prenda?->nombre ?? 'Prenda eliminada',
                            'precio_unitario' => (int) $produccion->cantidad > 0
                                ? ((float) $produccion->total / (int) $produccion->cantidad)
                                : 0,
                            'cantidad' => (int) $produccion->cantidad,
                            'total' => (float) $produccion->total,
                            'fecha' => $fecha->toDateString(),
                            'periodo' => $periodo['periodo'],
                            'anio' => $periodo['anio'],
                            'mes' => $periodo['mes'],
                            'quincena' => $periodo['quincena'],
                            'cerrado_por' => null,
                        ]
                    );

                    $idsMigrados[] = $produccion->id;
                }

                if ($idsMigrados !== []) {
                    Produccion::query()->whereIn('id', $idsMigrados)->delete();
                }
            });
    }

    public function down(): void
    {
        Schema::table('historial_producciones', function (Blueprint $table) {
            if (Schema::hasColumn('historial_producciones', 'produccion_origen_id')) {
                $table->dropColumn('produccion_origen_id');
            }
        });
    }
};
