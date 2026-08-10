<?php

namespace Database\Seeders;

use App\Models\PrendaEquivalencia;
use App\Models\RecolectorPrenda;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LavanderoPrendasEquivalenciasSeeder extends Seeder
{
    private const TIPO_LAVADO = 'LAVADO';

    private array $prendasBase = [
        1 => ['nombre' => 'CHAQUETA', 'precio' => null],
        3 => ['nombre' => 'COBIJA', 'precio' => null],
        5 => ['nombre' => 'SACO', 'precio' => null],
        8 => ['nombre' => 'VESTIDO DAMA / CABALLERO', 'precio' => null],
        11 => ['nombre' => 'SABANA', 'precio' => null],
        14 => ['nombre' => 'ALFOMBRA PEQUENA', 'precio' => null],
        15 => ['nombre' => 'PELUCHE PEQUENO', 'precio' => null],
        17 => ['nombre' => 'ZAPATOS', 'precio' => null],
        19 => ['nombre' => 'ALFOMBRA GRANDE', 'precio' => null],
        21 => ['nombre' => 'CORTINA', 'precio' => null],
        22 => ['nombre' => 'PELUCHE GRANDE', 'precio' => null],
        104 => ['nombre' => 'CUBRELECHO', 'precio' => 1200],
        105 => ['nombre' => 'PLUMON', 'precio' => 1200],
    ];

    private array $valoresLavandero = [
        'BATAS' => 900,
        'BLUSAS' => 800,
        'CASCO ABIERTO' => 2500,
        'FALDAS' => 900,
    ];

    private array $equivalencias = [
        1 => [
            'CHAQUETA DELGADA CORTA',
            'CHAQUETA DELGADA PEQUENA DAMA O NINO',
            'CHAQUETA GRUESA CORTA',
            'CHAQUETA GRUESA LARGA',
            'CHAQUETA PLUMA DE GANSO',
        ],
        3 => [
            'COBIJA CAMA DOBLE DELGADA',
            'COBIJA CAMA DOBLE GRUESA',
            'COBIJA CAMA SENCILLA DELGADA',
            'COBIJA CAMA SENCILLA GRUESA',
        ],
        5 => [
            'SACOS DE VESTIDO',
            'SACOS VESTIDO',
        ],
        8 => [
            'VESTIDO DE CABALLERO',
            'VESTIDO CABALLERO',
            'VESTIDO DE DAMA',
            'VESTIDO DAMA',
        ],
        11 => [
            'SABANA CAMA DOBLE',
            'SABANA CAMA SENCILLA',
        ],
        14 => [
            'TAPETE 1M2',
            'TAPETE 2M2',
            'TAPETE 3M2',
            'TAPETE 1 METRO CUADRADO',
            'TAPETE 2 METROS CUADRADOS',
            'TAPETE 3 METROS CUADRADOS',
        ],
        15 => [
            'PELUCHE 30 CM',
            'PELUCHE 30 CENTIMETROS',
            'PELUCHE 40 CM',
            'PELUCHE 40 CENTIMETROS',
            'PELUCHE 50 CM',
            'PELUCHE 50 CENTIMETROS',
        ],
        17 => [
            'ZAPATOS GRD',
            'ZAPATOS GRANDES',
            'ZAPATOS PQ',
            'ZAPATOS PEQUENOS',
        ],
        19 => [
            'TAPETE 4M2',
            'TAPETE 5M2',
            'TAPETE 6M2',
            'TAPETE GRUESO 6M2',
            'TAPETE 4 METROS CUADRADOS',
            'TAPETE 5 METROS CUADRADOS',
            'TAPETE 6 METROS CUADRADOS',
        ],
        21 => [
            'CORTINA GRANDE',
            'CORTINA GRD',
            'CORTINA MEDIANA',
            'CORTINA MD',
            'CORTINA PEQUENA',
            'CORTINA PQ',
        ],
        22 => [
            'PELUCHE 60 CM',
            'PELUCHE 60 CENTIMETROS',
            'PELUCHE 70 CM',
            'PELUCHE 70 CENTIMETROS',
            'PELUCHE 80 CM',
            'PELUCHE 80 CENTIMETROS',
            'PELUCHE 80CM 100CM',
            'PELUCHE 100 CM',
            'PELUCHE 100 CENTIMETROS',
        ],
        104 => [
            'CUBRELECHO CAMA DOBLE DELGADA',
            'CUBRELECHO CAMA DOBLE GRUESA',
            'CUBRELECHO CAMA SENCILLA DELGADA',
            'CUBRELECHO CAMA SENCILLA GRUESA',
        ],
        105 => [
            'PLUMON CAMA DOBLE',
            'PLUMON CAMA SENCILLA',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->prendasBase as $id => $prenda) {
                $this->upsertPrenda((int) $id, $prenda['nombre'], $prenda['precio']);
            }

            foreach ($this->valoresLavandero as $nombre => $precio) {
                $this->upsertPrendaPorNombre($nombre, $precio);
            }

            $recolectorPrendas = RecolectorPrenda::query()->get();
            $porNombreNormalizado = $recolectorPrendas->groupBy(fn (RecolectorPrenda $prenda) => $this->normalizar($prenda->nombre));
            $equivalenciasCreadas = 0;

            foreach ($this->equivalencias as $prendaId => $nombresRecolector) {
                foreach ($nombresRecolector as $nombreRecolector) {
                    foreach ($porNombreNormalizado->get($this->normalizar($nombreRecolector), collect()) as $recolectorPrenda) {
                        PrendaEquivalencia::updateOrCreate(
                            ['recolector_prenda_id' => $recolectorPrenda->id],
                            ['prenda_id' => (int) $prendaId],
                        );

                        $equivalenciasCreadas++;
                    }
                }
            }

            $this->ajustarSecuenciaPrendas();
            $this->command?->info($equivalenciasCreadas.' equivalencias de prendas lavandero sincronizadas correctamente.');
        });
    }

    private function upsertPrenda(int $id, string $nombre, ?int $precio): void
    {
        $existente = DB::table('prendas')->where('id', $id)->first();
        $datos = [
            'nombre' => $nombre,
            'tipo' => self::TIPO_LAVADO,
            'activo' => true,
            'updated_at' => now(),
        ];

        if ($precio !== null || ! $existente) {
            $datos['precio'] = $precio ?? 0;
        }

        if ($existente) {
            DB::table('prendas')->where('id', $id)->update($datos);

            return;
        }

        DB::table('prendas')->insert($datos + [
            'id' => $id,
            'created_at' => now(),
        ]);
    }

    private function upsertPrendaPorNombre(string $nombre, int $precio): void
    {
        $existente = DB::table('prendas')
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($nombre))])
            ->first();

        $datos = [
            'nombre' => $nombre,
            'tipo' => self::TIPO_LAVADO,
            'precio' => $precio,
            'activo' => true,
            'updated_at' => now(),
        ];

        if ($existente) {
            DB::table('prendas')->where('id', $existente->id)->update($datos);

            return;
        }

        DB::table('prendas')->insert($datos + [
            'created_at' => now(),
        ]);
    }

    private function ajustarSecuenciaPrendas(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('prendas', 'id'), COALESCE((SELECT MAX(id) FROM prendas), 1))");
    }

    private function normalizar(?string $valor): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::ascii(strtolower((string) $valor))) ?: '';
    }
}
