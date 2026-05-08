<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bloques_numero_orden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recolector_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');        // 1–12
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('inicio');      // primer número del bloque
            $table->unsignedBigInteger('fin');         // último número del bloque (inicio + 599)
            $table->unsignedBigInteger('siguiente');   // próximo número a asignar
            $table->timestamps();

            $table->index(['recolector_id', 'mes', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloques_numero_orden');
    }
};
