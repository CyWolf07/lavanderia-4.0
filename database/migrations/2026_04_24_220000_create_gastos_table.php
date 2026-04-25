<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('concepto', 150);
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('periodo', 25)->index();
            $table->unsignedSmallInteger('anio')->index();
            $table->unsignedTinyInteger('mes')->index();
            $table->unsignedTinyInteger('quincena')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
