<?php

namespace Database\Seeders;

use App\Models\Estamento;
use App\Support\LegacyReferenceData;
use Illuminate\Database\Seeder;

class EstamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (LegacyReferenceData::estamentos() as $estamento) {
            Estamento::query()->updateOrCreate(
                ['nombre' => $estamento['nombre']],
                ['nombre' => $estamento['nombre']]
            );
        }
    }
}
