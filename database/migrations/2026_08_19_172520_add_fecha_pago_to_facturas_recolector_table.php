<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas_recolector', 'fecha_pago')) {
                $table->timestamp('fecha_pago')
                    ->nullable()
                    ->after('quincena_pago')
                    ->comment('Timestamp exacto en que se registró el pago');
            }
        });

        // Backfill: para facturas ya pagadas sin fecha_pago, usamos updated_at como proxy
        // (updated_at refleja cuándo se cambió el estado a pagado)
        DB::statement("
            UPDATE facturas_recolector
            SET fecha_pago = updated_at
            WHERE estado_factura = 'pagado'
              AND fecha_pago IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            if (Schema::hasColumn('facturas_recolector', 'fecha_pago')) {
                $table->dropColumn('fecha_pago');
            }
        });
    }
};
