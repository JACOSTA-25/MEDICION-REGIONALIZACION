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

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'nombre' => 'Test User',
                'username' => 'test.user',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertEquals('Test User', $user->nombre);
        $this->assertEquals('test.user', $user->username);
    }

    public function test_profile_information_accepts_the_existing_username(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'nombre' => 'Test User',
                'username' => $user->username,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame($user->username, $user->refresh()->username);
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
}
