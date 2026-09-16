<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserTelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_the_correct_secret_token(): void
    {
        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 1], 'text' => '/start whatever'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret'])
            ->assertForbidden();
    }

    public function test_it_rejects_requests_with_no_secret_token_at_all(): void
    {
        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 1], 'text' => '/start whatever'],
        ])->assertForbidden();
    }

    public function test_it_binds_the_chat_id_for_a_valid_start_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        TelegramVerification::create(['user_id' => $user->id, 'link_token' => 'test-token-123']);

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 777], 'text' => '/start test-token-123'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')])
            ->assertOk();

        $this->assertSame('777', $user->fresh()->telegram_chat_id);
    }
}
