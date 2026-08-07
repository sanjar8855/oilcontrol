<?php

namespace App\Services;

use App\Models\Client;
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
    public function sendMessage(string $chatId, string $message, ?array $replyMarkup = null): bool
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $response = Http::post("{$this->apiUrl}/sendMessage", $payload);

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
     * /start buyrug'iga javob — telefon raqamini so'raymiz
     */
    public function handleStartCommand(int $chatId, string $firstName): void
    {
        $message = "🚗 <b>OilControl Bot'ga xush kelibsiz!</b>\n\n";
        $message .= "Assalomu alaykum, {$firstName}!\n\n";
        $message .= "Servis eslatmalarini olish uchun ustaxonaga bergan <b>telefon raqamingizni</b> yuboring.\n\n";
        $message .= "📱 Pastdagi tugmani bosing yoki raqamni qo'lda yozing (masalan: <code>+998901234567</code>).\n\n";
        $message .= "🔔 <b>Eslatmalar:</b>\n";
        $message .= "• Mashinangiz servisga muhtoj bo'lishidan 30 kun oldin\n";
        $message .= "• 14 kun oldin\n";
        $message .= "• 7 kun oldin";

        $this->sendMessage($chatId, $message, $this->contactRequestKeyboard());
    }

    /**
     * Botga kiritilgan, telefon raqamiga o'xshamaydigan har qanday xabarga javob
     */
    public function handleDefaultMessage(int $chatId, string $firstName): void
    {
        $message = "Salom {$firstName}! 👋\n\n";
        $message .= "Servis eslatmalarini olish uchun telefon raqamingizni yuboring ";
        $message .= "(masalan: <code>+998901234567</code>) yoki pastdagi tugmadan foydalaning.\n\n";
        $message .= "Batafsil ma'lumot uchun /help ni bosing.";

        $this->sendMessage($chatId, $message, $this->contactRequestKeyboard());
    }

    /**
     * /help buyrug'iga javob
     */
    public function handleHelpCommand(int $chatId): void
    {
        $message = "ℹ️ <b>Yordam</b>\n\n";
        $message .= "<b>Buyruqlar:</b>\n";
        $message .= "/start - Boshlash va telefon raqamni bog'lash\n";
        $message .= "/help - Yordam\n";
        $message .= "/myid - Bog'langan mijozlarni va Telegram ID ni ko'rish\n\n";
        $message .= "<b>Qanday ishlaydi?</b>\n";
        $message .= "1. Telefon raqamingizni yuboring (ustaxonaga bergan raqam bilan bir xil bo'lishi kerak)\n";
        $message .= "2. Raqam tizimda topilsa, avtomatik bog'lanasiz\n";
        $message .= "3. Bir nechta mijoz hisobiga (masalan, boshqa raqamga) ulanmoqchi bo'lsangiz, o'sha raqamni ham yuborishingiz mumkin\n\n";
        $message .= "<b>Bot haqida:</b>\n";
        $message .= "OilControl - bu avtomobil servis eslatma tizimi. ";
        $message .= "Bot sizga mashinangiz servisga muhtoj bo'lganida avtomatik eslatma yuboradi.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * /myid buyrug'iga javob — Telegram ID va bog'langan mijozlar ro'yxati
     */
    public function handleMyIdCommand(int $chatId): void
    {
        $clients = Client::query()
            ->where('telegram_id', (string) $chatId)
            ->with('workshop')
            ->get();

        $message = "📱 <b>Sizning Telegram ID:</b> <code>{$chatId}</code>\n\n";

        if ($clients->isEmpty()) {
            $message .= "Hozircha hech qanday mijoz hisobiga bog'lanmagansiz.\n";
            $message .= "Bog'lanish uchun telefon raqamingizni yuboring.";
        } else {
            $message .= "🔗 <b>Bog'langan mijozlar:</b>\n";
            foreach ($clients as $client) {
                $workshopName = $client->workshop?->name;
                $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . "\n";
            }
        }

        $this->sendMessage($chatId, $message);
    }

    /**
     * Foydalanuvchi "Telefon raqamni yuborish" tugmasi orqali kontakt yuborganda
     */
    public function handleContactShared(int $chatId, array $contact, ?int $fromUserId, string $firstName): void
    {
        // O'zga birovning kontaktini yuborib, uni o'ziga bog'lab olishning oldini olamiz
        if (isset($contact['user_id']) && $fromUserId && (int) $contact['user_id'] !== (int) $fromUserId) {
            $this->sendMessage(
                $chatId,
                "⚠️ Iltimos, faqat <b>o'zingizning</b> telefon raqamingizni yuboring.",
                $this->contactRequestKeyboard()
            );
            return;
        }

        $this->handlePhoneNumber($chatId, (string) $contact['phone_number'], $firstName);
    }

    /**
     * Matn sifatida yuborilgan raqam telefon raqamiga o'xshaydimi
     */
    public function looksLikePhoneNumber(string $text): bool
    {
        $digits = preg_replace('/\D+/', '', $text);
        return $digits !== null && strlen($digits) >= 9 && strlen($digits) <= 15;
    }

    /**
     * Telefon raqamni tizimdagi mijozlar bilan solishtirib, topilsa Telegram ID ni bog'lash.
     * Bitta raqam bir nechta mijoz yozuviga tegishli bo'lsa (masalan turli ustaxonalarda),
     * barchasi shu Telegram akkauntga bog'lanadi. Foydalanuvchi keyinroq boshqa raqam
     * yuborsa, o'sha mijoz(lar) ham shu akkauntga qo'shilib boraveradi.
     */
    public function handlePhoneNumber(int $chatId, string $phone, string $firstName): void
    {
        $clients = $this->findClientsByPhone($phone);

        if ($clients->isEmpty()) {
            $message = "❌ Kechirasiz, <b>{$phone}</b> raqami tizimda topilmadi.\n\n";
            $message .= "Iltimos, avval ustaxonangizga murojaat qilib, mijoz sifatida ro'yxatdan o'ting. ";
            $message .= "Shundan so'ng shu raqamni botga qaytadan yuboring.";

            $this->sendMessage($chatId, $message, $this->contactRequestKeyboard());
            return;
        }

        $linkedNow = [];
        $relinked = [];
        $alreadyLinked = [];

        foreach ($clients as $client) {
            if ((string) $client->telegram_id === (string) $chatId) {
                $alreadyLinked[] = $client;
                continue;
            }

            $wasLinkedElsewhere = filled($client->telegram_id);
            $client->update(['telegram_id' => (string) $chatId]);

            if ($wasLinkedElsewhere) {
                $relinked[] = $client;
            } else {
                $linkedNow[] = $client;
            }

            Log::info("Telegram bog'landi: mijoz #{$client->id} ({$client->name}) -> chat {$chatId}");
        }

        $message = "✅ <b>Muvaffaqiyatli bog'landi, {$firstName}!</b>\n\n";

        foreach (array_merge($linkedNow, $relinked) as $client) {
            $workshopName = $client->workshop?->name;
            $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . "\n";
        }

        foreach ($alreadyLinked as $client) {
            $workshopName = $client->workshop?->name;
            $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . " — allaqachon ulangan\n";
        }

        $message .= "\n🔔 Endi servis eslatmalarini shu yerda olasiz.\n\n";
        $message .= "➕ Yana boshqa mijoz hisobini (masalan, boshqa telefon raqamini) bog'lamoqchi bo'lsangiz, o'sha raqamni yuboring.";

        $this->sendMessage($chatId, $message, $this->removeKeyboard());
    }

    /**
     * Telefon raqami bo'yicha mos keluvchi barcha mijoz yozuvlarini topish.
     * Raqamlar +998/998/0 prefiksi yoki bo'shliq/tire kabi formatlardan qat'i nazar
     * oxirgi 9 ta raqam bo'yicha solishtiriladi.
     */
    protected function findClientsByPhone(string $phone)
    {
        $suffix = $this->normalizePhoneSuffix($phone);

        if (!$suffix) {
            return collect();
        }

        return Client::query()
            ->whereRaw(
                "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), 9) = ?",
                [$suffix]
            )
            ->whereRaw("LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '')) >= 9")
            ->with('workshop')
            ->get();
    }

    /**
     * Raqamdagi oxirgi 9 ta raqamni qaytaradi (O'zbekiston telefon raqamlari uzunligi)
     */
    protected function normalizePhoneSuffix(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (!$digits || strlen($digits) < 9) {
            return null;
        }

        return substr($digits, -9);
    }

    /**
     * Kontakt so'rash tugmachasi
     */
    protected function contactRequestKeyboard(): array
    {
        return [
            'keyboard' => [
                [
                    ['text' => '📱 Telefon raqamni yuborish', 'request_contact' => true],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    /**
     * Klaviaturani olib tashlash
     */
    protected function removeKeyboard(): array
    {
        return ['remove_keyboard' => true];
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
