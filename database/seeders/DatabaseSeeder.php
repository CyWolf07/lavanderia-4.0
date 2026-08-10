<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Prenda;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Admin'], ['descripcion' => 'Administrador del sistema']);
        $rolProgramador = Rol::firstOrCreate(['nombre' => 'Programador'], ['descripcion' => 'Control total del sistema']);
        $rolUsuario = Rol::firstOrCreate(['nombre' => 'Usuario'], ['descripcion' => 'Empleado de produccion']);
        $rolRecolector = Rol::firstOrCreate(['nombre' => 'Recolector'], ['descripcion' => 'Ingreso de facturas y pedidos']);

        $this->crearUsuarioBaseSiFalta('admin', $rolAdmin->id, [
            'name' => 'Administrador',
            'email' => 'admin@lavanderia.com',
            'cedula' => '1000000001',
            'contacto' => '3000000001',
            'password' => Hash::make('admin123'),
        ]);

        $this->crearUsuarioBaseSiFalta('programador', $rolProgramador->id, [
            'name' => 'Programador Principal',
            'email' => 'programador@lavanderia.com',
            'cedula' => '1000000002',
            'contacto' => '3000000002',
            'password' => Hash::make('programador123'),
        ]);

        $this->crearUsuarioBaseSiFalta('usuario', $rolUsuario->id, [
            'name' => 'Empleado 1',
            'email' => 'usuario@lavanderia.com',
            'cedula' => '1000000003',
            'contacto' => '3000000003',
            'password' => Hash::make('usuario123'),
        ]);

        $this->crearUsuarioBaseSiFalta('recolector', $rolRecolector->id, [
            'name' => 'Recolector Principal',
            'email' => 'recolector@lavanderia.com',
            'cedula' => '1000000004',
            'contacto' => '3000000004',
            'password' => Hash::make('recolector123'),
        ]);

        Prenda::firstOrCreate(['nombre' => 'Camisa'], ['tipo' => 'Normal', 'precio' => 12500]);
        Prenda::firstOrCreate(['nombre' => 'Pantalon'], ['tipo' => 'Normal', 'precio' => 15000]);
        Prenda::firstOrCreate(['nombre' => 'Abrigo'], ['tipo' => 'Lavado Seco', 'precio' => 45000]);

        Cliente::firstOrCreate(
            ['nombre' => 'Hotel Plaza Central'],
            ['celular' => '3105550001', 'direccion' => 'Cra 12 #45-18', 'barrio' => 'Centro', 'activo' => true]
        );

        Cliente::firstOrCreate(
            ['nombre' => 'Maria Gomez'],
            ['celular' => '3115550002', 'direccion' => 'Calle 8 #22-10', 'barrio' => 'Norte', 'activo' => true]
        );

        $this->call(RecolectorPrendasSeeder::class);
        $this->call(LavanderoPrendasEquivalenciasSeeder::class);
    }

    private function crearUsuarioBaseSiFalta(string $rol, int $rolId, array $datos): void
    {
        $existeRol = User::query()
            ->whereRaw('LOWER(rol) = ?', [$rol])
            ->orWhere('rol_id', $rolId)
            ->exists();

        if ($existeRol) {
            return;
        }

        User::create($datos + [
            'rol' => $rol,
            'rol_id' => $rolId,
            'activo' => true,
        ]);
    }
}
