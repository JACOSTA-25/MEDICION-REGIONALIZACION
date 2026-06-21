<?php

namespace Tests\Feature\Perfil;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActualizacionContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit').'#cambiar-contrasena')
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password_hash));
        $this->assertAuthenticatedAs($user->refresh());
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('updatePassword', ['current_password']);
    }

    public function test_new_password_must_have_at_least_eight_characters(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'corta7',
                'password_confirmation' => 'corta7',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('updatePassword', ['password']);
    }

    public function test_password_confirmation_error_is_shown_in_spanish(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'otra-clave-distinta',
            ]);

        $response->assertRedirect(route('profile.edit'));

        $errors = session('errors');

        $this->assertNotNull($errors);
        $this->assertSame(
            'La confirmacion de la nueva contrasena no coincide.',
            $errors->getBag('updatePassword')->first('password')
        );
    }

    public function test_profile_page_displays_success_dialog_before_logout_after_password_update(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'password-updated'])
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Contrasena actualizada correctamente')
            ->assertSee('Tu nueva contrasena fue registrada con exito. Para proteger tu cuenta, al cerrar este mensaje se cerrara tu sesion y deberas ingresar nuevamente.')
            ->assertSee('Aceptar')
            ->assertSee('password_updated_logout');
    }

    public function test_password_updated_logout_closes_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'), [
            'password_updated_logout' => '1',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
