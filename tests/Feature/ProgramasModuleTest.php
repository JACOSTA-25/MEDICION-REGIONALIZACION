<?php

namespace Tests\Feature;

use App\Models\Programa;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramasModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sede_can_create_program_for_its_own_sede(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_ADMIN_SEDE,
            'id_sede' => Sede::ID_FONSECA,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('programs.store'), [
                'id_sede' => Sede::ID_FONSECA,
                'nombre' => 'Ingenieria Ambiental',
            ]);

        $response
            ->assertRedirect(route('programs.index'))
            ->assertSessionHas('catalog_status');

        $this->assertDatabaseHas('programa', [
            'id_sede' => Sede::ID_FONSECA,
            'nombre' => 'Ingenieria Ambiental',
        ]);
    }

    public function test_admin_2_0_can_view_programs_but_cannot_create_them(): void
    {
        Programa::query()->create([
            'id_sede' => Sede::ID_MAICAO,
            'nombre' => 'Derecho',
        ]);

        $user = User::factory()->create([
            'rol' => User::ROLE_ADMIN_2_0,
            'id_sede' => null,
        ]);

        $this->actingAs($user)
            ->get(route('programs.index'))
            ->assertOk()
            ->assertSee('Derecho');

        $this->actingAs($user)
            ->post(route('programs.store'), [
                'id_sede' => Sede::ID_MAICAO,
                'nombre' => 'Programa Bloqueado Admin 20',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('programa', [
            'id_sede' => Sede::ID_MAICAO,
            'nombre' => 'Programa Bloqueado Admin 20',
        ]);
    }
}
