<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_recolector_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_recolector_id')->constrained('facturas_recolector')->cascadeOnDelete();
            $table->foreignId('recolector_prenda_id')->nullable()->constrained('recolector_prendas')->nullOnDelete();
            $table->string('prenda_nombre', 100);
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->unsignedInteger('cantidad');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_recolector_detalles');
    }
};
