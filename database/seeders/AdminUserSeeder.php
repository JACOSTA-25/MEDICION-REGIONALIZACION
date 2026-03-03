<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proceso = Proceso::query()->orderBy('id_proceso')->first();
        $dependencia = Dependencia::query()->orderBy('id_dependencia')->first();

        $users = [
            [
                'username' => env('SEED_ADMIN_USERNAME', 'JACOSTA'),
                'nombre' => env('SEED_ADMIN_NOMBRE', 'Ing. Javier Acosta Alfaro'),
                'password' => env('SEED_ADMIN_PASSWORD', 'ingjavier'),
                'rol' => User::ROLE_ADMIN,
                'id_proceso' => null,
                'id_dependencia' => null,
            ],
            [
                'username' => env('SEED_ADMIN20_USERNAME', 'ADMIN20'),
                'nombre' => env('SEED_ADMIN20_NOMBRE', 'Administrador 2.0'),
                'password' => env('SEED_ADMIN20_PASSWORD', 'admin20'),
                'rol' => User::ROLE_ADMIN_2_0,
                'id_proceso' => null,
                'id_dependencia' => null,
            ],
            [
                'username' => env('SEED_LP_USERNAME', 'LPROCESO1'),
                'nombre' => env('SEED_LP_NOMBRE', 'Lider de Proceso Demo'),
                'password' => env('SEED_LP_PASSWORD', 'liderproceso'),
                'rol' => User::ROLE_LIDER_PROCESO,
                'id_proceso' => $proceso?->id_proceso,
                'id_dependencia' => null,
            ],
            [
                'username' => env('SEED_LD_USERNAME', 'LDEPENDENCIA1'),
                'nombre' => env('SEED_LD_NOMBRE', 'Lider de Dependencia Demo'),
                'password' => env('SEED_LD_PASSWORD', 'liderdependencia'),
                'rol' => User::ROLE_LIDER_DEPENDENCIA,
                'id_proceso' => $dependencia?->id_proceso,
                'id_dependencia' => $dependencia?->id_dependencia,
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['username' => $userData['username']],
                [
                    'nombre' => $userData['nombre'],
                    'password_hash' => Hash::make($userData['password']),
                    'rol' => $userData['rol'],
                    'id_proceso' => $userData['id_proceso'],
                    'id_dependencia' => $userData['id_dependencia'],
                    'activo' => true,
                ]
            );
        }
    }
}
