<?php

namespace Database\Seeders;

use App\Models\Programa;
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
                ['nombre' => $programa['nombre']],
                ['nombre' => $programa['nombre']]
            );
        }
    }
}
