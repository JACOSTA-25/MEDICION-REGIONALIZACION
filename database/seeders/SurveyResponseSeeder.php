<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Programa;
use App\Models\Proceso;
use App\Models\Servicio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyResponseSeeder extends Seeder
{
    private const TOTAL_RESPUESTAS = 140;

    private const ESTAMENTOS_REQUIEREN_PROGRAMA = [
        'docente',
        'egresado',
        'estudiante',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estamentos = Estamento::query()
            ->select(['id_estamento', 'nombre'])
            ->get();

        $programaIds = Programa::query()
            ->pluck('id_programa');

        $serviciosActivos = $this->serviciosActivosConRelacionValida();

        if ($estamentos->isEmpty() || $serviciosActivos->isEmpty()) {
            return;
        }

        $bloquesCalificacion = $this->bloquesCalificacion();
        $respuestas = [];

        for ($indice = 0; $indice < self::TOTAL_RESPUESTAS; $indice++) {
            $estamento = $estamentos->random();
            $servicio = $serviciosActivos->random();
            $calificacion = $bloquesCalificacion[array_rand($bloquesCalificacion)];

            $respuestas[] = [
                'id_estamento' => $estamento->id_estamento,
                'id_programa' => $this->estamentoRequierePrograma($estamento->nombre) && $programaIds->isNotEmpty()
                    ? $programaIds->random()
                    : null,
                'id_proceso' => $servicio->id_proceso,
                'id_dependencia' => $servicio->id_dependencia,
                'id_servicio' => $servicio->id_servicio,
                'pregunta1' => $calificacion[0],
                'pregunta2' => $calificacion[1],
                'pregunta3' => $calificacion[2],
                'pregunta4' => $calificacion[3],
                'pregunta5' => $calificacion[4],
                'pregunta6' => $calificacion[5],
                'observaciones' => null,
                'fecha_respuesta' => now()
                    ->subDays(random_int(0, 180))
                    ->subMinutes(random_int(0, 1440)),
            ];
        }

        DB::table('respuesta')->insert($respuestas);
    }

    private function serviciosActivosConRelacionValida(): Collection
    {
        return Servicio::query()
            ->join('dependencia', 'dependencia.id_dependencia', '=', 'servicio.id_dependencia')
            ->join('proceso', 'proceso.id_proceso', '=', 'dependencia.id_proceso')
            ->where('servicio.activo', true)
            ->where('dependencia.activo', true)
            ->where('proceso.activo', true)
            ->get([
                'servicio.id_servicio',
                'servicio.id_dependencia',
                'dependencia.id_proceso',
            ]);
    }

    private function estamentoRequierePrograma(string $nombreEstamento): bool
    {
        return in_array(mb_strtolower($nombreEstamento), self::ESTAMENTOS_REQUIEREN_PROGRAMA, true);
    }

    private function bloquesCalificacion(): array
    {
        return [
            [5, 5, 5, 5, 5, 5],
            [5, 5, 4, 5, 4, 5],
            [4, 4, 4, 4, 4, 4],
            [4, 5, 4, 4, 5, 4],
            [3, 3, 3, 3, 3, 3],
            [3, 4, 3, 4, 3, 4],
            [2, 2, 2, 2, 2, 2],
            [2, 3, 2, 3, 2, 3],
            [1, 1, 1, 1, 1, 1],
            [1, 2, 1, 2, 1, 2],
            [5, 4, 3, 2, 1, 3],
            [1, 3, 5, 2, 4, 2],
        ];
    }
}
