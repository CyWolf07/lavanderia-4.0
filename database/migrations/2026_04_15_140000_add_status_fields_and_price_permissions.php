<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('contacto');
            $table->boolean('puede_editar_precios')->default(false)->after('activo');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('direccion');
        });

        Schema::table('prendas', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('precio');
        });

        Schema::table('recolector_prendas', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('recolector_prendas', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('prendas', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['activo', 'puede_editar_precios']);
        });
    }
};
