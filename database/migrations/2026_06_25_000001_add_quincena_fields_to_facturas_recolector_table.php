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
            if (! Schema::hasColumn('facturas_recolector', 'quincena_origen')) {
                $table->string('quincena_origen', 25)->nullable()->after('metodo_pago')->index();
            }

            if (! Schema::hasColumn('facturas_recolector', 'quincena_pago')) {
                $table->string('quincena_pago', 25)->nullable()->after('quincena_origen')->index();
            }
        });

        $this->backfillQuincenas();
    }

    public function down(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            if (Schema::hasColumn('facturas_recolector', 'quincena_origen')) {
                $table->dropColumn('quincena_origen');
            }

            if (Schema::hasColumn('facturas_recolector', 'quincena_pago')) {
                $table->dropColumn('quincena_pago');
            }
        });
    }

    private function backfillQuincenas(): void
    {
        $expressions = match (DB::getDriverName()) {
            'pgsql' => [
                'origen' => "TO_CHAR(fecha_ingreso, 'YYYY') || '/' || TO_CHAR(fecha_ingreso, 'MM') || '/' || CASE WHEN EXTRACT(DAY FROM fecha_ingreso) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END",
                'pago' => "TO_CHAR(updated_at, 'YYYY') || '/' || TO_CHAR(updated_at, 'MM') || '/' || CASE WHEN EXTRACT(DAY FROM updated_at) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END",
            ],
            'sqlite' => [
                'origen' => "strftime('%Y', fecha_ingreso) || '/' || strftime('%m', fecha_ingreso) || '/' || CASE WHEN CAST(strftime('%d', fecha_ingreso) AS INTEGER) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END",
                'pago' => "strftime('%Y', updated_at) || '/' || strftime('%m', updated_at) || '/' || CASE WHEN CAST(strftime('%d', updated_at) AS INTEGER) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END",
            ],
            'mysql', 'mariadb' => [
                'origen' => "CONCAT(DATE_FORMAT(fecha_ingreso, '%Y/%m/'), CASE WHEN DAY(fecha_ingreso) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END)",
                'pago' => "CONCAT(DATE_FORMAT(updated_at, '%Y/%m/'), CASE WHEN DAY(updated_at) <= 15 THEN 'QUINCENA1' ELSE 'QUINCENA2' END)",
            ],
            default => null,
        };

        if (! $expressions) {
            return;
        }

        DB::statement("
            UPDATE facturas_recolector
            SET quincena_origen = ({$expressions['origen']})
            WHERE quincena_origen IS NULL
              AND fecha_ingreso IS NOT NULL
        ");

        DB::statement("
            UPDATE facturas_recolector
            SET quincena_pago = ({$expressions['pago']})
            WHERE estado_factura = 'pagado'
              AND quincena_pago IS NULL
        ");
    }
};
