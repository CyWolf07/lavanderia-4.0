<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $facturas = DB::table('facturas_recolector')->whereBetween('total', [68999, 69001])->get();
        foreach ($facturas as $f) {
            DB::table('factura_recolector_detalles')->where('factura_recolector_id', $f->id)->delete();
            DB::table('facturas_recolector')->where('id', $f->id)->delete();
        }

        // Just in case it's 69 because of decimal point
        $facturas = DB::table('facturas_recolector')->whereBetween('total', [68.9, 69.1])->get();
        foreach ($facturas as $f) {
            DB::table('factura_recolector_detalles')->where('factura_recolector_id', $f->id)->delete();
            DB::table('facturas_recolector')->where('id', $f->id)->delete();
        }

        // Just in case it's in producciones
        DB::table('producciones')->whereBetween('total', [68999, 69001])->delete();
        DB::table('producciones')->whereBetween('total', [68.9, 69.1])->delete();

        // Just in case it's in historial_producciones
        DB::table('historial_producciones')->whereBetween('total', [68999, 69001])->delete();
        DB::table('historial_producciones')->whereBetween('total', [68.9, 69.1])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration possible
    }
};
