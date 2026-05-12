<?php

namespace Tests\Feature;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Sede;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\SedeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalizacionGeneralReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_regionalizacion_admin_sede_can_view_university_wide_general_report(): void
    {
        $this->seed(SedeSeeder::class);

        $this->createSurveyRecordForSede(Sede::ID_REGIONALIZACION, 'Regionalizacion');
        $this->createSurveyRecordForSede(Sede::ID_MAICAO, 'Maicao');

        $user = User::factory()->create([
            'rol' => User::ROLE_ADMIN_SEDE,
            'id_sede' => Sede::ID_REGIONALIZACION,
        ]);

        $this->actingAs($user)
            ->get(route('reports.general', [
                'trimestre' => 1,
                'id_sede' => '',
            ]))
            ->assertOk()
            ->assertSee('Todas las sedes')
            ->assertViewHas('selectedSedeId', null)
            ->assertViewHas('report', fn (array $report): bool => (int) ($report['totals']['survey_count'] ?? 0) === 2);
    }

    public function test_university_wide_general_report_groups_regular_sedes_and_keeps_regionalizacion_processes(): void
    {
        $this->seed(SedeSeeder::class);

        $this->createSurveyRecordForSede(Sede::ID_MAICAO, 'Maicao');
        $this->createSurveyRecordForSede(Sede::ID_FONSECA, 'Fonseca');
        $this->createSurveyRecordForSede(Sede::ID_VILLANUEVA, 'Villanueva');
        $this->createSurveyRecordForSede(Sede::ID_REGIONALIZACION, 'Regionalizacion A', 'Proceso Regionalizacion A');
        $this->createSurveyRecordForSede(Sede::ID_REGIONALIZACION, 'Regionalizacion B', 'Proceso Regionalizacion B');

        $user = User::factory()->create([
            'rol' => User::ROLE_ADMIN_SEDE,
            'id_sede' => Sede::ID_REGIONALIZACION,
        ]);

        $this->actingAs($user)
            ->get(route('reports.general', [
                'trimestre' => 1,
                'id_sede' => '',
            ]))
            ->assertOk()
            ->assertViewHas('selectedSedeId', null)
            ->assertViewHas('report', function (array $report): bool {
                $rows = collect($report['tables']['scope_population']['rows'] ?? []);

                return ($report['tables']['scope_population']['first_column_title'] ?? null) === 'Sede / Proceso'
                    && ($report['tables']['scope_population']['second_column_title'] ?? null) === 'Total encuestados'
                    && (int) ($report['totals']['survey_count'] ?? 0) === 5
                    && (int) ($report['tables']['scope_population']['total_general'] ?? 0) === 5
                    && $rows->pluck('label')->all() === [
                        'Sede Maicao',
                        'Sede Fonseca',
                        'Sede Villanueva',
                        'Proceso Regionalizacion A',
                        'Proceso Regionalizacion B',
                    ]
                    && $rows->pluck('total')->all() === [1, 1, 1, 1, 1];
            });
    }

    public function test_regular_admin_sede_remains_limited_to_its_own_sede_in_general_report(): void
    {
        $this->seed(SedeSeeder::class);

        $this->createSurveyRecordForSede(Sede::ID_MAICAO, 'Maicao');
        $this->createSurveyRecordForSede(Sede::ID_FONSECA, 'Fonseca');

        $user = User::factory()->create([
            'rol' => User::ROLE_ADMIN_SEDE,
            'id_sede' => Sede::ID_MAICAO,
        ]);

        $this->actingAs($user)
            ->get(route('reports.general', [
                'trimestre' => 1,
                'id_sede' => '',
            ]))
            ->assertOk()
            ->assertDontSee('Todas las sedes')
            ->assertViewHas('selectedSedeId', Sede::ID_MAICAO)
            ->assertViewHas('report', fn (array $report): bool => (int) ($report['totals']['survey_count'] ?? 0) === 1);
    }

    private function createSurveyRecordForSede(int $sedeId, string $label, ?string $processName = null): void
    {
        $estamento = Estamento::query()->firstOrCreate([
            'nombre' => 'Estudiante',
        ]);

        $programa = Programa::query()->create([
            'id_sede' => $sedeId,
            'nombre' => 'Programa '.$label,
        ]);

        $proceso = Proceso::query()->create([
            'id_sede' => $sedeId,
            'nombre' => $processName ?? ('Proceso '.$label),
            'activo' => true,
        ]);

        $dependencia = Dependencia::query()->create([
            'id_sede' => $sedeId,
            'id_proceso' => $proceso->id_proceso,
            'nombre' => 'Dependencia '.$label,
            'activo' => true,
        ]);

        $servicio = Servicio::query()->create([
            'id_sede' => $sedeId,
            'id_dependencia' => $dependencia->id_dependencia,
            'nombre' => 'Servicio '.$label,
            'activo' => true,
        ]);

        Respuesta::query()->create([
            'id_sede' => $sedeId,
            'id_estamento' => $estamento->id_estamento,
            'id_programa' => $programa->id_programa,
            'id_proceso' => $proceso->id_proceso,
            'id_dependencia' => $dependencia->id_dependencia,
            'id_servicio' => $servicio->id_servicio,
            'pregunta1' => 5,
            'pregunta2' => 5,
            'pregunta3' => 4,
            'pregunta4' => 4,
            'pregunta5' => 5,
            'observaciones' => 'Observacion '.$label,
            'fecha_respuesta' => now()->startOfYear()->addDays(10),
        ]);
    }
}
