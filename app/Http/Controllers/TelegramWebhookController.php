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
            $fromUserId = $message['from']['id'] ?? null;

            // Foydalanuvchi "Telefon raqamni yuborish" tugmasi orqali kontakt yuborsa
            if (isset($message['contact'])) {
                $this->telegramBot->handleContactShared($chatId, $message['contact'], $fromUserId, $firstName);
            } elseif ($text === '/start' || str_starts_with($text, '/start ')) {
                // Deep-link: "/start <token>" — usta ekranidan chiqqan QR/havola shu payloadni yuboradi
                $token = trim(substr($text, 6)) ?: null;
                $this->telegramBot->handleStartCommand($chatId, $firstName, $token);
            } elseif ($text === '/help') {
                $this->telegramBot->handleHelpCommand($chatId);
            } elseif ($text === '/myid') {
                $this->telegramBot->handleMyIdCommand($chatId);
            } else {
                // Matn orqali telefon raqam qabul qilinmaydi (xavfsizlik, docs 5.1-bo'lim) —
                // faqat deep-link token va contact-share orqali bog'lanish mumkin.
                $this->telegramBot->handleDefaultMessage($chatId, $firstName);
            }
        }

        // Inline tugma bosilganda (masalan, til tanlash) kelgan callback_query
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $chatId = $callback['message']['chat']['id'] ?? null;
            $data = $callback['data'] ?? '';

            if ($chatId && str_starts_with($data, 'lang:')) {
                $locale = substr($data, 5);
                $this->telegramBot->handleLanguageSelection($chatId, $locale, $callback['id']);
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

    /**
     * Bot menyu tugmasini Mini App'ga sozlash (BotFather'dagi qo'lda sozlashning o'rnini bosadi)
     */
    public function setMenuButton(): JsonResponse
    {
        $result = $this->telegramBot->setMenuButton();

        return response()->json($result);
    }
}
