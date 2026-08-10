<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['prendas', 'recolector_prendas'] as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'activo')) {
                continue;
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$tabla} ALTER COLUMN activo SET DEFAULT true");

                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->boolean('activo')->default(true)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['prendas', 'recolector_prendas'] as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'activo')) {
                continue;
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$tabla} ALTER COLUMN activo SET DEFAULT false");

                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->boolean('activo')->default(false)->change();
            });
        }
    }
};
