<?php

namespace Tests\Feature;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Servicio;
use App\Services\ReportService;
use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'null']);
        $compiledPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'medicion-views';

        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config(['view.compiled' => $compiledPath]);

        $this->seed([
            EstamentoSeeder::class,
            ProgramaSeeder::class,
            EstructuraOrganizacionalSeeder::class,
        ]);
    }

    public function test_report_service_calculates_expected_statistics_and_indicators(): void
    {
        [$serviceA, $serviceB] = $this->sampleServicesInDifferentProcesses();

        $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
        $administrativo = Estamento::query()->where('nombre', 'Administrativo')->firstOrFail();
        $programa = Programa::query()->firstOrFail();

        $this->storeResponse(
            $estudiante->id_estamento,
            $programa->id_programa,
            $serviceA->id_proceso,
            $serviceA->id_dependencia,
            $serviceA->id_servicio,
            [5, 4, 3, 2, 1, 5],
            '2026-01-05 10:00:00'
        );

        $this->storeResponse(
            $administrativo->id_estamento,
            null,
            $serviceB->id_proceso,
            $serviceB->id_dependencia,
            $serviceB->id_servicio,
            [4, 4, 4, 4, 4, 4],
            '2026-01-08 15:00:00'
        );

        $this->storeResponse(
            $estudiante->id_estamento,
            $programa->id_programa,
            $serviceA->id_proceso,
            $serviceA->id_dependencia,
            $serviceA->id_servicio,
            [1, 2, 3, 4, 5, 2],
            '2026-01-12 09:30:00'
        );

        $this->storeResponse(
            $estudiante->id_estamento,
            $programa->id_programa,
            $serviceA->id_proceso,
            $serviceA->id_dependencia,
            $serviceA->id_servicio,
            [5, 5, 5, 5, 5, 5],
            '2025-12-20 09:30:00'
        );

        $service = app(ReportService::class);
        $report = $service->generate('general', '2026-01-01', '2026-01-31');

        $this->assertSame(3, $report['totals']['survey_count']);
        $this->assertSame(18, $report['totals']['answer_count']);
        $this->assertCount(6, $report['questions']);
        $this->assertCount(6, $report['tables']['satisfaction_consolidated']);
        $this->assertCount(6, $report['charts']['question_results']);

        $questionOne = collect($report['questions'])->firstWhere('number', 1);
        $this->assertNotNull($questionOne);
        $this->assertSame(1, $questionOne['frequencies'][0]['frequency']);
        $this->assertSame(1, $questionOne['frequencies'][3]['frequency']);
        $this->assertSame(1, $questionOne['frequencies'][4]['frequency']);
        $this->assertEquals(66.67, $questionOne['satisfaction']['satisfied_percentage']);

        $programRows = collect($report['tables']['by_program'])->keyBy('programa');
        $this->assertSame(2, $programRows[$programa->nombre]['encuestas']);
        $this->assertSame(1, $programRows['Sin programa']['encuestas']);

        $this->assertEquals(61.0, $report['indicators']['global']['satisfaction_percentage']);

        $processFiltered = $service->generate('process', '2026-01-01', '2026-01-31', $serviceA->id_proceso);
        $this->assertSame(2, $processFiltered['totals']['survey_count']);

        $dependencyFiltered = $service->generate('individual', '2026-01-01', '2026-01-31', null, $serviceB->id_dependencia);
        $this->assertSame(1, $dependencyFiltered['totals']['survey_count']);
    }

    /**
     * @return array{0: object{id_servicio: int, id_dependencia: int, id_proceso: int}, 1: object{id_servicio: int, id_dependencia: int, id_proceso: int}}
     */
    private function sampleServicesInDifferentProcesses(): array
    {
        $dependencyA = Dependencia::query()->orderBy('id_dependencia')->firstOrFail();
        $dependencyB = Dependencia::query()
            ->where('id_proceso', '!=', $dependencyA->id_proceso)
            ->orderBy('id_dependencia')
            ->firstOrFail();

        $serviceA = Servicio::query()->where('id_dependencia', $dependencyA->id_dependencia)->firstOrFail();
        $serviceB = Servicio::query()->where('id_dependencia', $dependencyB->id_dependencia)->firstOrFail();

        return [
            (object) [
                'id_servicio' => (int) $serviceA->id_servicio,
                'id_dependencia' => (int) $dependencyA->id_dependencia,
                'id_proceso' => (int) $dependencyA->id_proceso,
            ],
            (object) [
                'id_servicio' => (int) $serviceB->id_servicio,
                'id_dependencia' => (int) $dependencyB->id_dependencia,
                'id_proceso' => (int) $dependencyB->id_proceso,
            ],
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int}  $answers
     */
    private function storeResponse(
        int $estamentoId,
        ?int $programaId,
        int $procesoId,
        int $dependenciaId,
        int $servicioId,
        array $answers,
        string $fechaRespuesta
    ): void {
        Respuesta::query()->create([
            'id_estamento' => $estamentoId,
            'id_programa' => $programaId,
            'id_proceso' => $procesoId,
            'id_dependencia' => $dependenciaId,
            'id_servicio' => $servicioId,
            'pregunta1' => $answers[0],
            'pregunta2' => $answers[1],
            'pregunta3' => $answers[2],
            'pregunta4' => $answers[3],
            'pregunta5' => $answers[4],
            'pregunta6' => $answers[5],
            'observaciones' => null,
            'fecha_respuesta' => $fechaRespuesta,
        ]);
    }
}
