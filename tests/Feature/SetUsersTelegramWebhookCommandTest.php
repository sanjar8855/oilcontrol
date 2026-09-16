<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetUsersTelegramWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calls_telegrams_set_webhook_api_with_the_configured_secret(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200)]);

        $this->artisan('telegram:set-users-webhook')->assertExitCode(0);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'setWebhook')
                && $request['secret_token'] === config('services.telegram.users_bot_webhook_secret')
                && str_contains($request['url'], '/telegram/users-webhook');
        });
    }
}
