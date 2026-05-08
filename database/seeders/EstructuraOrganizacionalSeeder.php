<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Servicio;
use App\Support\Legacy\DatosReferenciaLegado;
use Illuminate\Database\Seeder;

class EstructuraOrganizacionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatosReferenciaLegado::organizationalStructure() as $estructura) {
            $proceso = Proceso::query()->updateOrCreate(
                ['id_proceso' => $estructura['id_proceso']],
                [
                    'id_sede' => Sede::ID_MAICAO,
                    'nombre' => $estructura['nombre'],
                ]
            );

            foreach ($estructura['dependencias'] as $dataDependencia) {
                $dependencia = Dependencia::query()->updateOrCreate(
                    ['id_dependencia' => $dataDependencia['id_dependencia']],
                    [
                        'id_sede' => Sede::ID_MAICAO,
                        'id_proceso' => $proceso->id_proceso,
                        'nombre' => $dataDependencia['nombre'],
                    ]
                );

                foreach ($dataDependencia['servicios'] as $servicio) {
                    Servicio::query()->updateOrCreate(
                        ['id_servicio' => $servicio['id_servicio']],
                        [
                            'id_sede' => Sede::ID_MAICAO,
                            'id_dependencia' => $dependencia->id_dependencia,
                            'nombre' => $servicio['nombre'],
                        ]
                    );
                }
            }
        }
    }
}
