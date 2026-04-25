<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incongruencias_recolector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_recolector_id')->constrained('facturas_recolector')->cascadeOnDelete();
            $table->foreignId('recolector_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('titulo', 150);
            $table->text('detalle');
            $table->string('estado', 20)->default('pendiente')->index();
            $table->timestamp('detectada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incongruencias_recolector');
    }
};
