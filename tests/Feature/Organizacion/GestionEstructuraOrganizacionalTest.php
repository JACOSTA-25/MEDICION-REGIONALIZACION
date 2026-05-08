<?php

namespace Tests\Feature\Organizacion;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\EstamentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GestionEstructuraOrganizacionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'null']);
    }

    public function test_admin_admin_2_0_and_admin_sede_can_access_the_module(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $admin20 = User::factory()->create(['rol' => User::ROLE_ADMIN_2_0]);
        $adminSede = User::factory()->create(['rol' => User::ROLE_ADMIN_SEDE, 'id_sede' => Sede::ID_MAICAO]);
        $leaderProcess = User::factory()->create(['rol' => User::ROLE_LIDER_PROCESO]);
        $leaderDependency = User::factory()->create(['rol' => User::ROLE_LIDER_DEPENDENCIA]);

        $this->actingAs($admin)
            ->get(route('process-dependency.index'))
            ->assertOk();

        $this->actingAs($admin20)
            ->get(route('process-dependency.index'))
            ->assertOk();

        $this->actingAs($adminSede)
            ->get(route('process-dependency.index'))
            ->assertOk();

        $process = Proceso::query()->create([
            'nombre' => 'Proceso de Dependencias Test',
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('process-dependency.processes.dependencies', $process))
            ->assertOk();

        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Dependencia Servicios Test',
            'activo' => true,
        ]);

        $this->actingAs($admin20)
            ->get(route('process-dependency.dependencies.services', $dependency))
            ->assertOk();

        $this->actingAs($leaderProcess)
            ->getJson(route('process-dependency.index'))
            ->assertForbidden();

        $this->actingAs($leaderDependency)
            ->getJson(route('process-dependency.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_process_and_register_audit(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('process-dependency.processes.store'), [
                'id_sede' => Sede::ID_MAICAO,
                'nombre' => 'Gestion de Archivo',
                'activo' => '1',
            ])
            ->assertRedirect(route('process-dependency.index'));

        $process = Proceso::query()->where('nombre', 'Gestion de Archivo')->firstOrFail();

        $this->assertDatabaseHas('proceso', [
            'id_proceso' => $process->id_proceso,
            'id_sede' => Sede::ID_MAICAO,
            'nombre' => 'Gestion de Archivo',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('catalog_audit', [
            'action' => 'CREATE',
            'entity_type' => 'proceso',
            'entity_id' => $process->id_proceso,
            'user_id' => $admin->id,
        ]);
    }

    public function test_deactivating_a_process_also_deactivates_its_dependencies(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Gestion de Movilidad',
            'activo' => true,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Coordinacion de convenios',
            'activo' => true,
        ]);
        $service = Servicio::query()->create([
            'id_dependencia' => $dependency->id_dependencia,
            'nombre' => 'Servicio de Convenios',
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('process-dependency.processes.deactivate', $process))
            ->assertRedirect(route('process-dependency.index'));

        $this->assertDatabaseHas('proceso', [
            'id_proceso' => $process->id_proceso,
            'activo' => false,
        ]);

        $this->assertDatabaseHas('dependencia', [
            'id_dependencia' => $dependency->id_dependencia,
            'activo' => false,
        ]);

        $this->assertDatabaseHas('servicio', [
            'id_servicio' => $service->id_servicio,
            'activo' => false,
        ]);

        $this->assertDatabaseHas('catalog_audit', [
            'action' => 'DEACTIVATE',
            'entity_type' => 'proceso',
            'entity_id' => $process->id_proceso,
            'user_id' => $admin->id,
        ]);
    }

    public function test_dependency_cannot_be_created_for_inactive_process(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Gestion de Calidad Institucional',
            'activo' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('process-dependency.processes.dependencies', $process))
            ->post(route('process-dependency.dependencies.store'), [
                'id_proceso' => $process->id_proceso,
                'nombre' => 'Mesa de apoyo',
                'activo' => '1',
                'redirect_proceso' => $process->id_proceso,
            ])
            ->assertRedirect(route('process-dependency.processes.dependencies', $process))
            ->assertSessionHasErrorsIn('createDependency', ['id_proceso']);

        $this->assertDatabaseMissing('dependencia', [
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Mesa de apoyo',
        ]);
    }

    public function test_dependency_activation_is_blocked_when_parent_process_is_inactive(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Gestion Academica',
            'activo' => false,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Analitica Institucional',
            'activo' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('process-dependency.dependencies.activate', $dependency), [
                'redirect_proceso' => $process->id_proceso,
            ])
            ->assertRedirect(route('process-dependency.processes.dependencies', $process))
            ->assertSessionHas('catalog_error');

        $this->assertDatabaseHas('dependencia', [
            'id_dependencia' => $dependency->id_dependencia,
            'activo' => false,
        ]);
    }

    public function test_dependencies_view_shows_only_selected_process_dependencies_and_hides_process_column(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $selectedProcess = Proceso::query()->create([
            'nombre' => 'Gestion de Proyeccion',
            'activo' => true,
        ]);
        $otherProcess = Proceso::query()->create([
            'nombre' => 'Gestion Financiera',
            'activo' => true,
        ]);

        Dependencia::query()->create([
            'id_proceso' => $selectedProcess->id_proceso,
            'nombre' => 'Relacion con egresados',
            'activo' => true,
        ]);

        Dependencia::query()->create([
            'id_proceso' => $otherProcess->id_proceso,
            'nombre' => 'Tesoreria',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('process-dependency.processes.dependencies', $selectedProcess));

        $response->assertOk();
        $response->assertSee('Dependencias de '.$selectedProcess->nombre);
        $response->assertSee('Relacion con egresados');
        $response->assertDontSee('Tesoreria');
        $response->assertDontSee('<th>Proceso</th>', false);
    }

    public function test_admin_can_create_service_and_register_audit(): void
    {
        $this->seed(EstamentoSeeder::class);

        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Gestion de Servicios',
            'activo' => true,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Dependencia Base',
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('process-dependency.services.store'), [
                'id_dependencia' => $dependency->id_dependencia,
                'nombre' => 'Atencion Integral',
                'activo' => '1',
                'redirect_proceso' => $process->id_proceso,
                'redirect_dependencia' => $dependency->id_dependencia,
            ])
            ->assertRedirect(route('process-dependency.dependencies.services', $dependency));

        $service = Servicio::query()->where('nombre', 'Atencion Integral')->firstOrFail();

        $this->assertDatabaseHas('servicio', [
            'id_servicio' => $service->id_servicio,
            'id_dependencia' => $dependency->id_dependencia,
            'activo' => true,
        ]);

        foreach (Estamento::query()->pluck('id_estamento') as $estamentoId) {
            $this->assertDatabaseHas('servicio_estamento', [
                'id_servicio' => $service->id_servicio,
                'id_estamento' => $estamentoId,
            ]);
        }

        $this->assertDatabaseHas('catalog_audit', [
            'action' => 'CREATE',
            'entity_type' => 'servicio',
            'entity_id' => $service->id_servicio,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_update_authorized_estamentos_for_a_service(): void
    {
        $this->seed(EstamentoSeeder::class);

        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Proceso de Permanencia',
            'activo' => true,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Permanencia Estudiantil',
            'activo' => true,
        ]);
        $service = Servicio::query()->create([
            'id_dependencia' => $dependency->id_dependencia,
            'nombre' => 'Tutorias Focalizadas',
            'activo' => true,
        ]);
        $estudiante = Estamento::query()->where('nombre', 'Estudiante')->firstOrFail();
        $docente = Estamento::query()->where('nombre', 'Docente')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('process-dependency.services.update', $service), [
                'id_dependencia' => $dependency->id_dependencia,
                'nombre' => 'Tutorias Focalizadas',
                'activo' => '1',
                'sync_estamentos' => '1',
                'id_estamentos' => [$estudiante->id_estamento],
                'redirect_proceso' => $process->id_proceso,
                'redirect_dependencia' => $dependency->id_dependencia,
            ])
            ->assertRedirect(route('process-dependency.dependencies.services', $dependency));

        $this->assertDatabaseHas('servicio_estamento', [
            'id_servicio' => $service->id_servicio,
            'id_estamento' => $estudiante->id_estamento,
        ]);

        $this->assertDatabaseMissing('servicio_estamento', [
            'id_servicio' => $service->id_servicio,
            'id_estamento' => $docente->id_estamento,
        ]);
    }

    public function test_service_cannot_be_created_for_inactive_dependency(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Proceso Inactivo Dependencia',
            'activo' => true,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Dependencia Inactiva',
            'activo' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('process-dependency.dependencies.services', $dependency))
            ->post(route('process-dependency.services.store'), [
                'id_dependencia' => $dependency->id_dependencia,
                'nombre' => 'Servicio Bloqueado',
                'activo' => '1',
                'redirect_proceso' => $process->id_proceso,
                'redirect_dependencia' => $dependency->id_dependencia,
            ])
            ->assertRedirect(route('process-dependency.dependencies.services', $dependency))
            ->assertSessionHasErrorsIn('createService', ['id_dependencia', 'activo']);

        $this->assertDatabaseMissing('servicio', [
            'id_dependencia' => $dependency->id_dependencia,
            'nombre' => 'Servicio Bloqueado',
        ]);
    }

    public function test_services_view_shows_only_selected_dependency_services(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Proceso Servicios Vista',
            'activo' => true,
        ]);
        $selectedDependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Dependencia Seleccionada',
            'activo' => true,
        ]);
        $otherDependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Otra Dependencia',
            'activo' => true,
        ]);

        Servicio::query()->create([
            'id_dependencia' => $selectedDependency->id_dependencia,
            'nombre' => 'Servicio Visible',
            'activo' => true,
        ]);

        Servicio::query()->create([
            'id_dependencia' => $otherDependency->id_dependencia,
            'nombre' => 'Servicio Oculto',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('process-dependency.dependencies.services', $selectedDependency));

        $response->assertOk();
        $response->assertSee('Servicios de '.$selectedDependency->nombre);
        $response->assertSee('Servicio Visible');
        $response->assertDontSee('Servicio Oculto');
    }

    public function test_services_view_still_loads_when_servicio_estamento_table_is_missing(): void
    {
        $this->seed(EstamentoSeeder::class);

        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $process = Proceso::query()->create([
            'nombre' => 'Proceso Beta Hosting',
            'activo' => true,
        ]);
        $dependency = Dependencia::query()->create([
            'id_proceso' => $process->id_proceso,
            'nombre' => 'Dependencia Beta Hosting',
            'activo' => true,
        ]);

        Servicio::query()->create([
            'id_dependencia' => $dependency->id_dependencia,
            'nombre' => 'Servicio Beta',
            'activo' => true,
        ]);

        Schema::dropIfExists('servicio_estamento');

        $response = $this->actingAs($admin)
            ->get(route('process-dependency.dependencies.services', $dependency));

        $response->assertOk();
        $response->assertSee('Servicio Beta');
        $response->assertSee('Configuracion no disponible en esta base de datos');
        $response->assertSee('La configuracion de estamentos no esta disponible en esta base de datos. Ejecuta la migracion pendiente para habilitarla.');
    }
}
