<?php

namespace Database\Seeders;

use App\Models\Estamento;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Sede;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class RespuestasPruebaSegundoTrimestreFonsecaVillanuevaSeeder extends Seeder
{
    private const REQUIRED_ESTAMENTOS = [
        'administrativo',
        'docente',
        'egresado',
        'estudiante',
        'sector externo',
    ];

    private const ESTAMENTOS_CON_PROGRAMA = [
        'docente',
        'egresado',
        'estudiante',
    ];

    private const BASE_DATES = [
        '2026-04-05',
        '2026-04-18',
        '2026-05-07',
        '2026-05-21',
        '2026-06-10',
        '2026-06-25',
    ];

    private const ANSWER_PATTERNS = [
        [5, 4, 5, 4, 5],
        [4, 4, 5, 5, 4],
        [5, 5, 4, 4, 5],
        [4, 5, 4, 4, 4],
        [5, 4, 4, 5, 4],
    ];

    public function run(): void
    {
        $this->call([
            SedeSeeder::class,
            EstamentoSeeder::class,
            DatosPruebaFonsecaVillanuevaSeeder::class,
        ]);

        $estamentos = $this->requiredEstamentos();

        foreach ([Sede::ID_FONSECA, Sede::ID_VILLANUEVA] as $sedeId) {
            $programas = Programa::query()
                ->where('id_sede', $sedeId)
                ->orderBy('id_programa')
                ->get(['id_programa', 'nombre']);

            if ($programas->isEmpty()) {
                throw new RuntimeException("La sede {$sedeId} no tiene programas para registrar respuestas de prueba.");
            }

            $servicios = Servicio::query()
                ->with(['dependencia:id_dependencia,id_proceso'])
                ->forSede($sedeId)
                ->where('activo', true)
                ->whereHas('dependencia', fn ($dependenciaQuery) => $dependenciaQuery
                    ->where('activo', true)
                    ->whereHas('proceso', fn ($processQuery) => $processQuery->where('activo', true)))
                ->orderBy('id_dependencia')
                ->orderBy('id_servicio')
                ->get(['id_servicio', 'id_sede', 'id_dependencia', 'nombre']);

            foreach ($servicios as $serviceIndex => $servicio) {
                $dependencia = $servicio->dependencia;

                if ($dependencia === null) {
                    continue;
                }

                foreach ($estamentos as $estamentoIndex => $estamento) {
                    $estamentoKey = $this->normalizeName((string) $estamento->nombre);
                    $programaId = $this->requiresProgram($estamentoKey)
                        ? (int) $programas[($serviceIndex + $estamentoIndex) % $programas->count()]->id_programa
                        : null;

                    $dateIndex = ($serviceIndex + $estamentoIndex) % count(self::BASE_DATES);
                    $responseDate = CarbonImmutable::parse(self::BASE_DATES[$dateIndex])
                        ->setTime(8 + (($serviceIndex + $estamentoIndex) % 8), 15, 0);
                    $answers = self::ANSWER_PATTERNS[($serviceIndex + $estamentoIndex) % count(self::ANSWER_PATTERNS)];

                    Respuesta::query()->updateOrCreate(
                        [
                            'id_sede' => $sedeId,
                            'id_estamento' => (int) $estamento->id_estamento,
                            'id_programa' => $programaId,
                            'id_proceso' => (int) $dependencia->id_proceso,
                            'id_dependencia' => (int) $servicio->id_dependencia,
                            'id_servicio' => (int) $servicio->id_servicio,
                            'fecha_respuesta' => $responseDate->format('Y-m-d H:i:s'),
                        ],
                        [
                            'pregunta1' => $answers[0],
                            'pregunta2' => $answers[1],
                            'pregunta3' => $answers[2],
                            'pregunta4' => $answers[3],
                            'pregunta5' => $answers[4],
                            'observaciones' => $this->observationFor(
                                $sedeId,
                                (string) $estamento->nombre,
                                (string) $servicio->nombre,
                                $programaId !== null
                                    ? (string) $programas->firstWhere('id_programa', $programaId)?->nombre
                                    : null
                            ),
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return Collection<int, Estamento>
     */
    private function requiredEstamentos(): Collection
    {
        $estamentos = Estamento::query()
            ->orderBy('id_estamento')
            ->get(['id_estamento', 'nombre']);

        $present = $estamentos
            ->map(fn (Estamento $estamento): string => $this->normalizeName((string) $estamento->nombre))
            ->all();

        $missing = array_values(array_diff(self::REQUIRED_ESTAMENTOS, $present));

        if ($missing !== []) {
            throw new RuntimeException('Faltan estamentos requeridos para la siembra: '.implode(', ', $missing).'.');
        }

        return $estamentos->filter(
            fn (Estamento $estamento): bool => in_array(
                $this->normalizeName((string) $estamento->nombre),
                self::REQUIRED_ESTAMENTOS,
                true
            )
        )->values();
    }

    private function requiresProgram(string $estamentoKey): bool
    {
        return in_array($estamentoKey, self::ESTAMENTOS_CON_PROGRAMA, true);
    }

    private function normalizeName(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function observationFor(int $sedeId, string $estamento, string $servicio, ?string $programa): string
    {
        $sede = match ($sedeId) {
            Sede::ID_FONSECA => 'Fonseca',
            Sede::ID_VILLANUEVA => 'Villanueva',
            default => 'Sede',
        };

        $programaFragment = $programa !== null ? " desde {$programa}" : ' desde el estamento correspondiente';

        return "[PRUEBA Q2 {$sede}] {$estamento} evaluo {$servicio}{$programaFragment}; la atencion fue favorable y deja observaciones de seguimiento institucional.";
    }
}
