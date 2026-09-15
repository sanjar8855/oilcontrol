<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramVerificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_factory_users_are_telegram_verified_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->telegram_verified_at);
    }

    public function test_the_unverified_telegram_factory_state_clears_the_timestamp(): void
    {
        $user = User::factory()->unverifiedTelegram()->create();

        $this->assertNull($user->telegram_verified_at);
    }

    public function test_a_user_has_one_telegram_verification_record(): void
    {
        $user = User::factory()->create();
        TelegramVerification::create([
            'user_id' => $user->id,
            'link_token' => 'abc123',
        ]);

        $this->assertInstanceOf(TelegramVerification::class, $user->telegramVerification);
        $this->assertSame('abc123', $user->telegramVerification->link_token);
    }
}
