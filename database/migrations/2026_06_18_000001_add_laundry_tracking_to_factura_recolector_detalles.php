<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_recolector_detalles', function (Blueprint $table) {
            $table->foreignId('lavado_por')->nullable()->after('subtotal')->constrained('users')->nullOnDelete();
            $table->timestamp('lavado_en')->nullable()->after('lavado_por');
            $table->foreignId('produccion_id')->nullable()->after('lavado_en')->constrained('producciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('factura_recolector_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produccion_id');
            $table->dropConstrainedForeignId('lavado_por');
            $table->dropColumn('lavado_en');
        });
    }
};
