<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class EstructuraOrganizacionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estructuras = [
            [
                'proceso' => 'Recursos Fisicos y Bibliotecarios',
                'dependencias' => [
                    [
                        'nombre' => 'Biblioteca',
                        'servicios' => [
                            'Prestamo de bibliografia',
                            'Capacitacion en biblioteca virtual',
                            'Prestamo de espacios',
                        ],
                    ],
                    [
                        'nombre' => 'Mantenimiento',
                        'servicios' => [
                            'Mantenimientos',
                            'Suministro de elementos',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($estructuras as $estructura) {
            $proceso = Proceso::query()->updateOrCreate(
                ['nombre' => $estructura['proceso']],
                ['nombre' => $estructura['proceso']]
            );

            foreach ($estructura['dependencias'] as $dataDependencia) {
                $dependencia = Dependencia::query()->updateOrCreate(
                    [
                        'id_proceso' => $proceso->id_proceso,
                        'nombre' => $dataDependencia['nombre'],
                    ],
                    [
                        'id_proceso' => $proceso->id_proceso,
                        'nombre' => $dataDependencia['nombre'],
                    ]
                );

                foreach ($dataDependencia['servicios'] as $nombreServicio) {
                    Servicio::query()->updateOrCreate(
                        [
                            'id_dependencia' => $dependencia->id_dependencia,
                            'nombre' => $nombreServicio,
                        ],
                        [
                            'id_dependencia' => $dependencia->id_dependencia,
                            'nombre' => $nombreServicio,
                        ]
                    );
                }
            }
        }
    }
}
