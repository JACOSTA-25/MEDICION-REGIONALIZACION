<?php

namespace Tests\Feature;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Servicio;
use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyCatalogEndpointsTest extends TestCase
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
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['nombre' => 'Prestamos de Bibliografia']);
        $response->assertJsonMissing(['nombre' => 'PQRS']);
    }

    public function test_catalog_endpoints_return_422_for_invalid_ids(): void
    {
        $this->getJson(route('survey.catalogs.dependencias', ['id_proceso' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_proceso');

        $this->getJson(route('survey.catalogs.servicios', ['id_dependencia' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_dependencia');
    }
}
