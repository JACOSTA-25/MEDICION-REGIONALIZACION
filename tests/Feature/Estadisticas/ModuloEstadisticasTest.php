<?php

namespace Tests\Feature\Estadisticas;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Respuesta;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuloEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'null']);
    }

    public function test_statistics_routes_respect_role_access_rules(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $admin20 = User::factory()->create(['rol' => User::ROLE_ADMIN_2_0]);
        $leaderProcess = User::factory()->create([
            'rol' => User::ROLE_LIDER_PROCESO,
            'id_proceso' => 10,
        ]);
        $leaderDependency = User::factory()->create([
            'rol' => User::ROLE_LIDER_DEPENDENCIA,
            'id_proceso' => 11,
            'id_dependencia' => 21,
        ]);

        $this->actingAs($admin)
            ->get(route('statistics.index'))
            ->assertOk();

        $this->actingAs($admin20)
            ->get(route('statistics.services'))
            ->assertOk();

        $this->actingAs($leaderProcess)
            ->get(route('statistics.processes'))
            ->assertOk();

        $this->actingAs($leaderProcess)
            ->get(route('statistics.dependencies'))
            ->assertOk();

        $this->actingAs($leaderProcess)
            ->getJson(route('statistics.services'))
            ->assertForbidden();

        $this->actingAs($leaderDependency)
            ->get(route('statistics.services'))
            ->assertOk();

        $this->actingAs($leaderDependency)
            ->getJson(route('statistics.processes'))
            ->assertForbidden();
    }

    public function test_process_statistics_payload_returns_expected_comparison_data(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $estamento = Estamento::query()->firstOrCreate(['nombre' => 'Estudiante']);
        $programa = Programa::query()->firstOrCreate(['nombre' => 'Ingenieria']);
        $processA = Proceso::query()->create(['nombre' => 'Bienestar', 'activo' => true]);
        $processB = Proceso::query()->create(['nombre' => 'Registro', 'activo' => true]);

        Respuesta::query()->create($this->responsePayload($estamento, $programa, $processA, null, null, [5, 5, 5, 5, 5, 5]));
        Respuesta::query()->create($this->responsePayload($estamento, $programa, $processA, null, null, [4, 4, 4, 4, 4, 4]));
        Respuesta::query()->create($this->responsePayload($estamento, $programa, $processB, null, null, [1, 2, 3, 2, 1, 2]));

        $response = $this->actingAs($admin)
            ->getJson(route('statistics.data.show', ['level' => 'processes']));

        $response->assertOk();
        $response->assertJsonPath('counters.surveys', 3);
        $response->assertJsonPath('counters.entities', 2);
        $response->assertJsonPath('table.0.name', 'Bienestar');
        $response->assertJsonPath('table.0.surveys', 2);
        $response->assertJsonCount(2, 'charts.quantityComparison');
    }

    public function test_dependency_leader_only_receives_statistics_for_its_services(): void
    {
        $estamento = Estamento::query()->firstOrCreate(['nombre' => 'Docente']);
        $programa = Programa::query()->firstOrCreate(['nombre' => 'Derecho']);
        $process = Proceso::query()->create(['nombre' => 'Academico', 'activo' => true]);
        $dependencyA = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Biblioteca',
            'activo' => true,
        ]);
        $dependencyB = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Archivo',
            'activo' => true,
        ]);
        $serviceA = Servicio::query()->create([
            'id_dependencia' => $dependencyA->id_dependencia,
            'nombre' => 'Prestamo externo',
            'activo' => true,
        ]);
        $serviceB = Servicio::query()->create([
            'id_dependencia' => $dependencyB->id_dependencia,
            'nombre' => 'Consulta historica',
            'activo' => true,
        ]);
        $leaderDependency = User::factory()->create([
            'rol' => User::ROLE_LIDER_DEPENDENCIA,
            'id_proceso' => $process->id_proceso,
            'id_dependencia' => $dependencyA->id_dependencia,
        ]);

        Respuesta::query()->create($this->responsePayload($estamento, $programa, $process, $dependencyA, $serviceA, [5, 5, 5, 4, 4, 4]));
        Respuesta::query()->create($this->responsePayload($estamento, $programa, $process, $dependencyB, $serviceB, [1, 1, 1, 1, 1, 1]));

        $response = $this->actingAs($leaderDependency)
            ->getJson(route('statistics.data.show', ['level' => 'services']));

        $response->assertOk();
        $response->assertJsonPath('filters.selected.id_proceso', $process->id_proceso);
        $response->assertJsonPath('filters.selected.id_dependencia', $dependencyA->id_dependencia);
        $response->assertJsonCount(1, 'table');
        $response->assertJsonPath('table.0.name', 'Prestamo externo');
    }

    /**
     * @param  array<int, int>  $answers
     * @return array<string, mixed>
     */
    private function responsePayload(
        Estamento $estamento,
        Programa $programa,
        Proceso $process,
        ?Dependencia $dependency,
        ?Servicio $service,
        array $answers
    ): array {
        return [
            'id_estamento' => $estamento->id_estamento,
            'id_programa' => $programa->id_programa,
            'id_proceso' => $process->id_proceso,
            'id_dependencia' => $dependency?->id_dependencia,
            'id_servicio' => $service?->id_servicio,
            'pregunta1' => $answers[0],
            'pregunta2' => $answers[1],
            'pregunta3' => $answers[2],
            'pregunta4' => $answers[3],
            'pregunta5' => $answers[4],
            'pregunta6' => $answers[5],
            'fecha_respuesta' => now(),
        ];
    }
}
