<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telegram Mini App'dan kelgan `initData`ni HMAC-SHA256 orqali tekshiradi
 * (Telegram'ning rasmiy algoritmi: https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app).
 * Bot tokenini bilmasdan bu imzoni soxtalashtirib bo'lmaydi — shuning uchun
 * bu yerda Laravel session/Sanctum emas, faqat shu imzo ishonch manbai hisoblanadi.
 *
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 3.
 */
class VerifyTelegramInitData
{
    /**
     * initData necha soniyagacha amal qiladi (eski, "sizib chiqqan" havolalarni qayta ishlatishning oldini olish uchun).
     */
    private const MAX_AGE_SECONDS = 86400;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data') ?? $request->input('initData');

        if (!is_string($initData) || $initData === '') {
            return response()->json(['message' => 'Telegram initData topilmadi'], 401);
        }

        $telegramUser = $this->verify($initData);

        if (!$telegramUser) {
            return response()->json(['message' => 'Telegram imzosi noto\'g\'ri yoki muddati o\'tgan'], 401);
        }

        $request->attributes->set('telegram_user', $telegramUser);

        return $next($request);
    }

    /**
     * initData'ni tekshiradi, muvaffaqiyatli bo'lsa Telegram foydalanuvchi ma'lumotini qaytaradi.
     *
     * @return array{id: string, first_name: ?string, username: ?string}|null
     */
    private function verify(string $initData): ?array
    {
        parse_str($initData, $pairs);

        $hash = $pairs['hash'] ?? null;
        if (!$hash) {
            return null;
        }
        unset($pairs['hash']);

        $authDate = (int) ($pairs['auth_date'] ?? 0);
        if ($authDate <= 0 || (time() - $authDate) > self::MAX_AGE_SECONDS) {
            return null;
        }

        ksort($pairs);
        $dataCheckString = collect($pairs)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            return null;
        }

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($computedHash, $hash)) {
            return null;
        }

        $user = json_decode($pairs['user'] ?? '', true);
        if (!is_array($user) || empty($user['id'])) {
            return null;
        }

        return [
            'id' => (string) $user['id'],
            'first_name' => $user['first_name'] ?? null,
            'username' => $user['username'] ?? null,
        ];
    }
}
