<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\SessionSecurity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');

        $this->get('/login')
            ->assertSee('Usuario o contrasena invalida.');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_authenticated_session_is_closed_after_ten_minutes_of_inactivity(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession([
                SessionSecurity::LAST_ACTIVITY_KEY => now()->subMinutes(11)->getTimestamp(),
            ])
            ->get(route('profile.edit'));

        $this->assertGuest();
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Tu sesion se cerro tras 10 minutos de inactividad. Ingresa nuevamente.');
    }

    public function test_authenticated_pages_are_served_with_no_store_cache_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }
}
