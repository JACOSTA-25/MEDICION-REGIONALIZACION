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
            EstamentoSeeder::class,
            ProgramaSeeder::class,
            EstructuraOrganizacionalSeeder::class,
            SurveyResponseSeeder::class,
            AdminUserSeeder::class,
            DemoAssignedUsersSeeder::class,
        ]);
    }
}
