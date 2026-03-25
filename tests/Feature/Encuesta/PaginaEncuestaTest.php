<?php

namespace Tests\Feature\Encuesta;

use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginaEncuestaTest extends TestCase
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

    public function test_guest_can_open_the_public_survey(): void
    {
        $response = $this->get(route('survey.create'));

        $response->assertOk();
        $response->assertSee('Encuesta de satisfaccion del servicio');
        $response->assertSee('Seleccione un estamento');
    }

    public function test_dashboard_remains_protected_by_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
