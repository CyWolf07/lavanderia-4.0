<?php

use App\Models\HistorialProduccion;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('historial_producciones')
            ->orderBy('id')
            ->chunkById(100, function ($registros) {
                foreach ($registros as $registro) {
                    if (empty($registro->fecha)) {
                        continue;
                    }

                    $periodo = HistorialProduccion::periodoDesdeFecha(Carbon::parse($registro->fecha));

                    if (
                        $registro->periodo === $periodo['periodo']
                        && (int) $registro->anio === $periodo['anio']
                        && (int) $registro->mes === $periodo['mes']
                        && (int) $registro->quincena === $periodo['quincena']
                    ) {
                        continue;
                    }

                    DB::table('historial_producciones')
                        ->where('id', $registro->id)
                        ->update([
                            'periodo' => $periodo['periodo'],
                            'anio' => $periodo['anio'],
                            'mes' => $periodo['mes'],
                            'quincena' => $periodo['quincena'],
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte: el ajuste normaliza el periodo real segun la fecha guardada.
    }
};
