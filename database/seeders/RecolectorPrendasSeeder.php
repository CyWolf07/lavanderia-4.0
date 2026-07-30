<?php

namespace Database\Seeders;

use App\Models\RecolectorPrenda;
use Illuminate\Database\Seeder;

class RecolectorPrendasSeeder extends Seeder
{
    public function run(): void
    {
        $prendas = [
            ['nombre' => 'VESTIDO DE CABALLERO', 'tipo' => 'LAVADO', 'precio' => 24000.00],
            ['nombre' => 'VESTIDO DE DAMA', 'tipo' => 'LAVADO', 'precio' => 24000.00],
            ['nombre' => 'CHAQUETA DELGADA CORTA', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'CHAQUETA GRUESA CORTA', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'CHAQUETA GRUESA LARGA', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'CHAQUETA DELGADA PEQUEÑA DAMA O NIÑO', 'tipo' => 'LAVADO', 'precio' => 15000.00],
            ['nombre' => 'CHAQUETA PLUMA DE GANSO', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'CHAQUETA PLUMA DE GANSO', 'tipo' => 'LAVADO', 'precio' => 38000.00],
            ['nombre' => 'ABRIGO O GABARDINA', 'tipo' => 'LAVADO', 'precio' => 22000.00],
            ['nombre' => 'JARDINERA', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'SACOS DE VESTIDO', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'PANTALON', 'tipo' => 'LAVADO', 'precio' => 9000.00],
            ['nombre' => 'BATAS', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'BLUSAS', 'tipo' => 'LAVADO', 'precio' => 9000.00],
            ['nombre' => 'CHALECOS', 'tipo' => 'LAVADO', 'precio' => 8000.00],
            ['nombre' => 'FALDAS', 'tipo' => 'LAVADO', 'precio' => 9000.00],
            ['nombre' => 'CAMISAS', 'tipo' => 'LAVADO', 'precio' => 9000.00],
            ['nombre' => 'SUETERS', 'tipo' => 'LAVADO', 'precio' => 15000.00],
            ['nombre' => 'CORTINA Grd', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'CORTINA MD', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'CORTINA PQ', 'tipo' => 'LAVADO', 'precio' => 15000.00],
            ['nombre' => 'CUBRELECHO - CAMA DOBLE GRUESA', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'COBIJA - CAMA SENCILLA DELGADA', 'tipo' => 'LAVADO', 'precio' => 15000.00],
            ['nombre' => 'COBIJA - CAMA DOBLE DELGADA', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'COBIJA - CAMA DOBLE GRUESA', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'COBIJA - CAMA SENCILLA GRUESA', 'tipo' => 'LAVADO', 'precio' => 17999.99],
            ['nombre' => 'CUBRELECHO - CAMA DOBLE DELGADA', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'CUBRELECHO - CAMA SENCILLA GRUESA', 'tipo' => 'LAVADO', 'precio' => 22000.00],
            ['nombre' => 'CUBRELECHO - CAMA SENCILLA DELGADA', 'tipo' => 'LAVADO', 'precio' => 18000.00],
            ['nombre' => 'PLUMON - CAMA SENCILLA', 'tipo' => 'LAVADO', 'precio' => 38000.00],
            ['nombre' => 'PLUMON - CAMA DOBLE', 'tipo' => 'LAVADO', 'precio' => 42000.00],
            ['nombre' => 'SABANA - CAMA DOBLE', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'SABANA - CAMA SENCILLA', 'tipo' => 'LAVADO', 'precio' => 17999.99],
            ['nombre' => 'CASCO', 'tipo' => 'LAVADO', 'precio' => 30000.00],
            ['nombre' => 'CASCO ABIERTO', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'ZAPATOS GRD', 'tipo' => 'LAVADO', 'precio' => 28000.00],
            ['nombre' => 'ZAPATOS PQ', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'PELUCHE 80cm - 100cm', 'tipo' => 'LAVADO', 'precio' => 75000.00],
            ['nombre' => 'PELUCHE 70cm', 'tipo' => 'LAVADO', 'precio' => 52000.00],
            ['nombre' => 'PELUCHE 60cm', 'tipo' => 'LAVADO', 'precio' => 47999.99],
            ['nombre' => 'PELUCHE 50cm', 'tipo' => 'LAVADO', 'precio' => 42000.00],
            ['nombre' => 'PELUCHE 40cm', 'tipo' => 'LAVADO', 'precio' => 35000.00],
            ['nombre' => 'PELUCHE 30 cm', 'tipo' => 'LAVADO', 'precio' => 25000.00],
            ['nombre' => 'TAPETE (1m^2)', 'tipo' => 'LAVADO', 'precio' => 20000.00],
            ['nombre' => 'TAPETE (2m^2)', 'tipo' => 'LAVADO', 'precio' => 37000.00],
            ['nombre' => 'TAPETE (3m^2)', 'tipo' => 'LAVADO', 'precio' => 45000.00],
            ['nombre' => 'TAPETE (4m^2)', 'tipo' => 'LAVADO', 'precio' => 57999.99],
            ['nombre' => 'TAPETE (6m^2)', 'tipo' => 'LAVADO', 'precio' => 72000.00],
            ['nombre' => 'TAPETE (5m^2)', 'tipo' => 'LAVADO', 'precio' => 65000.00],
            ['nombre' => 'TAPETE GRUESO (6m^2)', 'tipo' => 'LAVADO', 'precio' => 85000.00],
            ['nombre' => 'PLANCHADO (c/u)', 'tipo' => 'ADICIONAL', 'precio' => 6000.00],
            ['nombre' => 'POR KILO (4 prendas pq =1kg)', 'tipo' => 'ADICIONAL', 'precio' => 25000.00],
            ['nombre' => 'SERVICIO PREMIUM', 'tipo' => 'EXTRA', 'precio' => 8000.00],
            ['nombre' => 'SECADO (prendas de vestir, sabanas, manteles)', 'tipo' => 'ADICIONAL', 'precio' => 10000.00],
            ['nombre' => 'SECADO ( cobijas, cubrelechos)', 'tipo' => 'ADICIONAL', 'precio' => 14999.99],
        ];

        RecolectorPrenda::query()->update(['activo' => false]);

        foreach ($prendas as $prenda) {
            RecolectorPrenda::updateOrCreate(
                [
                    'nombre' => $prenda['nombre'],
                    'tipo' => $prenda['tipo'],
                ],
                [
                    'precio' => $prenda['precio'],
                    'activo' => true,
                ]
            );
        }

        $this->command?->info(count($prendas).' prendas de recolector sincronizadas correctamente.');
    }
}
