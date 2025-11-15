<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Telegram orqali xabar yuborish
     */
    public function sendMessage(string $chatId, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info("Telegram xabar yuborildi: Chat ID {$chatId}");
                return true;
            }

            Log::error("Telegram xabar yuborishda xato: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Telegram xatolik: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Webhookni o'rnatish
     */
    public function setWebhook(string $url): array
    {
        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url' => $url,
        ]);

        return $response->json();
    }

    /**
     * Webhookni o'chirish
     */
    public function deleteWebhook(): array
    {
        $response = Http::post("{$this->apiUrl}/deleteWebhook");
        return $response->json();
    }

    /**
     * Bot ma'lumotlarini olish
     */
    public function getMe(): array
    {
        $response = Http::get("{$this->apiUrl}/getMe");
        return $response->json();
    }

    /**
     * Webhook ma'lumotlarini olish
     */
    public function getWebhookInfo(): array
    {
        $response = Http::get("{$this->apiUrl}/getWebhookInfo");
        return $response->json();
    }

    /**
     * /start buyrug'iga javob
     */
    public function handleStartCommand(int $chatId, string $firstName): void
    {
        $message = "🚗 <b>OilControl Bot'ga xush kelibsiz!</b>\n\n";
        $message .= "Assalomu alaykum, {$firstName}!\n\n";
        $message .= "📱 <b>Sizning Telegram ID:</b>\n";
        $message .= "<code>{$chatId}</code>\n\n";
        $message .= "📋 <b>Bu ID ni nima qilish kerak?</b>\n";
        $message .= "Bu ID raqamini avtomobil servisiga (ustaxonaga) bering. ";
        $message .= "Ular sizni mijoz sifatida qo'shganda bu ID ni kiritadilar. ";
        $message .= "Shundan keyin siz avtomatik ravishda servis eslatmalarini olasiz.\n\n";
        $message .= "🔔 <b>Eslatmalar:</b>\n";
        $message .= "• Mashinangiz servisga muhtoj bo'lishidan 30 kun oldin\n";
        $message .= "• 14 kun oldin\n";
        $message .= "• 7 kun oldin\n\n";
        $message .= "❓ Savollaringiz bo'lsa, ustaxonangizga murojaat qiling.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Botga kiritilgan har qanday xabarga javob
     */
    public function handleDefaultMessage(int $chatId, string $firstName): void
    {
        $message = "Salom {$firstName}! 👋\n\n";
        $message .= "📱 Sizning Telegram ID: <code>{$chatId}</code>\n\n";
        $message .= "Bu ID ni avtomobil servisiga bering va avtomatik eslatmalar olishni boshlang!\n\n";
        $message .= "Batafsil ma'lumot olish uchun /start ni bosing.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * /help buyrug'iga javob
     */
    public function handleHelpCommand(int $chatId): void
    {
        $message = "ℹ️ <b>Yordam</b>\n\n";
        $message .= "<b>Buyruqlar:</b>\n";
        $message .= "/start - Boshlash va ID ni olish\n";
        $message .= "/help - Yordam\n";
        $message .= "/myid - Telegram ID ni ko'rish\n\n";
        $message .= "<b>Bot haqida:</b>\n";
        $message .= "OilControl - bu avtomobil servis eslatma tizimi. ";
        $message .= "Bot sizga mashinangiz servisga muhtoj bo'lganida avtomatik eslatma yuboradi.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * /myid buyrug'iga javob
     */
    public function handleMyIdCommand(int $chatId): void
    {
        $message = "📱 <b>Sizning Telegram ID:</b>\n\n";
        $message .= "<code>{$chatId}</code>\n\n";
        $message .= "Bu ID ni ustaxonangizga bering!";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Servis eslatma xabarini formatlash va yuborish
     */
    public function sendServiceReminder(string $chatId, array $data): bool
    {
        $message = "🔔 <b>Servis Eslatmasi</b>\n\n";
        $message .= "Hurmatli <b>{$data['client_name']}</b>!\n\n";
        $message .= "🚗 Avtomobil: <b>{$data['vehicle_make']} {$data['vehicle_model']}</b>\n";
        $message .= "📅 Keyingi servis: <b>{$data['days_remaining']} kundan keyin</b>\n\n";
        $message .= "📊 Ma'lumotlar:\n";
        $message .= "• Oxirgi servis: {$data['last_service_km']} km\n";
        $message .= "• Keyingi servis: {$data['next_service_km']} km\n";
        $message .= "• Servis turi: {$data['service_type']}\n\n";

        if (isset($data['workshop_phone'])) {
            $message .= "📞 Ustaxona telefoni: {$data['workshop_phone']}\n";
        }

        if (isset($data['workshop_name'])) {
            $message .= "🏢 {$data['workshop_name']}\n";
        }

        $message .= "\n✅ Vaqtida servisga kelishni unutmang!";

        return $this->sendMessage($chatId, $message);
    }
}
