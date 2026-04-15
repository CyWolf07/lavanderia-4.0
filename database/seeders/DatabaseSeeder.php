<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Prenda;
use App\Models\Cliente;
use App\Models\RecolectorPrenda;
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

        User::updateOrCreate([
            'email' => 'admin@lavanderia.com',
        ], [
            'name' => 'Administrador',
            'cedula' => '1000000001',
            'contacto' => '3000000001',
            'password' => Hash::make('admin123'),
            'rol' => 'admin',
            'rol_id' => $rolAdmin->id,
        ]);

        User::updateOrCreate([
            'email' => 'programador@lavanderia.com',
        ], [
            'name' => 'Programador Principal',
            'cedula' => '1000000002',
            'contacto' => '3000000002',
            'password' => Hash::make('programador123'),
            'rol' => 'programador',
            'rol_id' => $rolProgramador->id,
        ]);

        User::updateOrCreate([
            'email' => 'usuario@lavanderia.com',
        ], [
            'name' => 'Empleado 1',
            'cedula' => '1000000003',
            'contacto' => '3000000003',
            'password' => Hash::make('usuario123'),
            'rol' => 'usuario',
            'rol_id' => $rolUsuario->id,
        ]);

        User::updateOrCreate([
            'email' => 'recolector@lavanderia.com',
        ], [
            'name' => 'Recolector Principal',
            'cedula' => '1000000004',
            'contacto' => '3000000004',
            'password' => Hash::make('recolector123'),
            'rol' => 'recolector',
            'rol_id' => $rolRecolector->id,
        ]);

        Prenda::firstOrCreate(['nombre' => 'Camisa'], ['tipo' => 'Normal', 'precio' => 12500]);
        Prenda::firstOrCreate(['nombre' => 'Pantalon'], ['tipo' => 'Normal', 'precio' => 15000]);
        Prenda::firstOrCreate(['nombre' => 'Abrigo'], ['tipo' => 'Lavado Seco', 'precio' => 45000]);

        Cliente::firstOrCreate(
            ['nit_cedula' => '900123456'],
            ['nombre' => 'Hotel Plaza Central', 'celular' => '3105550001', 'direccion' => 'Cra 12 #45-18']
        );

        Cliente::firstOrCreate(
            ['nit_cedula' => '1012345678'],
            ['nombre' => 'Maria Gomez', 'celular' => '3115550002', 'direccion' => 'Calle 8 #22-10']
        );

        RecolectorPrenda::firstOrCreate(['nombre' => 'Cobija'], ['tipo' => 'Hogar', 'precio' => 28000]);
        RecolectorPrenda::firstOrCreate(['nombre' => 'Cortina'], ['tipo' => 'Hogar', 'precio' => 32000]);
        RecolectorPrenda::firstOrCreate(['nombre' => 'Edredon'], ['tipo' => 'Hogar', 'precio' => 45000]);
    }
}
