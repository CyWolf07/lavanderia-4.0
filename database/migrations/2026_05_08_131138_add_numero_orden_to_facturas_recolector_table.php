<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            // Nullable para no romper registros existentes; se llenará justo después
            $table->unsignedBigInteger('numero_orden')->nullable()->after('id');
        });

        // Asignar numero_orden a registros existentes basándonos en el ID
        DB::statement('UPDATE facturas_recolector SET numero_orden = id');

        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->unique('numero_orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->dropUnique(['numero_orden']);
            $table->dropColumn('numero_orden');
        });
    }
};
