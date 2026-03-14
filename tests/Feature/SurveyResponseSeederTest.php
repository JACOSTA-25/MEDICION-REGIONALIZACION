<?php

namespace Tests\Feature;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Respuesta;
use Database\Seeders\EstamentoSeeder;
use Database\Seeders\EstructuraOrganizacionalSeeder;
use Database\Seeders\ProgramaSeeder;
use Database\Seeders\SurveyResponseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SurveyResponseSeederTest extends TestCase
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

    public function test_survey_response_seeder_generates_expected_volume_and_range(): void
    {
        $this->seed(SurveyResponseSeeder::class);

        $year = now()->year;
        $fechaInicio = Carbon::create($year, 2, 1, 0, 0, 0, config('app.timezone'));
        $fechaFin = Carbon::create($year, 5, 20, 23, 59, 59, config('app.timezone'));

        $this->assertDatabaseCount('respuesta', 300);

        $primeraRespuesta = Respuesta::query()->orderBy('fecha_respuesta')->firstOrFail();
        $ultimaRespuesta = Respuesta::query()->orderByDesc('fecha_respuesta')->firstOrFail();

        $this->assertTrue($primeraRespuesta->fecha_respuesta->betweenIncluded($fechaInicio, $fechaFin));
        $this->assertTrue($ultimaRespuesta->fecha_respuesta->betweenIncluded($fechaInicio, $fechaFin));

        $this->assertSame(
            Estamento::query()->count(),
            Respuesta::query()->distinct()->count('id_estamento')
        );

        $this->assertSame(
            Proceso::query()->where('activo', true)->count(),
            Respuesta::query()->distinct()->count('id_proceso')
        );

        $this->assertSame(
            Dependencia::query()->where('activo', true)->count(),
            Respuesta::query()->distinct()->count('id_dependencia')
        );
    }

    public function test_survey_response_seeder_respects_program_requirement_by_estamento(): void
    {
        $this->seed(SurveyResponseSeeder::class);

        $estamentosConPrograma = Estamento::query()
            ->whereIn('nombre', ['Docente', 'Egresado', 'Estudiante'])
            ->pluck('id_estamento');

        $this->assertSame(
            0,
            Respuesta::query()
                ->whereIn('id_estamento', $estamentosConPrograma)
                ->whereNull('id_programa')
                ->count()
        );

        $this->assertGreaterThan(
            0,
            Respuesta::query()
                ->whereNotIn('id_estamento', $estamentosConPrograma)
                ->whereNull('id_programa')
                ->count()
        );
    }
}
