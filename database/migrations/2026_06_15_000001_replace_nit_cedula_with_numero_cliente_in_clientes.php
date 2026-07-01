<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Paso 1: Agregar numero_cliente a clientes (si no existe ya) ─────────
        if (! Schema::hasColumn('clientes', 'numero_cliente')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unsignedInteger('numero_cliente')->nullable()->after('nombre');
            });

            // Llenar con valores ascendentes para registros existentes
            $clientes = DB::table('clientes')->orderBy('id')->get();
            foreach ($clientes as $index => $cliente) {
                DB::table('clientes')->where('id', $cliente->id)->update([
                    'numero_cliente' => $index + 1,
                ]);
            }

            // Hacer la columna única y no nula
            Schema::table('clientes', function (Blueprint $table) {
                $table->unsignedInteger('numero_cliente')->nullable(false)->unique()->change();
            });
        }

        // ── Paso 2: Eliminar nit_cedula de clientes (si todavía existe) ─────────
        if (Schema::hasColumn('clientes', 'nit_cedula')) {
            Schema::table('clientes', function (Blueprint $table) {
                // Intenta eliminar el índice único si existe
                try {
                    $table->dropUnique(['nit_cedula']);
                } catch (\Throwable) {
                    // El índice ya no existe — ignorar
                }
                $table->dropColumn('nit_cedula');
            });
        }

        // ── Paso 3: En facturas_recolector renombrar nit_cedula → numero_cliente ─
        if (Schema::hasColumn('facturas_recolector', 'nit_cedula')) {
            Schema::table('facturas_recolector', function (Blueprint $table) {
                $table->renameColumn('nit_cedula', 'numero_cliente');
            });
        }
    }

    public function down(): void
    {
        // Revertir numero_cliente → nit_cedula en clientes
        if (Schema::hasColumn('clientes', 'numero_cliente')) {
            Schema::table('clientes', function (Blueprint $table) {
                try { $table->dropUnique(['numero_cliente']); } catch (\Throwable) {}
                $table->dropColumn('numero_cliente');
            });
        }

        if (! Schema::hasColumn('clientes', 'nit_cedula')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('nit_cedula', 50)->nullable()->unique();
            });
        }

        // Revertir numero_cliente → nit_cedula en facturas_recolector
        if (Schema::hasColumn('facturas_recolector', 'numero_cliente')) {
            Schema::table('facturas_recolector', function (Blueprint $table) {
                $table->renameColumn('numero_cliente', 'nit_cedula');
            });
        }
    }
};
