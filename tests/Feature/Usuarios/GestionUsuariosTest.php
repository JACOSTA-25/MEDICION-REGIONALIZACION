<?php

namespace Tests\Feature\Usuarios;

use App\Models\Dependencia;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\User;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'null']);
    }

    public function test_admin_can_delete_a_user_from_the_management_module(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $managedUser = User::factory()->create(['rol' => User::ROLE_LIDER_PROCESO]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('user_status', 'Usuario eliminado correctamente.');

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }

    public function test_admin_cannot_delete_his_own_account_from_the_management_module(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('user_error', 'No puedes eliminar tu propio usuario.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_create_form_only_lists_dependencies_for_the_selected_process(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $processA = Proceso::query()->create([
            'nombre' => 'Proceso A',
            'activo' => true,
        ]);
        $processB = Proceso::query()->create([
            'nombre' => 'Proceso B',
            'activo' => true,
        ]);

        Dependencia::query()->create([
            'id_proceso' => $processA->id_proceso,
            'nombre' => 'Dependencia Proceso A',
            'activo' => true,
        ]);
        Dependencia::query()->create([
            'id_proceso' => $processB->id_proceso,
            'nombre' => 'Dependencia Proceso B',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession([
                '_old_input' => [
                    'rol' => User::ROLE_LIDER_DEPENDENCIA,
                    'id_proceso' => $processA->id_proceso,
                ],
                'open_create_user' => true,
            ])
            ->get(route('users.index'));

        $response->assertOk();

        preg_match(
            '/<select[^>]*id="create_dependencia"[^>]*>(.*?)<\/select>/s',
            $response->getContent(),
            $matches,
        );

        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('Dependencia Proceso A', $matches[1]);
        $this->assertStringNotContainsString('Dependencia Proceso B', $matches[1]);
    }

    public function test_users_index_uses_the_global_sede_scope_stored_in_session(): void
    {
        $admin = User::factory()->create(['rol' => User::ROLE_ADMIN]);
        $maicaoUser = User::factory()->create([
            'nombre' => 'Usuario Maicao',
            'id_sede' => Sede::ID_MAICAO,
        ]);
        $fonsecaUser = User::factory()->create([
            'nombre' => 'Usuario Fonseca',
            'id_sede' => Sede::ID_FONSECA,
        ]);

        $response = $this->actingAs($admin)
            ->withSession([ServicioSedes::SESSION_SCOPE_KEY => Sede::ID_FONSECA])
            ->get(route('users.index'));

        $response->assertOk();
        $response->assertSee($fonsecaUser->nombre);
        $response->assertDontSee($maicaoUser->nombre);
    }
}
