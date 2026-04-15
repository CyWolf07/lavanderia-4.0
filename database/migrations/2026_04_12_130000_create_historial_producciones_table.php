<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prenda_id')->nullable()->constrained('prendas')->nullOnDelete();
            $table->string('prenda_nombre', 100);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->integer('cantidad');
            $table->decimal('total', 12, 2);
            $table->date('fecha');
            $table->string('periodo', 25)->index();
            $table->unsignedSmallInteger('anio')->index();
            $table->unsignedTinyInteger('mes')->index();
            $table->unsignedTinyInteger('quincena')->index();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_producciones');
    }
};
