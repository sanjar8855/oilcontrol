<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class SendSubscriptionExpiryWarningsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_it_sends_a_warning_through_the_users_bot_when_telegram_is_verified(): void
    {
        $capturedUrl = null;
        Http::fake(function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();
            return Http::response(['ok' => true], 200);
        });

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '123456', 'telegram_verified_at' => now()]);
        $workshop->update(['trial_ends_at' => now()->addDays(3), 'subscription_expires_at' => null]);

        $this->artisan('subscriptions:notify-expiring')->assertExitCode(0);

        $this->assertNotNull($capturedUrl);
        $this->assertStringContainsString(config('services.telegram.users_bot_token'), $capturedUrl);
    }

    public function test_it_skips_a_workshop_whose_owner_has_not_completed_telegram_verification(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '123456', 'telegram_verified_at' => null]);
        $workshop->update(['trial_ends_at' => now()->addDays(3), 'subscription_expires_at' => null]);

        $this->artisan('subscriptions:notify-expiring')->assertExitCode(0);

        Http::assertNothingSent();
    }
}
