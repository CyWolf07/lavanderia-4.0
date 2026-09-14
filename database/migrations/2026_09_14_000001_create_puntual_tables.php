<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->timestamp('entregado_en')->nullable();
            $table->foreignId('entregado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->index('fecha_entrega');
        });
        Schema::create('puntual_recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_recolector_id')->constrained('facturas_recolector')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('fecha_entrega');
            $table->date('fecha_aviso');
            $table->string('tipo', 16);
            $table->timestamp('leido_en')->nullable();
            $table->timestamp('push_en')->nullable();
            $table->timestamps();
            $table->unique(['factura_recolector_id', 'user_id', 'fecha_entrega', 'tipo'], 'puntual_aviso_unico');
        });
        Schema::create('puntual_suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntual_suscripciones');
        Schema::dropIfExists('puntual_recordatorios');
        Schema::table('facturas_recolector', function (Blueprint $table) {
            $table->dropForeign(['entregado_por']);
            $table->dropIndex(['fecha_entrega']);
            $table->dropColumn(['entregado_en', 'entregado_por']);
        });
    }
};
