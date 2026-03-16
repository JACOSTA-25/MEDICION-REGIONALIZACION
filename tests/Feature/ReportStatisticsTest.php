<?php

namespace Tests\Feature;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Programa;
use App\Models\ReportingQuarter;
use App\Models\Respuesta;
use App\Models\Servicio;
use App\Models\User;
use App\Services\PdfChartImageService;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
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

        ini_set('memory_limit', '512M');
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

    public function test_general_report_route_uses_the_selected_quarter_configuration(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            [$serviceA] = $this->sampleServicesInDifferentProcesses();

            $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
            $programa = Programa::query()->firstOrFail();
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

            ReportingQuarter::query()->create([
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-10',
                'end_date' => '2026-03-31',
                'updated_by' => $admin->id,
            ]);

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [5, 5, 5, 5, 5, 5],
                '2026-01-09 08:00:00'
            );

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [4, 4, 4, 4, 4, 4],
                '2026-01-10 08:00:00'
            );

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [3, 3, 3, 3, 3, 3],
                '2026-03-31 08:00:00'
            );

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [2, 2, 2, 2, 2, 2],
                '2026-04-01 08:00:00'
            );

            $response = $this->actingAs($admin)
                ->get(route('reports.general', ['trimestre' => 1]));

            $response->assertOk();
            $response->assertViewHas('selectedQuarterNumber', 1);
            $response->assertViewHas('selectedQuarterPeriod', '10/01/2026 a 31/03/2026');
            $response->assertViewHas('report', fn (array $report): bool => $report['totals']['survey_count'] === 2
                && $report['from'] === '2026-01-10'
                && $report['to'] === '2026-03-31');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_export_view_renders_cover_subtitle_for_each_report_type(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            [$serviceA] = $this->sampleServicesInDifferentProcesses();

            $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
            $programa = Programa::query()->firstOrFail();
            $dependency = Dependencia::query()->findOrFail($serviceA->id_dependencia);
            $processName = (string) ($dependency->proceso?->nombre ?? 'Proceso seleccionado');
            $dependencyName = (string) $dependency->nombre;
            $processLeaderName = 'BRENDA BRITO ARREGOCES';
            $dependencyLeaderName = 'CARLOS PEREZ IGUARAN';

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [4, 4, 4, 4, 4, 4],
                '2026-10-15 08:00:00'
            );

            $report = app(ReportService::class)->generate('general', '2026-10-01', '2026-12-31');
            $chartImages = app(PdfChartImageService::class)->build($report);

            $generalHtml = view('reports.export', [
                'chartImages' => $chartImages,
                'contextRows' => [
                    ['label' => 'Trimestre', 'value' => 'IV Trimestre'],
                    ['label' => 'Periodo', 'value' => '01/10/2026 a 31/12/2026'],
                ],
                'description' => 'Reporte general',
                'printFallback' => false,
                'report' => $report,
                'reportType' => 'general',
                'signature' => null,
                'title' => 'Reporte general',
            ])->render();

            $processHtml = view('reports.export', [
                'chartImages' => $chartImages,
                'contextRows' => [
                    ['label' => 'Trimestre', 'value' => 'IV Trimestre'],
                    ['label' => 'Periodo', 'value' => '01/10/2026 a 31/12/2026'],
                    ['label' => 'Proceso', 'value' => $processName],
                ],
                'description' => 'Reporte por proceso',
                'printFallback' => false,
                'report' => $report,
                'reportType' => 'process',
                'signature' => [
                    'name' => $processLeaderName,
                    'title' => 'Lider del proceso de',
                    'scope' => mb_strtoupper($processName, 'UTF-8'),
                ],
                'title' => 'Reporte por proceso',
            ])->render();

            $dependencyHtml = view('reports.export', [
                'chartImages' => $chartImages,
                'contextRows' => [
                    ['label' => 'Trimestre', 'value' => 'IV Trimestre'],
                    ['label' => 'Periodo', 'value' => '01/10/2026 a 31/12/2026'],
                    ['label' => 'Proceso', 'value' => $processName],
                    ['label' => 'Dependencia', 'value' => $dependencyName],
                ],
                'description' => 'Reporte individual',
                'printFallback' => false,
                'report' => $report,
                'reportType' => 'individual',
                'signature' => [
                    'name' => $dependencyLeaderName,
                    'title' => 'Lider de la dependencia',
                    'scope' => mb_strtoupper($dependencyName, 'UTF-8'),
                ],
                'title' => 'Reporte individual',
            ])->render();

            $this->assertStringContainsString('SEDE MAICAO IV TRIMESTRE', $generalHtml);
            $this->assertStringContainsString('PROCESO DE '.mb_strtoupper($processName, 'UTF-8').' IV TRIMESTRE', $processHtml);
            $this->assertStringContainsString('DEPENDENCIA '.mb_strtoupper($dependencyName, 'UTF-8').' IV TRIMESTRE', $dependencyHtml);
            $this->assertStringContainsString('2026', $generalHtml);
            $this->assertStringContainsString('class="cover-logo"', $generalHtml);
            $this->assertStringContainsString('alt="Logo de portada"', $generalHtml);
            $this->assertStringContainsString(
                'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE FORMA GENERAL (2026)',
                $generalHtml
            );
            $this->assertStringContainsString(
                'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE '.mb_strtoupper($processName, 'UTF-8').' (2026)',
                $processHtml
            );
            $this->assertStringContainsString(
                'CONCLUSIONES DE LA MEDICION DE LA SATISFACCION DE LOS USUARIOS DE '.mb_strtoupper($dependencyName, 'UTF-8').' (2026)',
                $dependencyHtml
            );
            $this->assertStringContainsString(
                'Medir el grado de satisfaccion por parte de los usuarios, partes interesadas y grupos de valor con relacion a los servicios brindados por la dependencia durante el periodo.',
                $dependencyHtml
            );
            $this->assertStringNotContainsString('servicios brindados por el dependencia', $dependencyHtml);
            $this->assertStringContainsString(
                'El numero de usuarios encuestados durante el IV Trimestre de 2026 fue de 1 usuario a los cuales se les presto el servicio en todos los procesos.',
                $generalHtml
            );
            $this->assertStringContainsString(
                'El 100% se siente satisfecho con la atencion del funcionario en la oficina '.mb_strtoupper($processName, 'UTF-8').', durante el IV Trimestre de 2026.',
                $processHtml
            );
            $this->assertStringContainsString(
                'El 100% se siente satisfecho con el lenguaje usado por los funcionarios en la dependencia '.mb_strtoupper($dependencyName, 'UTF-8').', durante el IV Trimestre de 2026.',
                $dependencyHtml
            );
            $this->assertStringContainsString(
                'En torno a los resultados obtenidos se presento un 0% donde los usuarios perciben un servicio ni satisfactorio ni insatisfactorio, teniendo en cuenta esta informacion se adelantaran acciones para mejorar el nivel de satisfaccion de estos usuarios.',
                $generalHtml
            );
            $this->assertStringNotContainsString($processLeaderName, $generalHtml);
            $this->assertStringContainsString($processLeaderName, $processHtml);
            $this->assertStringContainsString('Lider del proceso de', $processHtml);
            $this->assertStringContainsString(mb_strtoupper($processName, 'UTF-8'), $processHtml);
            $this->assertStringContainsString($dependencyLeaderName, $dependencyHtml);
            $this->assertStringContainsString('Lider de la dependencia', $dependencyHtml);
            $this->assertStringContainsString(mb_strtoupper($dependencyName, 'UTF-8'), $dependencyHtml);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_general_report_pdf_can_be_downloaded_for_a_quarter(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            [$serviceA] = $this->sampleServicesInDifferentProcesses();

            $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
            $programa = Programa::query()->firstOrFail();
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
            User::factory()->create([
                'nombre' => 'BRENDA BRITO ARREGOCES',
                'rol' => User::ROLE_LIDER_PROCESO,
                'id_proceso' => $serviceA->id_proceso,
                'id_dependencia' => null,
            ]);

            ReportingQuarter::query()->create([
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-10',
                'end_date' => '2026-03-31',
                'updated_by' => $admin->id,
            ]);

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [4, 4, 4, 4, 4, 4],
                '2026-01-10 08:00:00'
            );

            $response = $this->actingAs($admin)
                ->get(route('reports.general', [
                    'trimestre' => 1,
                    'export_pdf' => 1,
                ]));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_process_report_pdf_can_be_downloaded_with_assigned_leader(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        try {
            [$serviceA] = $this->sampleServicesInDifferentProcesses();

            $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
            $programa = Programa::query()->firstOrFail();
            $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

            User::factory()->create([
                'nombre' => 'BRENDA BRITO ARREGOCES',
                'rol' => User::ROLE_LIDER_PROCESO,
                'id_proceso' => $serviceA->id_proceso,
                'id_dependencia' => null,
            ]);

            User::factory()->create([
                'nombre' => 'CARLOS PEREZ IGUARAN',
                'rol' => User::ROLE_LIDER_DEPENDENCIA,
                'id_proceso' => $serviceA->id_proceso,
                'id_dependencia' => $serviceA->id_dependencia,
            ]);

            ReportingQuarter::query()->create([
                'year' => 2026,
                'quarter_number' => 1,
                'start_date' => '2026-01-10',
                'end_date' => '2026-03-31',
                'updated_by' => $admin->id,
            ]);

            $this->storeResponse(
                $estudiante->id_estamento,
                $programa->id_programa,
                $serviceA->id_proceso,
                $serviceA->id_dependencia,
                $serviceA->id_servicio,
                [4, 4, 4, 4, 4, 4],
                '2026-01-10 08:00:00'
            );

            $processResponse = $this->actingAs($admin)
                ->get(route('reports.process', [
                    'trimestre' => 1,
                    'id_proceso' => $serviceA->id_proceso,
                    'export_pdf' => 1,
                ]));

            $processResponse->assertOk();
            $processResponse->assertHeader('Content-Type', 'application/pdf');
        } finally {
            CarbonImmutable::setTestNow();
        }
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
