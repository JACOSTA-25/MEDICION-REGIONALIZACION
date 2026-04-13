<?php

namespace Tests\Feature\Perfil;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizacionPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Informacion del perfil')
            ->assertSee('Estos datos son informativos y son administrados por el Super Administrador.')
            ->assertSee('Si necesitas actualizar esta informacion, solicita el ajuste al Super Administrador.')
            ->assertDontSee('Delete Account');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('userDeletion', ['password']);

        $this->assertNotNull($user->fresh());
    }

    public function test_dashboard_contains_a_direct_link_to_change_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cambiar contrasena')
            ->assertSee(route('profile.edit').'#cambiar-contrasena');
    }
}
