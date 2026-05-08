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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalizacionGeneralReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_regionalizacion_admin_sede_can_view_university_wide_general_report(): void
    {
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

    public function test_regular_admin_sede_remains_limited_to_its_own_sede_in_general_report(): void
    {
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

    private function createSurveyRecordForSede(int $sedeId, string $label): void
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
            'nombre' => 'Proceso '.$label,
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
