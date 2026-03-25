<?php

namespace Database\Seeders;

use App\Models\Estamento;
use App\Support\Legacy\DatosReferenciaLegado;
use Illuminate\Database\Seeder;

class EstamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DatosReferenciaLegado::estamentos() as $estamento) {
            Estamento::query()->updateOrCreate(
                ['nombre' => $estamento['nombre']],
                ['nombre' => $estamento['nombre']]
            );
        }
    }
}
