<?php

namespace Tests\Feature\Encuesta;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodigoQrEncuestaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_trying_to_access_the_qr_module(): void
    {
        $this->get(route('survey.qr'))
            ->assertRedirect(route('login'));
    }

    public function test_all_authenticated_roles_can_access_the_qr_module(): void
    {
        $roles = [
            User::ROLE_ADMIN,
            User::ROLE_ADMIN_2_0,
            User::ROLE_LIDER_PROCESO,
            User::ROLE_LIDER_DEPENDENCIA,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['rol' => $role]);

            $this->actingAs($user)
                ->get(route('survey.qr'))
                ->assertOk()
                ->assertSee('QR de encuesta')
                ->assertSee('https://medicion.desarrollougmaicao.com/encuesta')
                ->assertSee('Descargar QR')
                ->assertSee('Compartir por Correo')
                ->assertSee('Compartir por WhatsApp');
        }
    }
}
