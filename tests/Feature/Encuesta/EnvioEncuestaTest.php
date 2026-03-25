<?php

namespace Tests\Feature\Encuesta;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Programa;
use App\Models\Servicio;
use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvioEncuestaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EstamentoSeeder::class,
            ProgramaSeeder::class,
            EstructuraOrganizacionalSeeder::class,
        ]);
    }

    public function test_guest_can_submit_a_valid_public_survey_response(): void
    {
        $payload = $this->validPayloadForEstamento('Estudiante');

        $response = $this->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $this->assertDatabaseHas('respuesta', [
            'id_estamento' => $payload['id_estamento'],
            'id_programa' => $payload['id_programa'],
            'id_proceso' => $payload['id_proceso'],
            'id_dependencia' => $payload['id_dependencia'],
            'id_servicio' => $payload['id_servicio'],
            'pregunta1' => 5,
            'pregunta6' => 4,
        ]);
    }

    public function test_program_is_required_for_estamentos_that_need_it(): void
    {
        $payload = $this->validPayloadForEstamento('Docente');
        $payload['id_programa'] = null;

        $response = $this->from(route('survey.create'))->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $response->assertSessionHasErrors('id_programa');
        $this->assertDatabaseCount('respuesta', 0);
    }

    public function test_dependencia_must_belong_to_the_selected_process(): void
    {
        $payload = $this->validPayloadForEstamento('Estudiante');

        $otroProceso = Proceso::query()->create(['nombre' => 'Proceso temporal de prueba']);
        $dependenciaAjena = Dependencia::query()->create([
            'id_proceso' => $otroProceso->id_proceso,
            'nombre' => 'Archivo Central',
        ]);

        $payload['id_dependencia'] = $dependenciaAjena->id_dependencia;

        $response = $this->from(route('survey.create'))->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $response->assertSessionHasErrors('id_dependencia');
        $this->assertDatabaseCount('respuesta', 0);
    }

    public function test_servicio_must_belong_to_the_selected_dependencia(): void
    {
        $payload = $this->validPayloadForEstamento('Estudiante');

        $otraDependencia = Dependencia::query()->create([
            'id_proceso' => $payload['id_proceso'],
            'nombre' => 'Bienestar Universitario',
        ]);
        $servicioAjeno = Servicio::query()->create([
            'id_dependencia' => $otraDependencia->id_dependencia,
            'nombre' => 'Atencion Psicosocial',
        ]);

        $payload['id_servicio'] = $servicioAjeno->id_servicio;

        $response = $this->from(route('survey.create'))->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $response->assertSessionHasErrors('id_servicio');
        $this->assertDatabaseCount('respuesta', 0);
    }

    public function test_servicio_must_be_allowed_for_the_selected_estamento(): void
    {
        $payload = $this->validPayloadForEstamento('Estudiante');
        $servicio = Servicio::query()->findOrFail($payload['id_servicio']);
        $docente = Estamento::query()->where('nombre', 'Docente')->firstOrFail();

        $servicio->estamentos()->sync([$docente->id_estamento]);

        $response = $this->from(route('survey.create'))->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $response->assertSessionHasErrors('id_servicio');
        $this->assertDatabaseCount('respuesta', 0);
    }

    public function test_questions_must_remain_within_the_defined_range(): void
    {
        $payload = $this->validPayloadForEstamento('Estudiante');
        $payload['pregunta4'] = 6;

        $response = $this->from(route('survey.create'))->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $response->assertSessionHasErrors('pregunta4');
        $this->assertDatabaseCount('respuesta', 0);
    }

    public function test_program_is_forced_to_null_when_estamento_does_not_require_it(): void
    {
        $payload = $this->validPayloadForEstamento('Administrativo');
        $payload['id_programa'] = Programa::query()->firstOrFail()->id_programa;

        $response = $this->post(route('survey.store'), $payload);

        $response->assertRedirect(route('survey.create'));
        $this->assertDatabaseHas('respuesta', [
            'id_estamento' => $payload['id_estamento'],
            'id_programa' => null,
            'id_proceso' => $payload['id_proceso'],
        ]);
    }

    private function validPayloadForEstamento(string $nombreEstamento): array
    {
        $estamento = Estamento::query()->where('nombre', $nombreEstamento)->firstOrFail();
        $programa = Programa::query()->firstOrFail();
        $servicio = Servicio::query()->where('nombre', 'Prestamos de Bibliografia')->firstOrFail();
        $dependencia = Dependencia::query()->findOrFail($servicio->id_dependencia);
        $proceso = Proceso::query()->findOrFail($dependencia->id_proceso);

        return [
            'id_dependencia' => $dependencia->id_dependencia,
            'id_estamento' => $estamento->id_estamento,
            'id_programa' => in_array($nombreEstamento, ['Docente', 'Egresado', 'Estudiante'], true)
                ? $programa->id_programa
                : null,
            'id_proceso' => $proceso->id_proceso,
            'id_servicio' => $servicio->id_servicio,
            'observaciones' => 'Respuesta de prueba',
            'pregunta1' => 5,
            'pregunta2' => 5,
            'pregunta3' => 4,
            'pregunta4' => 5,
            'pregunta5' => 4,
            'pregunta6' => 4,
        ];
    }
}
