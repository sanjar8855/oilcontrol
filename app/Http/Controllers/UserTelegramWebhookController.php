<?php

namespace App\Http\Controllers;

use App\Services\UserTelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserTelegramWebhookController extends Controller
{
    public function __construct(protected UserTelegramBotService $telegramBot)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!$secret || $secret !== config('services.telegram.users_bot_webhook_secret')) {
            Log::warning('Users bot webhook: secret_token mos kelmadi');
            abort(403);
        }

        $message = $request->input('message');

        if ($message && isset($message['text']) && str_starts_with($message['text'], '/start ')) {
            $chatId = (int) ($message['chat']['id'] ?? 0);
            $token = trim(substr($message['text'], 7));

            if ($chatId && $token !== '') {
                $this->telegramBot->handleStartCommand($chatId, $token);
            }
        }

        return response()->json(['ok' => true]);
    }
}
