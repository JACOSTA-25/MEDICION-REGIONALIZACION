<?php

namespace Database\Seeders;

use App\Models\Programa;
use Illuminate\Database\Seeder;

class ProgramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programas = [
            'Trabajo Social',
            'Contaduria Publica',
            'Ingenieria de Sistemas',
            'Licenciatura en Educacion Infantil',
            'Administracion de Empresas',
            'Negocios Internacionales',
            'Posgrado',
        ];

        foreach ($programas as $nombre) {
            Programa::query()->updateOrCreate(
                ['nombre' => $nombre],
                ['nombre' => $nombre]
            );
        }
    }
}
