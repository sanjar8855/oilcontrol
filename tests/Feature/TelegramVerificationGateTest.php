<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class TelegramVerificationGateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_an_unverified_user_can_still_reach_the_dashboard(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_logout_stays_reachable_while_unverified(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    }

    public function test_a_verified_user_is_never_redirected_to_the_telegram_verify_page(): void
    {
        [$user] = $this->createDirectorWithWorkshop();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
