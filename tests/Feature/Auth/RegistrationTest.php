<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_available_by_default(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_guests_can_not_register_when_public_registration_is_disabled(): void
    {
        $response = $this->post('/register', [
            'nombre' => 'Test User',
            'username' => 'test.user',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }
}
