<?php

namespace Database\Seeders;

use App\Models\Programa;
use App\Support\LegacyReferenceData;
use Illuminate\Database\Seeder;

class ProgramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (LegacyReferenceData::programas() as $programa) {
            Programa::query()->updateOrCreate(
                ['nombre' => $programa['nombre']],
                ['nombre' => $programa['nombre']]
            );
        }
    }
}
