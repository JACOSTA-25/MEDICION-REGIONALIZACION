<?php

namespace Database\Seeders;

use App\Models\Estamento;
use Illuminate\Database\Seeder;

class EstamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estamentos = [
            ['nombre' => 'Estudiante'],
            ['nombre' => 'Docente'],
            ['nombre' => 'Egresado'],
            ['nombre' => 'Administrativo'],
            ['nombre' => 'Sector externo'],
        ];

        foreach ($estamentos as $estamento) {
            Estamento::query()->updateOrCreate(
                ['nombre' => $estamento['nombre']],
                $estamento
            );
        }
    }
}
