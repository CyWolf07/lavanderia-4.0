<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->string('estado_factura', 20)->default('pendiente')->after('total');
            $table->string('metodo_pago', 30)->nullable()->after('estado_factura');
        });
    }

    public function down(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->dropColumn(['estado_factura', 'metodo_pago']);
        });
    }
};
