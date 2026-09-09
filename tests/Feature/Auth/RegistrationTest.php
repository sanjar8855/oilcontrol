<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '+998901112233',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_a_new_workshop_starts_the_onboarding_cycle(): void
    {
        $this->post('/register', [
            'name' => 'Onboarding Test',
            'phone' => '+998901112244',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $workshop = \App\Models\Workshop::where('owner_name', 'Onboarding Test')->firstOrFail();

        $this->assertSame('products', $workshop->onboarding_step);
        $this->assertTrue($workshop->isOnboarding());
    }
}
