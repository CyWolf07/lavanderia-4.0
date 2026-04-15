<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas_recolector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recolector_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->dateTime('fecha_ingreso');
            $table->date('fecha_entrega');
            $table->string('direccion')->nullable();
            $table->string('nit_cedula', 50)->nullable();
            $table->string('celular', 50)->nullable();
            $table->json('observaciones')->nullable();
            $table->unsignedInteger('total_prendas')->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_recolector');
    }
};
