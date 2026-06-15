<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('barrio');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->string('codigo_postal', 10)->nullable()->after('longitud');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'codigo_postal']);
        });
    }
};
