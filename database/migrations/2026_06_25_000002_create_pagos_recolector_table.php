<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla pagos_recolector
 *
 * Registra el cálculo de la comisión del 30% por quincena y por recolector.
 * Cada vez que se procesa un pago de factura, el sistema recalcula y actualiza
 * (o crea) el registro de comisión para la quincena de pago correspondiente.
 *
 * Estructura del flujo:
 *  1. Admin marca una factura como "pagado".
 *  2. updateFacturaEstado asigna quincena_pago = quincena activa actual.
 *  3. Se recalcula el total pagado en esa quincena para el recolector.
 *  4. Se guarda/actualiza el registro en pagos_recolector (30% del total).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_recolector', function (Blueprint $table) {
            $table->id();

            // Recolector al que pertenece este pago de comisión
            $table->foreignId('recolector_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Quincena a la que corresponde este cálculo (p.ej. "2026/06/QUINCENA2")
            $table->string('quincena', 25)->index();

            // Suma total de facturas pagadas del recolector en esta quincena
            $table->decimal('total_facturas', 14, 2)->default(0);

            // Porcentaje aplicado (30 por defecto, guardado para auditoría)
            $table->decimal('porcentaje', 5, 2)->default(30.00);

            // Monto calculado de comisión = total_facturas * porcentaje / 100
            $table->decimal('monto_comision', 14, 2)->default(0);

            // Número de facturas pagadas contabilizadas en este cálculo
            $table->unsignedInteger('cantidad_facturas')->default(0);

            // ¿Ya fue desembolsado/pagado al recolector?
            $table->boolean('pagado_al_recolector')->default(false);
            $table->timestamp('pagado_en')->nullable();

            $table->timestamps();

            // Un recolector solo tiene un registro de comisión por quincena
            $table->unique(['recolector_id', 'quincena']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_recolector');
    }
};
