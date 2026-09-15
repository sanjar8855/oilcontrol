<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class TelegramVerificationControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_show_renders_the_verify_page_with_a_deep_link(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $response = $this->actingAs($user)->get(route('telegram.verify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Telegram/Verify')
            ->where('deepLink', fn ($link) => str_contains($link, 'https://t.me/') && str_contains($link, '?start=')));
    }

    public function test_submitting_the_correct_code_verifies_the_user(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')])
            ->assertOk();

        preg_match('/<code>(\d{6})<\/code>/', $capturedText, $matches);

        $response = $this->actingAs($user)->post(route('telegram.verify.code'), ['code' => $matches[1]]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->telegram_verified_at);
    }

    public function test_submitting_a_wrong_code_shows_an_error(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')]);

        $response = $this->actingAs($user)->post(route('telegram.verify.code'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->telegram_verified_at);
    }

    public function test_resend_is_throttled_right_after_the_first_code(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')]);

        $response = $this->actingAs($user)->post(route('telegram.verify.resend'));

        $response->assertSessionHas('status');
        $this->assertStringContainsString('60', session('status'));
    }

    public function test_the_verify_page_itself_stays_reachable_while_unverified(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'))->assertOk();
    }
}
