<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Programa;
use App\Models\Proceso;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RespuestasEncuestaSeeder extends Seeder
{
    private const TOTAL_RESPUESTAS = 300;

    private const ESTAMENTOS_REQUIEREN_PROGRAMA = [
        'docente',
        'egresado',
        'estudiante',
    ];

    private const DISTRIBUCION_ESTAMENTOS = [
        'estudiante' => 38,
        'docente' => 20,
        'administrativo' => 18,
        'egresado' => 14,
        'sector externo' => 10,
    ];

    private const OBSERVACIONES = [
        'La atencion fue clara y cordial.',
        'El tiempo de respuesta fue adecuado.',
        'Recibi orientacion suficiente para completar el tramite.',
        'El servicio cumplio con lo esperado.',
        'La informacion entregada fue util y comprensible.',
        'Seria ideal ampliar los horarios de atencion.',
        'El proceso fue agil y bien explicado.',
        'La experiencia general fue positiva.',
        'Hubo demoras, pero finalmente se resolvio la solicitud.',
        'La senalizacion del area podria mejorar.',
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

        $year = now()->year;
        $fechaInicio = CarbonImmutable::create($year, 2, 1, 0, 0, 0, config('app.timezone'));
        $fechaFin = CarbonImmutable::create($year, 5, 20, 23, 59, 59, config('app.timezone'));
        $bloquesCalificacion = $this->bloquesCalificacion();
        $estamentosPonderados = $this->estamentosPonderados($estamentos);
        $respuestas = [];

        foreach ($estamentos as $estamento) {
            $respuestas[] = $this->crearRespuesta(
                $estamento,
                $serviciosActivos->random(),
                $programaIds,
                $bloquesCalificacion,
                $fechaInicio,
                $fechaFin
            );
        }

        foreach ($serviciosActivos->groupBy('id_proceso') as $serviciosPorProceso) {
            $respuestas[] = $this->crearRespuesta(
                $estamentosPonderados->random(),
                $serviciosPorProceso->random(),
                $programaIds,
                $bloquesCalificacion,
                $fechaInicio,
                $fechaFin
            );
        }

        foreach ($serviciosActivos->groupBy('id_dependencia') as $serviciosPorDependencia) {
            $respuestas[] = $this->crearRespuesta(
                $estamentosPonderados->random(),
                $serviciosPorDependencia->random(),
                $programaIds,
                $bloquesCalificacion,
                $fechaInicio,
                $fechaFin
            );
        }

        while (count($respuestas) < self::TOTAL_RESPUESTAS) {
            $respuestas[] = $this->crearRespuesta(
                $estamentosPonderados->random(),
                $serviciosActivos->random(),
                $programaIds,
                $bloquesCalificacion,
                $fechaInicio,
                $fechaFin
            );
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

    private function estamentosPonderados(Collection $estamentos): Collection
    {
        $estamentosPorNombre = $estamentos->keyBy(
            fn (Estamento $estamento): string => mb_strtolower($estamento->nombre)
        );

        $ponderados = collect(self::DISTRIBUCION_ESTAMENTOS)
            ->flatMap(function (int $peso, string $nombre) use ($estamentosPorNombre): array {
                $estamento = $estamentosPorNombre->get($nombre);

                if ($estamento === null) {
                    return [];
                }

                return array_fill(0, $peso, $estamento);
            })
            ->values();

        return $ponderados->isNotEmpty() ? $ponderados : $estamentos->values();
    }

    private function crearRespuesta(
        Estamento $estamento,
        object $servicio,
        Collection $programaIds,
        array $bloquesCalificacion,
        CarbonImmutable $fechaInicio,
        CarbonImmutable $fechaFin
    ): array {
        $calificacion = $bloquesCalificacion[array_rand($bloquesCalificacion)];

        return [
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
            'observaciones' => $this->observacionAleatoria(),
            'fecha_respuesta' => $this->fechaAleatoriaEntre($fechaInicio, $fechaFin),
        ];
    }

    private function observacionAleatoria(): ?string
    {
        if (random_int(1, 100) > 30) {
            return null;
        }

        return self::OBSERVACIONES[array_rand(self::OBSERVACIONES)];
    }

    private function fechaAleatoriaEntre(CarbonImmutable $fechaInicio, CarbonImmutable $fechaFin): CarbonImmutable
    {
        $segundosDisponibles = $fechaInicio->diffInSeconds($fechaFin);

        return $fechaInicio->addSeconds(random_int(0, $segundosDisponibles));
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
