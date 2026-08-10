<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producciones', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_validada')->default(0)->after('cantidad');
            $table->decimal('total_validado', 10, 2)->default(0)->after('total');
            $table->string('estado_validacion', 20)->default('validado')->after('fecha')->index();
            $table->timestamp('validado_en')->nullable()->after('estado_validacion');
        });

        DB::table('producciones')->update([
            'cantidad_validada' => DB::raw('cantidad'),
            'total_validado' => DB::raw('COALESCE(total, 0)'),
            'estado_validacion' => 'validado',
            'validado_en' => now(),
        ]);

        Schema::create('incongruencias_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->nullable()->constrained('producciones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prenda_id')->nullable()->constrained('prendas')->nullOnDelete();
            $table->date('fecha')->index();
            $table->string('prenda_nombre', 100);
            $table->string('tipo', 20)->index();
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_reportada')->default(0);
            $table->integer('diferencia');
            $table->text('detalle');
            $table->string('estado', 20)->default('pendiente')->index();
            $table->timestamp('detectada_en')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();

            $table->unique(['fecha', 'prenda_id', 'tipo', 'produccion_id'], 'incong_prod_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incongruencias_produccion');

        Schema::table('producciones', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_validada',
                'total_validado',
                'estado_validacion',
                'validado_en',
            ]);
        });
    }
};
