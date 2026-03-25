<?php

namespace Tests\Feature\Encuesta;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Servicio;
use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogosEncuestaTest extends TestCase
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

    public function test_dependencias_endpoint_returns_only_records_for_the_selected_process(): void
    {
        $proceso = Proceso::query()
            ->where('nombre', 'Gestion De Bienes, Servicios Academicos Y Bibliotecarios')
            ->firstOrFail();
        $otroProceso = Proceso::query()->create(['nombre' => 'Gestion Academica']);

        Dependencia::query()->create([
            'id_proceso' => $otroProceso->id_proceso,
            'nombre' => 'Registro y Control',
        ]);

        $response = $this->getJson(route('survey.catalogs.dependencias', [
            'id_proceso' => $proceso->id_proceso,
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['nombre' => 'Biblioteca']);
        $response->assertJsonMissing(['nombre' => 'Registro y Control']);
    }

    public function test_servicios_endpoint_returns_only_records_for_the_selected_dependency(): void
    {
        $dependencia = Dependencia::query()->where('nombre', 'Biblioteca')->firstOrFail();
        $estamento = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
        $otraDependencia = Dependencia::query()->create([
            'id_proceso' => $dependencia->id_proceso,
            'nombre' => 'Atencion al Ciudadano',
        ]);

        Servicio::query()->create([
            'id_dependencia' => $otraDependencia->id_dependencia,
            'nombre' => 'PQRS',
        ]);

        $response = $this->getJson(route('survey.catalogs.servicios', [
            'id_dependencia' => $dependencia->id_dependencia,
            'id_estamento' => $estamento->id_estamento,
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['nombre' => 'Prestamos de Bibliografia']);
        $response->assertJsonMissing(['nombre' => 'PQRS']);
    }

    public function test_catalog_endpoints_return_422_for_invalid_ids(): void
    {
        $estamento = Estamento::query()->firstOrFail();

        $this->getJson(route('survey.catalogs.dependencias', [
            'id_proceso' => 999999,
            'id_estamento' => $estamento->id_estamento,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_proceso');

        $this->getJson(route('survey.catalogs.servicios', [
            'id_dependencia' => 999999,
            'id_estamento' => $estamento->id_estamento,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_dependencia');
    }

    public function test_dependencias_endpoint_returns_only_dependencies_with_services_for_the_selected_estamento(): void
    {
        $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
        $docente = Estamento::query()->where('nombre', 'Docente')->firstOrFail();
        $proceso = Proceso::query()->create(['nombre' => 'Proceso de Bienestar']);
        $dependenciaVisible = Dependencia::query()->create([
            'id_proceso' => $proceso->id_proceso,
            'nombre' => 'Apoyo Estudiantil',
            'activo' => true,
        ]);
        $dependenciaOculta = Dependencia::query()->create([
            'id_proceso' => $proceso->id_proceso,
            'nombre' => 'Desarrollo Docente',
            'activo' => true,
        ]);

        $servicioVisible = Servicio::query()->create([
            'id_dependencia' => $dependenciaVisible->id_dependencia,
            'nombre' => 'Tutorias',
            'activo' => true,
        ]);
        $servicioOculto = Servicio::query()->create([
            'id_dependencia' => $dependenciaOculta->id_dependencia,
            'nombre' => 'Acompanamiento Docente',
            'activo' => true,
        ]);

        $servicioVisible->estamentos()->sync([$estudiante->id_estamento]);
        $servicioOculto->estamentos()->sync([$docente->id_estamento]);

        $response = $this->getJson(route('survey.catalogs.dependencias', [
            'id_proceso' => $proceso->id_proceso,
            'id_estamento' => $estudiante->id_estamento,
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['nombre' => 'Apoyo Estudiantil']);
        $response->assertJsonMissing(['nombre' => 'Desarrollo Docente']);
    }

    public function test_servicios_endpoint_returns_only_services_allowed_for_the_selected_estamento(): void
    {
        $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
        $docente = Estamento::query()->where('nombre', 'Docente')->firstOrFail();
        $dependencia = Dependencia::query()->create([
            'id_proceso' => Proceso::query()->create(['nombre' => 'Proceso de Orientacion'])->id_proceso,
            'nombre' => 'Centro de Acompanamiento',
            'activo' => true,
        ]);

        $tutoria = Servicio::query()->create([
            'id_dependencia' => $dependencia->id_dependencia,
            'nombre' => 'Tutorias',
            'activo' => true,
        ]);
        $asesoria = Servicio::query()->create([
            'id_dependencia' => $dependencia->id_dependencia,
            'nombre' => 'Asesoria Docente',
            'activo' => true,
        ]);

        $tutoria->estamentos()->sync([$estudiante->id_estamento]);
        $asesoria->estamentos()->sync([$docente->id_estamento]);

        $response = $this->getJson(route('survey.catalogs.servicios', [
            'id_dependencia' => $dependencia->id_dependencia,
            'id_estamento' => $estudiante->id_estamento,
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['nombre' => 'Tutorias']);
        $response->assertJsonMissing(['nombre' => 'Asesoria Docente']);
    }
}
