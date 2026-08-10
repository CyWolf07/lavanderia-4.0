<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_equivalencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recolector_prenda_id')->nullable()->constrained('recolector_prendas')->nullOnDelete();
            $table->foreignId('prenda_id')->nullable()->constrained('prendas')->nullOnDelete();
            $table->timestamps();

            $table->unique('recolector_prenda_id');
            $table->index('prenda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_equivalencias');
    }
};
