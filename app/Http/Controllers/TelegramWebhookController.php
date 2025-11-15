<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    /**
     * Telegram webhook orqali kelgan xabarlarni qabul qilish
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        // Logga yozish (debugging uchun)
        Log::info('Telegram webhook received', $update);

        // Agar xabar bo'lsa
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $firstName = $message['from']['first_name'] ?? 'Foydalanuvchi';

            // Buyruqlarni tekshirish
            if ($text === '/start') {
                $this->telegramBot->handleStartCommand($chatId, $firstName);
            } elseif ($text === '/help') {
                $this->telegramBot->handleHelpCommand($chatId);
            } elseif ($text === '/myid') {
                $this->telegramBot->handleMyIdCommand($chatId);
            } else {
                // Har qanday boshqa xabar uchun
                $this->telegramBot->handleDefaultMessage($chatId, $firstName);
            }
        }

        // Telegram serveriga "OK" javob qaytarish
        return response()->json(['ok' => true]);
    }

    /**
     * Webhookni o'rnatish (Artisan buyrug'i orqali ishlatiladi)
     */
    public function setWebhook(): JsonResponse
    {
        $url = route('telegram.webhook');
        $result = $this->telegramBot->setWebhook($url);

        return response()->json($result);
    }

    /**
     * Webhook ma'lumotlarini olish
     */
    public function getWebhookInfo(): JsonResponse
    {
        $info = $this->telegramBot->getWebhookInfo();

        return response()->json($info);
    }

    /**
     * Webhookni o'chirish
     */
    public function deleteWebhook(): JsonResponse
    {
        $result = $this->telegramBot->deleteWebhook();

        return response()->json($result);
    }

    /**
     * Bot ma'lumotlarini olish
     */
    public function getBotInfo(): JsonResponse
    {
        $info = $this->telegramBot->getMe();

        return response()->json($info);
    }
}
