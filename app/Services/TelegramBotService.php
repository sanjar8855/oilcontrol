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
     * Inline tugma bosilganda Telegram'ga "qabul qilindi" javobini yuborish
     * (aks holda foydalanuvchi ekranida tugma "yuklanmoqda" holatida qolib ketadi).
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        Http::post("{$this->apiUrl}/answerCallbackQuery", array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]));
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
     * Bot menyusidagi tugmani ("☰" yonidagi) Mini App'ga ochiladigan qilib sozlaydi.
     * BotFather'da qo'lda "Menu Button → Web App URL" sozlashning API orqali muqobili.
     */
    public function setMenuButton(): array
    {
        $url = config('services.telegram.mini_app_url');

        $response = Http::post("{$this->apiUrl}/setChatMenuButton", [
            'menu_button' => $url
                ? json_encode(['type' => 'web_app', 'text' => 'Garajim', 'web_app' => ['url' => $url]])
                : json_encode(['type' => 'default']),
        ]);

        return $response->json();
    }

    /**
     * /start buyrug'iga javob. Agar deep-link token (`/start <token>`) kelgan bo'lsa,
     * mijoz shu token orqali to'g'ridan-to'g'ri bog'lanadi (usta ulagan asosiy yo'l).
     * Token bo'lmasa, faqat "Telefon yuborish" tugmasi (contact-share) taklif qilinadi —
     * matn orqali raqam qabul qilinmaydi (xavfsizlik, docs 5.1-bo'lim).
     */
    public function handleStartCommand(int $chatId, string $firstName, ?string $token = null): void
    {
        if ($token) {
            $this->linkClientByToken($chatId, $token, $firstName);
            return;
        }

        $message = "🚗 <b>OilControl Bot'ga xush kelibsiz!</b>\n\n";
        $message .= "Assalomu alaykum, {$firstName}!\n\n";
        $message .= "Servis eslatmalarini olish uchun ustaxonangizdan sizga maxsus havola/QR so'rang — usta shu orqali sizni bir bosishda ulaydi.\n\n";
        $message .= "Agar ustaxonada allaqachon ro'yxatdan o'tgan bo'lsangiz, pastdagi tugma orqali telefon raqamingizni yuborishingiz ham mumkin.\n\n";
        $message .= "🔔 <b>Eslatmalar:</b>\n";
        $message .= "• Mashinangiz servisga muhtoj bo'lishidan 30 kun oldin\n";
        $message .= "• 14 kun oldin\n";
        $message .= "• 7 kun oldin";

        $this->sendMessage($chatId, $message, $this->contactRequestKeyboard());
    }

    /**
     * Usta ekranidagi QR/havola orqali kelgan bir martalik tokenni tekshirib,
     * mijozni shu chat_id ga bog'laydi. Token to'g'ridan-to'g'ri bitta Client
     * yozuvini ko'rsatgani uchun bu yo'lda telefon-qidiruv noaniqligi yo'q.
     */
    protected function linkClientByToken(int $chatId, string $token, string $firstName): void
    {
        $client = Client::where('telegram_link_token', $token)->first();

        if (!$client) {
            $message = "❌ Havola muddati o'tgan yoki noto'g'ri.\n\n";
            $message .= "Iltimos, ustaxona xodimidan yangi havola/QR so'rang.";
            $this->sendMessage($chatId, $message);
            return;
        }

        $isFirstTimeForThisChat = !Client::where('telegram_id', (string) $chatId)->whereNotNull('locale')->exists();

        $client->update([
            'telegram_id' => (string) $chatId,
            'telegram_link_token' => null,
            'telegram_linked_at' => now(),
        ]);

        Log::info("Telegram deep-link orqali bog'landi: mijoz #{$client->id} ({$client->name}) -> chat {$chatId}");

        $workshopName = $client->workshop?->name;
        $message = "✅ <b>Muvaffaqiyatli bog'landi, {$firstName}!</b>\n\n";
        $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . "\n\n";
        $message .= "🔔 Endi servis eslatmalarini shu yerda olasiz.";

        $this->sendMessage($chatId, $message, $isFirstTimeForThisChat ? null : ($this->miniAppKeyboard() ?? $this->removeKeyboard()));

        if ($isFirstTimeForThisChat) {
            $this->askLanguage($chatId);
        }
    }

    /**
     * Birinchi marta ulanganda mijozdan qaysi tilda xabar olishni so'raydi.
     * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 4.
     */
    public function askLanguage(int $chatId): void
    {
        $this->sendMessage($chatId, "🌐 Tilni tanlang / Выберите язык:", [
            'inline_keyboard' => [[
                ['text' => "🇺🇿 O'zbekcha", 'callback_data' => 'lang:uz'],
                ['text' => '🇷🇺 Русский', 'callback_data' => 'lang:ru'],
            ]],
        ]);
    }

    /**
     * Til tanlash tugmasi bosilganda — shu chat_id ga bog'langan barcha mijoz
     * yozuvlariga (turli moyxonalarda bo'lsa ham) tanlangan til qo'llaniladi.
     */
    public function handleLanguageSelection(int $chatId, string $locale, string $callbackQueryId): void
    {
        if (!in_array($locale, ['uz', 'ru'], true)) {
            return;
        }

        Client::where('telegram_id', (string) $chatId)->update(['locale' => $locale]);

        $confirmations = [
            'uz' => "✅ Til: O'zbekcha",
            'ru' => '✅ Язык: Русский',
        ];

        $this->answerCallbackQuery($callbackQueryId, $confirmations[$locale]);
        $this->sendMessage($chatId, $confirmations[$locale], $this->miniAppKeyboard());
    }

    /**
     * Botga kiritilgan, telefon raqamiga o'xshamaydigan har qanday xabarga javob
     */
    public function handleDefaultMessage(int $chatId, string $firstName): void
    {
        $message = "Salom {$firstName}! 👋\n\n";
        $message .= "Bog'lanish uchun ustaxonadan havola/QR so'rang, yoki pastdagi tugma orqali telefon raqamingizni yuboring.\n\n";
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
        $message .= "/start - Boshlash (ustaxona bergan havola orqali avtomatik bog'lanadi)\n";
        $message .= "/help - Yordam\n";
        $message .= "/myid - Bog'langan mijozlarni va Telegram ID ni ko'rish\n\n";
        $message .= "<b>Qanday ishlaydi?</b>\n";
        $message .= "1. Ustaxonadan olgan havola/QR orqali /start bosing — avtomatik bog'lanasiz\n";
        $message .= "2. Yoki pastdagi \"Telefon raqamni yuborish\" tugmasi orqali o'z raqamingizni tasdiqlang\n\n";
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
     * Telegram tasdiqlagan (contact-share) telefon raqamni tizimdagi mijozlar bilan
     * solishtirib, topilsa Telegram ID ni bog'lash. Mijoz allaqachon BOSHQA chat_id ga
     * bog'langan bo'lsa, avtomatik tortib olinmaydi — moyxona xodimi tasdiqlashi kerak
     * (docs 5.1-bo'lim, hisob o'g'irlanishining oldini olish uchun).
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

        $isFirstTimeForThisChat = !Client::where('telegram_id', (string) $chatId)->whereNotNull('locale')->exists();

        $linkedNow = [];
        $alreadyLinked = [];
        $blockedElsewhere = [];

        foreach ($clients as $client) {
            if ((string) $client->telegram_id === (string) $chatId) {
                $alreadyLinked[] = $client;
                continue;
            }

            if (filled($client->telegram_id)) {
                // Boshqa chat_id ga bog'langan — silliq tortib olinmaydi, xodim tasdiqlashi kerak.
                $blockedElsewhere[] = $client;
                Log::warning("Telegram bog'lash rad etildi (allaqachon boshqa chat'ga bog'langan): mijoz #{$client->id} ({$client->name}), yangi chat {$chatId}");
                continue;
            }

            $client->update([
                'telegram_id' => (string) $chatId,
                'telegram_link_token' => null,
                'telegram_linked_at' => now(),
            ]);
            $linkedNow[] = $client;

            Log::info("Telegram bog'landi (contact-share): mijoz #{$client->id} ({$client->name}) -> chat {$chatId}");
        }

        if (empty($linkedNow) && empty($alreadyLinked)) {
            $message = "⚠️ Bu raqam tizimda topildi, lekin allaqachon boshqa Telegram hisobiga ulangan.\n\n";
            $message .= "Iltimos, ustaxona xodimiga murojaat qiling — u tasdiqlagach qayta ulanasiz.";
            $this->sendMessage($chatId, $message);
            return;
        }

        $message = "✅ <b>Muvaffaqiyatli bog'landi, {$firstName}!</b>\n\n";

        foreach ($linkedNow as $client) {
            $workshopName = $client->workshop?->name;
            $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . "\n";
        }

        foreach ($alreadyLinked as $client) {
            $workshopName = $client->workshop?->name;
            $message .= "• {$client->name}" . ($workshopName ? " ({$workshopName})" : '') . " — allaqachon ulangan\n";
        }

        if (!empty($blockedElsewhere)) {
            $message .= "\n⚠️ Ba'zi yozuvlar boshqa Telegram hisobiga ulangan bo'lgani uchun o'tkazib yuborildi — ustaxona xodimiga murojaat qiling.\n";
        }

        $message .= "\n🔔 Endi servis eslatmalarini shu yerda olasiz.";

        $this->sendMessage($chatId, $message, $isFirstTimeForThisChat ? $this->removeKeyboard() : ($this->miniAppKeyboard() ?? $this->removeKeyboard()));

        if ($isFirstTimeForThisChat) {
            $this->askLanguage($chatId);
        }
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
     * Mini App'ni ochuvchi inline tugma ("Garajimni ochish"). Faqat inline
     * klaviaturalarda ishlaydi (web_app request_contact kabi reply-klaviaturada emas).
     * TELEGRAM_MINI_APP_URL sozlanmagan bo'lsa, tugma yuborilmaydi (null).
     */
    protected function miniAppKeyboard(?string $buttonText = null): ?array
    {
        $url = config('services.telegram.mini_app_url');

        if (!$url) {
            return null;
        }

        return [
            'inline_keyboard' => [[
                ['text' => $buttonText ?? '🚗 Garajimni ochish', 'web_app' => ['url' => $url]],
            ]],
        ];
    }

    /**
     * Servis eslatma xabarini formatlash va yuborish
     */
    public function sendServiceReminder(string $chatId, array $data): bool
    {
        $isRu = ($data['locale'] ?? 'uz') === 'ru';

        if ($isRu) {
            $message = "🔔 <b>Напоминание о сервисе</b>\n\n";
            $message .= "Уважаемый(ая) <b>{$data['client_name']}</b>!\n\n";
            $message .= "🚗 Автомобиль: <b>{$data['vehicle_make']} {$data['vehicle_model']}</b>\n";
            $message .= "📅 Следующий сервис: <b>через {$data['days_remaining']} дн.</b>\n\n";
            $message .= "📊 Данные:\n";
            $message .= "• Последний сервис: {$data['last_service_km']} км\n";
            $message .= "• Следующий сервис: {$data['next_service_km']} км\n";
            $message .= "• Тип услуги: {$data['service_type']}\n\n";

            if (isset($data['workshop_phone'])) {
                $message .= "📞 Телефон сервиса: {$data['workshop_phone']}\n";
            }
            if (isset($data['workshop_name'])) {
                $message .= "🏢 {$data['workshop_name']}\n";
            }

            $message .= "\n✅ Не забудьте вовремя приехать на сервис!";
        } else {
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
        }

        return $this->sendMessage($chatId, $message, $this->miniAppKeyboard($isRu ? '🚗 Открыть гараж' : null));
    }

    /**
     * Xizmat tugagach mijozga avtomatik yuboriladigan elektron kvitansiya —
     * "chek yo'q" bo'shlig'ini to'ldiradi (docs 2-bo'lim).
     */
    public function sendReceipt(string $chatId, array $data): bool
    {
        $isRu = ($data['locale'] ?? 'uz') === 'ru';

        if ($isRu) {
            $message = "🧾 <b>Квитанция</b>\n\n";
            $message .= "Уважаемый(ая) <b>{$data['client_name']}</b>!\n\n";
            $message .= "🚗 {$data['vehicle_make']} {$data['vehicle_model']}\n";
            $message .= "🔧 Услуга: {$data['service_type']}\n";
            $message .= "📏 Пробег: {$data['odometer_reading']} км\n\n";
            $message .= "💰 Итого: {$data['total_amount']}\n";

            $message .= (($data['remaining_amount'] ?? 0) > 0)
                ? "⚠️ Остаток долга: {$data['remaining_amount']}\n"
                : "✅ Оплачено полностью\n";

            $message .= "\n📅 Следующий сервис: <b>{$data['next_service_km']} км</b>";
        } else {
            $message = "🧾 <b>Kvitansiya</b>\n\n";
            $message .= "Hurmatli <b>{$data['client_name']}</b>!\n\n";
            $message .= "🚗 {$data['vehicle_make']} {$data['vehicle_model']}\n";
            $message .= "🔧 Xizmat: {$data['service_type']}\n";
            $message .= "📏 Probeg: {$data['odometer_reading']} km\n\n";
            $message .= "💰 Jami: {$data['total_amount']}\n";

            $message .= (($data['remaining_amount'] ?? 0) > 0)
                ? "⚠️ Qarz qoldig'i: {$data['remaining_amount']}\n"
                : "✅ To'liq to'landi\n";

            $message .= "\n📅 Keyingi servis: <b>{$data['next_service_km']} km</b>";
        }

        return $this->sendMessage($chatId, $message, $this->miniAppKeyboard($isRu ? '🧾 Смотреть квитанции' : '🧾 Kvitansiyalarni ko\'rish'));
    }
}
