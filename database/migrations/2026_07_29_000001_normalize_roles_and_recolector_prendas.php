<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicarRecolectorPrendas();
        $this->sincronizarRolesUsuarios();
    }

    public function down(): void
    {
        //
    }

    private function deduplicarRecolectorPrendas(): void
    {
        if (! Schema::hasTable('recolector_prendas')) {
            return;
        }

        $grupos = DB::table('recolector_prendas')
            ->selectRaw("LOWER(TRIM(nombre)) as nombre_key, LOWER(TRIM(COALESCE(tipo, ''))) as tipo_key, COUNT(*) as cantidad")
            ->groupBy('nombre_key', 'tipo_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($grupos as $grupo) {
            $duplicados = DB::table('recolector_prendas')
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [$grupo->nombre_key])
                ->whereRaw("LOWER(TRIM(COALESCE(tipo, ''))) = ?", [$grupo->tipo_key])
                ->orderByDesc('activo')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $principal = $duplicados->first();

            if (! $principal) {
                continue;
            }

            $idsDuplicados = $duplicados->pluck('id')
                ->filter(fn ($id) => (int) $id !== (int) $principal->id)
                ->values();

            if ($idsDuplicados->isEmpty()) {
                continue;
            }

            if (Schema::hasTable('factura_recolector_detalles')) {
                DB::table('factura_recolector_detalles')
                    ->whereIn('recolector_prenda_id', $idsDuplicados->all())
                    ->update(['recolector_prenda_id' => $principal->id]);
            }

            DB::table('recolector_prendas')
                ->whereIn('id', $idsDuplicados->all())
                ->delete();
        }
    }

    private function sincronizarRolesUsuarios(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return;
        }

        $roles = DB::table('roles')->get()
            ->mapWithKeys(fn ($rol) => [strtolower(trim((string) $rol->nombre)) => $rol]);

        foreach (['admin', 'programador', 'usuario', 'recolector'] as $nombreRol) {
            if (! $roles->has($nombreRol)) {
                $id = DB::table('roles')->insertGetId([
                    'nombre' => ucfirst($nombreRol),
                    'descripcion' => 'Rol '.$nombreRol.' del sistema',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $roles->put($nombreRol, (object) [
                    'id' => $id,
                    'nombre' => ucfirst($nombreRol),
                ]);
            }
        }

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function ($user) use ($roles) {
                $rolTexto = strtolower(trim((string) ($user->rol ?? '')));
                $rolRelacion = null;

                if (! empty($user->rol_id)) {
                    $rolRelacion = $roles->first(fn ($rol) => (int) $rol->id === (int) $user->rol_id);
                }

                if (! in_array($rolTexto, ['admin', 'programador', 'usuario', 'recolector'], true) && $rolRelacion) {
                    $rolTexto = strtolower(trim((string) $rolRelacion->nombre));
                }

                if (! in_array($rolTexto, ['admin', 'programador', 'usuario', 'recolector'], true)) {
                    $rolTexto = 'usuario';
                }

                $rol = $roles->get($rolTexto);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'rol' => $rolTexto,
                        'rol_id' => $rol->id,
                        'activo' => $user->activo ?? true,
                        'updated_at' => now(),
                    ]);
            });
    }
};
