<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        $sedes = [
            [
                'id_sede' => Sede::ID_MAICAO,
                'codigo' => 'MAICAO',
                'slug' => 'maicao',
                'nombre' => 'Sede Maicao',
            ],
            [
                'id_sede' => Sede::ID_FONSECA,
                'codigo' => 'FONSECA',
                'slug' => 'fonseca',
                'nombre' => 'Sede Fonseca',
            ],
            [
                'id_sede' => Sede::ID_VILLANUEVA,
                'codigo' => 'VILLANUEVA',
                'slug' => 'villanueva',
                'nombre' => 'Sede Villanueva',
            ],
            [
                'id_sede' => Sede::ID_REGIONALIZACION,
                'codigo' => 'REGIONALIZACION',
                'slug' => 'regionalizacion',
                'nombre' => 'Sede Regionalizacion',
            ],
        ];

        foreach ($sedes as $sede) {
            Sede::query()->updateOrCreate(
                ['id_sede' => $sede['id_sede']],
                [
                    'codigo' => $sede['codigo'],
                    'slug' => $sede['slug'],
                    'nombre' => $sede['nombre'],
                    'activo' => true,
                ]
            );
        }
    }
}
