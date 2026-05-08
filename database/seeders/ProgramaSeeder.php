<?php

namespace Database\Seeders;

use App\Models\Programa;
use App\Models\Sede;
use App\Support\Legacy\DatosReferenciaLegado;
use Illuminate\Database\Seeder;

class ProgramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatosReferenciaLegado::programas() as $programa) {
            Programa::query()->updateOrCreate(
                [
                    'id_sede' => Sede::ID_MAICAO,
                    'nombre' => $programa['nombre'],
                ],
                [
                    'id_sede' => Sede::ID_MAICAO,
                    'nombre' => $programa['nombre'],
                ]
            );
        }
    }
}
