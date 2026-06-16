<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar numero_cliente a clientes (nullable primero para datos existentes)
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedInteger('numero_cliente')->nullable()->after('nombre');
        });

        // 2. Llenar con valores ascendentes para registros existentes
        $clientes = DB::table('clientes')->orderBy('id')->get();
        foreach ($clientes as $index => $cliente) {
            DB::table('clientes')->where('id', $cliente->id)->update([
                'numero_cliente' => $index + 1,
            ]);
        }

        // 3. Hacer la columna única y no nula
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedInteger('numero_cliente')->nullable(false)->unique()->change();
        });

        // 4. Eliminar nit_cedula de clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['nit_cedula']);
            $table->dropColumn('nit_cedula');
        });

        // 5. En facturas_recolector: renombrar nit_cedula → numero_cliente
        if (Schema::hasColumn('facturas_recolector', 'nit_cedula')) {
            Schema::table('facturas_recolector', function (Blueprint $table) {
                $table->renameColumn('nit_cedula', 'numero_cliente');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['numero_cliente']);
            $table->dropColumn('numero_cliente');
            $table->string('nit_cedula', 50)->nullable()->unique();
        });

        if (Schema::hasColumn('facturas_recolector', 'numero_cliente')) {
            Schema::table('facturas_recolector', function (Blueprint $table) {
                $table->renameColumn('numero_cliente', 'nit_cedula');
            });
        }
    }
};
