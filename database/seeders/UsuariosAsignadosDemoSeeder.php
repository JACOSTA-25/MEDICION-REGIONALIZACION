<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosAsignadosDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dependencies = Dependencia::query()
            ->with('proceso:id_proceso')
            ->orderBy('id_dependencia')
            ->get(['id_dependencia', 'id_proceso']);
        $processes = Proceso::query()
            ->orderBy('id_proceso')
            ->get(['id_proceso']);

        if ($dependencies->isEmpty() || $processes->isEmpty()) {
            return;
        }

        $defaultPassword = env('SEED_DEMO_USERS_PASSWORD', 'demo12345');
        $totalDependencyLeaders = 30;
        $totalProcessLeaders = 15;
        $totalDependencies = $dependencies->count();
        $totalProcesses = $processes->count();

        for ($index = 1; $index <= $totalDependencyLeaders; $index++) {
            $dependency = $dependencies->get(($index - 1) % $totalDependencies);
            $username = sprintf('UDEMO%02d', $index);

            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'nombre' => sprintf('Usuario Demo %02d', $index),
                    'password_hash' => Hash::make($defaultPassword),
                    'rol' => User::ROLE_LIDER_DEPENDENCIA,
                    'id_proceso' => (int) $dependency->id_proceso,
                    'id_dependencia' => (int) $dependency->id_dependencia,
                    'activo' => true,
                ]
            );
        }

        for ($index = 1; $index <= $totalProcessLeaders; $index++) {
            $process = $processes->get(($index - 1) % $totalProcesses);
            $username = sprintf('UPROC%02d', $index);

            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'nombre' => sprintf('Usuario Proceso %02d', $index),
                    'password_hash' => Hash::make($defaultPassword),
                    'rol' => User::ROLE_LIDER_PROCESO,
                    'id_proceso' => (int) $process->id_proceso,
                    'id_dependencia' => null,
                    'activo' => true,
                ]
            );
        }
    }
}
