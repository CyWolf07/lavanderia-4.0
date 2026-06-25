<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Trazabilidad quincenal en facturas_recolector
 *
 * Agrega dos campos de quincena:
 *  - quincena_origen : quincena en que se CREÓ la factura (nunca cambia).
 *  - quincena_pago   : quincena en que se PAGÓ la factura (se asigna al marcar como pagado).
 *
 * Esto permite que una factura creada en una quincena cerrada sea pagada
 * y contabilizada en la quincena activa donde se efectúa el cobro real,
 * sin alterar la quincena de origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            // Quincena en la que se creó/registró la factura (p.ej. "2026/06/QUINCENA1")
            $table->string('quincena_origen', 25)->nullable()->after('metodo_pago')->index();

            // Quincena en la que se marcó como pagada (puede ser diferente a quincena_origen)
            $table->string('quincena_pago', 25)->nullable()->after('quincena_origen')->index();
        });

        // Backfill: poblar quincena_origen para las facturas existentes usando fecha_ingreso
        DB::statement("
            UPDATE facturas_recolector
            SET quincena_origen = (
                TO_CHAR(fecha_ingreso, 'YYYY') || '/' ||
                TO_CHAR(fecha_ingreso, 'MM')   || '/' ||
                CASE WHEN EXTRACT(DAY FROM fecha_ingreso) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END
            )
            WHERE quincena_origen IS NULL
              AND fecha_ingreso IS NOT NULL
        ");

        // Backfill: poblar quincena_pago para facturas ya pagadas usando updated_at
        DB::statement("
            UPDATE facturas_recolector
            SET quincena_pago = (
                TO_CHAR(updated_at, 'YYYY') || '/' ||
                TO_CHAR(updated_at, 'MM')   || '/' ||
                CASE WHEN EXTRACT(DAY FROM updated_at) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END
            )
            WHERE estado_factura = 'pagado'
              AND quincena_pago IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->dropColumn(['quincena_origen', 'quincena_pago']);
        });
    }
};
