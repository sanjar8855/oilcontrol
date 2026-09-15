<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use App\Services\UserTelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserTelegramBotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_verification_creates_a_link_token_for_the_user(): void
    {
        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);

        $verification = $service->startVerification($user);

        $this->assertNotEmpty($verification->link_token);
        $this->assertSame($user->id, $verification->user_id);
    }

    public function test_start_verification_reuses_the_existing_record_on_repeat_calls(): void
    {
        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);

        $first = $service->startVerification($user);
        $second = $service->startVerification($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->link_token, $second->link_token);
    }

    public function test_handle_start_command_binds_the_chat_id_and_sends_a_code(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);

        $service->handleStartCommand(555666, $verification->link_token);

        $this->assertSame('555666', $user->fresh()->telegram_chat_id);
        $this->assertNotNull($verification->fresh()->code_hash);
        $this->assertNotNull($verification->fresh()->code_expires_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_handle_start_command_ignores_an_unknown_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $service = app(UserTelegramBotService::class);
        $service->handleStartCommand(999, 'unknown-token');

        $this->assertDatabaseCount('telegram_verifications', 0);
    }

    public function test_verify_code_marks_the_user_as_verified_on_a_correct_code(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        preg_match('/<code>(\d{6})<\/code>/', $capturedText, $matches);
        $result = $service->verifyCode($user->fresh(), $matches[1]);

        $this->assertTrue($result['ok']);
        $this->assertNotNull($user->fresh()->telegram_verified_at);
        $this->assertDatabaseCount('telegram_verifications', 0);
    }

    public function test_verify_code_rejects_a_wrong_code_and_counts_the_attempt(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        // NOTE: brief's test used User::factory()->create(), but Task 2's UserFactory
        // defaults telegram_verified_at to now() for every created user, which made the
        // final assertNull(...) assertion fail regardless of implementation correctness
        // (the user was already "verified" before verifyCode() was ever called). Using
        // unverifiedTelegram() restores the test's evident intent: a wrong code should
        // not verify the user. Flagged in task-3-report.md rather than silently changed.
        $user = User::factory()->unverifiedTelegram()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        $result = $service->verifyCode($user->fresh(), '000000');

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $verification->fresh()->attempts);
        $this->assertNull($user->fresh()->telegram_verified_at);
    }

    public function test_verify_code_locks_out_after_five_wrong_attempts(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        for ($i = 0; $i < 5; $i++) {
            $service->verifyCode($user->fresh(), '000000');
        }

        $result = $service->verifyCode($user->fresh(), '000000');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Yangi kod', $result['message']);
    }

    public function test_resend_code_is_throttled_within_sixty_seconds(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        $result = $service->resendCode($user->fresh());

        $this->assertFalse($result['ok']);
    }

    public function test_send_onboarding_result_does_nothing_without_a_verified_chat(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->unverifiedTelegram()->create();
        $service = app(UserTelegramBotService::class);

        $sent = $service->sendOnboardingResult($user, ['saleTotal' => 40000, 'profit' => 10000, 'reminders' => []]);

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_send_onboarding_result_sends_a_message_with_a_verified_chat(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create(['telegram_chat_id' => '777']);
        $service = app(UserTelegramBotService::class);

        $sent = $service->sendOnboardingResult($user, ['saleTotal' => 40000, 'profit' => 10000, 'reminders' => ['2026-10-01']]);

        $this->assertTrue($sent);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }
}
