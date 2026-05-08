<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SedeSeeder::class,
            EstamentoSeeder::class,
            ProgramaSeeder::class,
            EstructuraOrganizacionalSeeder::class,
            RespuestasEncuestaSeeder::class,
            UsuariosAdminSeeder::class,
            UsuariosAsignadosDemoSeeder::class,
        ]);
    }
}
