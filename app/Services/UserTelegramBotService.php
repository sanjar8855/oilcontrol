<?php

namespace App\Services;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SaaS mijozi (ustaxona egasi) uchun alohida bot — @oilcontrol_customers_bot.
 * Ustaxonalarning o'z mijozlariga (Client modeli) xabar yuboradigan
 * TelegramBotService'dan butunlay mustaqil.
 */
class UserTelegramBotService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = (string) config('services.telegram.users_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Users bot xabar yuborishda xato: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Users bot xatolik: " . $e->getMessage());
            return false;
        }
    }

    public function setWebhook(string $url, string $secretToken): array
    {
        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url' => $url,
            'secret_token' => $secretToken,
        ]);

        return $response->json();
    }

    public function deleteWebhook(): array
    {
        return Http::post("{$this->apiUrl}/deleteWebhook")->json();
    }

    public function getMe(): array
    {
        return Http::get("{$this->apiUrl}/getMe")->json();
    }

    public function getWebhookInfo(): array
    {
        return Http::get("{$this->apiUrl}/getWebhookInfo")->json();
    }

    /**
     * /telegram/verify sahifasi ochilganda chaqiriladi. Bir foydalanuvchi
     * uchun bitta yozuv — qayta chaqirilsa, mavjud (hali ishlatilmagan)
     * link_token qaytariladi, chunki sahifani qayta yuklash allaqachon
     * yuborilgan kodni bekor qilmasligi kerak.
     */
    public function startVerification(User $user): TelegramVerification
    {
        return TelegramVerification::firstOrCreate(
            ['user_id' => $user->id],
            ['link_token' => Str::random(32)]
        );
    }

    /**
     * Botda "/start <token>" kelganda: tokenni topib, shu userning
     * chat_id'sini yozadi va 6 xonali kodni yuboradi. Token topilmasa,
     * jimgina rad javobi yuboriladi (userga bog'liq bo'lmagan xato bo'lgani
     * uchun DB'da hech narsa o'zgarmaydi).
     */
    public function handleStartCommand(int $chatId, string $token): void
    {
        $verification = TelegramVerification::where('link_token', $token)->first();

        if (!$verification) {
            $this->sendMessage((string) $chatId, "❌ Havola muddati o'tgan yoki noto'g'ri. Saytga qaytib, \"Kodni qayta yuborish\"ni bosing.");
            return;
        }

        $user = $verification->user;
        $user->update(['telegram_chat_id' => (string) $chatId]);

        $this->issueCode($verification, $chatId, $user);
    }

    protected function issueCode(TelegramVerification $verification, int $chatId, User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $verification->update([
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $message = "🔐 <b>Tasdiqlash kodi</b>\n\n";
        $message .= "Hurmatli {$user->name}, OilControl hisobingizni tasdiqlash uchun quyidagi kodni saytga kiriting:\n\n";
        $message .= "<code>{$code}</code>\n\n";
        $message .= "Kod 5 daqiqa amal qiladi.";

        $this->sendMessage((string) $chatId, $message);
    }

    /**
     * "Kodni qayta yuborish" tugmasi. 60 soniyalik throttle bilan.
     *
     * @return array{ok: bool, message: string}
     */
    public function resendCode(User $user): array
    {
        $verification = $user->telegramVerification;

        if (!$verification || !$user->telegram_chat_id) {
            return ['ok' => false, 'message' => "Avval Telegram botni ochib \"Start\" bosing."];
        }

        if ($verification->last_sent_at && $verification->last_sent_at->diffInSeconds(now()) < 60) {
            return ['ok' => false, 'message' => "Iltimos, 60 soniyadan keyin qayta urinib ko'ring."];
        }

        $this->issueCode($verification, (int) $user->telegram_chat_id, $user);

        return ['ok' => true, 'message' => "Kod qayta yuborildi."];
    }

    /**
     * Saytdagi forma orqali kod tasdiqlanadi.
     *
     * @return array{ok: bool, message: string}
     */
    public function verifyCode(User $user, string $code): array
    {
        $verification = $user->telegramVerification;

        if (!$verification || !$verification->code_expires_at) {
            return ['ok' => false, 'message' => "Avval Telegram botdan kod so'rang."];
        }

        if ($verification->attempts >= 5) {
            return ['ok' => false, 'message' => "Urinishlar soni tugadi. Yangi kod so'rang."];
        }

        if ($verification->code_expires_at->isPast()) {
            return ['ok' => false, 'message' => "Kod muddati tugagan. Yangi kod so'rang."];
        }

        if (!Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            if ($verification->fresh()->attempts >= 5) {
                return ['ok' => false, 'message' => "Kod noto'g'ri. Urinishlar tugadi — Yangi kod so'rang."];
            }

            return ['ok' => false, 'message' => "Kod noto'g'ri."];
        }

        $user->update(['telegram_verified_at' => now()]);
        $verification->delete();

        return ['ok' => true, 'message' => "Tasdiqlandi."];
    }

    /**
     * Onboarding (4-qadam) yakunlanganda namunaviy natija xabari.
     *
     * @param  array{saleTotal: float, profit: float, reminders: array}  $summary
     */
    public function sendOnboardingResult(User $user, array $summary): bool
    {
        if (!$user->telegram_chat_id || !$user->telegram_verified_at) {
            return false;
        }

        $remindersCount = count($summary['reminders'] ?? []);

        $message = "🎉 <b>O'quv bosqichi yakunlandi!</b>\n\n";
        $message .= "Bu — sinov uchun yaratilgan <b>namunaviy</b> savdo natijasi. ";
        $message .= "Kelajakda haqiqiy savdolaringiz bo'yicha xuddi shunday xabar shu yerga kelib turadi.\n\n";
        $message .= "💰 Savdo summasi: <b>" . number_format($summary['saleTotal'] ?? 0, 0, '.', ' ') . " so'm</b>\n";
        $message .= "📈 Foyda: <b>" . number_format($summary['profit'] ?? 0, 0, '.', ' ') . " so'm</b>\n";
        $message .= "🔔 Rejalashtirilgan eslatmalar: <b>{$remindersCount} ta</b>";

        return $this->sendMessage((string) $user->telegram_chat_id, $message);
    }
}
