<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('factura_recolector_detalles', 'color_prenda')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE factura_recolector_detalles ALTER COLUMN color_prenda TYPE TEXT'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE factura_recolector_detalles MODIFY color_prenda TEXT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasColumn('factura_recolector_detalles', 'color_prenda')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE factura_recolector_detalles ALTER COLUMN color_prenda TYPE VARCHAR(50)'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE factura_recolector_detalles MODIFY color_prenda VARCHAR(50) NULL'),
            default => null,
        };
    }
};
