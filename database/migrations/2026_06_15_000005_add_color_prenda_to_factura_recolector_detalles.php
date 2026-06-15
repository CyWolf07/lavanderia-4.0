<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_recolector_detalles', function (Blueprint $table) {
            $table->string('color_prenda', 50)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('factura_recolector_detalles', function (Blueprint $table) {
            $table->dropColumn('color_prenda');
        });
    }
};
