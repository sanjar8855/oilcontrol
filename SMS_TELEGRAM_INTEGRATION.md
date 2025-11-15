# SMS va Telegram Integratsiya Qo'llanmasi

Bu qo'llanma OilControl loyihangizga SMS va Telegram orqali eslatma yuborish funksiyasini to'liq faollashtirish uchun zarur bo'lgan barcha qadamlarni tushuntiradi.

## 🚀 Hozirda Tayyor Bo'lgan Funksiyalar

✅ Avtomatik eslatma sistemasi to'liq ishlaydi
✅ ServiceLog yaratilganda 30, 14, 7 kun oldin eslatmalar avtomatik yaratiladi
✅ Laravel Scheduler har kuni soat 9:00 da eslatmalarni tekshiradi
✅ Mijozlarga Telegram ID qo'shish imkoniyati mavjud
✅ ReminderService SMS va Telegram yuborish uchun tayyor

## 📋 Qolgan Qadamlar

### 1. SMS Integration (Eskiz.uz yoki Playmobile)

#### Eskiz.uz API (Tavsiya Etiladi)

**A. Ro'yxatdan o'tish:**
1. [eskiz.uz](https://eskiz.uz) saytiga kiring
2. Ro'yxatdan o'ting va balans to'ldiring
3. API kalitingizni oling

**B. Konfiguratsiya:**

`.env` fayliga qo'shing:
```env
ESKIZ_EMAIL=your-email@example.com
ESKIZ_PASSWORD=your-password
ESKIZ_SENDER=4546
```

**C. HTTP Client Sozlash:**

`config/services.php` fayliga qo'shing:
```php
'eskiz' => [
    'email' => env('ESKIZ_EMAIL'),
    'password' => env('ESKIZ_PASSWORD'),
    'sender' => env('ESKIZ_SENDER', '4546'),
    'api_url' => 'https://notify.eskiz.uz/api',
],
```

**D. ReminderService ni Yangilash:**

`app/Services/ReminderService.php` faylida `sendSMS()` metodini yangilang:

```php
protected function sendSMS(string $phoneNumber, string $message): void
{
    // Birinchi token oling
    $tokenResponse = Http::post(config('services.eskiz.api_url') . '/auth/login', [
        'email' => config('services.eskiz.email'),
        'password' => config('services.eskiz.password'),
    ]);

    if (!$tokenResponse->successful()) {
        throw new \Exception('Eskiz.uz token olishda xato');
    }

    $token = $tokenResponse->json()['data']['token'];

    // SMS yuborish
    $response = Http::withToken($token)->post(
        config('services.eskiz.api_url') . '/message/sms/send',
        [
            'mobile_phone' => $phoneNumber,
            'message' => $message,
            'from' => config('services.eskiz.sender'),
        ]
    );

    if (!$response->successful()) {
        throw new \Exception('SMS yuborishda xato: ' . $response->body());
    }

    \Log::info("SMS yuborildi: {$phoneNumber}");
}
```

#### Playmobile API (Muqobil)

`.env` fayliga:
```env
PLAYMOBILE_LOGIN=your-login
PLAYMOBILE_PASSWORD=your-password
PLAYMOBILE_SENDER=OilControl
```

`config/services.php`:
```php
'playmobile' => [
    'login' => env('PLAYMOBILE_LOGIN'),
    'password' => env('PLAYMOBILE_PASSWORD'),
    'sender' => env('PLAYMOBILE_SENDER'),
    'api_url' => 'http://91.204.239.42:8083/broker-api/send',
],
```

ReminderService:
```php
protected function sendSMS(string $phoneNumber, string $message): void
{
    $response = Http::get(config('services.playmobile.api_url'), [
        'login' => config('services.playmobile.login'),
        'password' => config('services.playmobile.password'),
        'sender' => config('services.playmobile.sender'),
        'messages' => json_encode([[
            'recipient' => $phoneNumber,
            'message-id' => uniqid(),
            'sms' => [
                'originator' => config('services.playmobile.sender'),
                'content' => ['text' => $message],
            ],
        ]]),
    ]);

    if (!$response->successful()) {
        throw new \Exception('SMS yuborishda xato');
    }
}
```

---

### 2. Telegram Bot Integration

#### A. Bot Yaratish:

1. Telegram da [@BotFather](https://t.me/BotFather) ni toping
2. `/newbot` buyrug'ini yuboring
3. Bot nomini kiriting (masalan: OilControl Reminder Bot)
4. Username kiriting (masalan: oilcontrol_reminder_bot)
5. BotFather sizga **Bot Token** beradi

#### B. Konfiguratsiya:

`.env` fayliga qo'shing:
```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

`config/services.php`:
```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
],
```

#### C. Telegram Package O'rnatish:

```bash
composer require telegram-bot/api
```

#### D. ReminderService ni Yangilash:

```php
use TelegramBot\Api\BotApi;

protected function sendTelegram(string $telegramId, string $message): void
{
    $bot = new BotApi(config('services.telegram.bot_token'));

    try {
        $bot->sendMessage($telegramId, $message);
        \Log::info("Telegram yuborildi: {$telegramId}");
    } catch (\Exception $e) {
        throw new \Exception('Telegram yuborishda xato: ' . $e->getMessage());
    }
}
```

#### E. Telegram Webhook Controller (Ixtiyoriy - Foydalanuvchilar bot orqali ro'yxatdan o'tishi uchun):

```bash
php artisan make:controller TelegramWebhookController
```

`app/Http/Controllers/TelegramWebhookController.php`:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TelegramBot\Api\BotApi;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            if ($text === '/start') {
                $bot = new BotApi(config('services.telegram.bot_token'));
                $bot->sendMessage(
                    $chatId,
                    "Sizning Telegram ID: {$chatId}\n\n" .
                    "Bu ID ni ustaxonangizga bering, ular sizga eslatmalar yuborish uchun qo'shishadi."
                );
            }
        }

        return response()->json(['ok' => true]);
    }
}
```

`routes/web.php` ga qo'shing:
```php
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
```

Webhookni sozlash:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://yourdomain.com/telegram/webhook"
```

---

### 3. Laravel Scheduler ni Ishga Tushirish

**Windows (XAMPP/Laragon):**

Task Scheduler orqali:
1. Task Scheduler ni oching
2. "Create Basic Task" bosing
3. Nom: Laravel Scheduler
4. Trigger: Daily
5. Time: Har daqiqa (yoki 9:00 AM)
6. Action: Start a program
7. Program: `C:\xampp\php\php.exe`
8. Arguments: `C:\xampp\htdocs\oilcontrol\artisan schedule:run`

**Linux (Production Server):**

Crontab qo'shish:
```bash
crontab -e
```

Quyidagi qatorni qo'shing:
```
* * * * * cd /path/to/oilcontrol && php artisan schedule:run >> /dev/null 2>&1
```

---

### 4. Test Qilish

#### Qo'lda test:

```bash
# Eslatma yuborishni test qilish
php artisan reminders:send

# Schedulerni test qilish
php artisan schedule:run

# Ma'lum bir servis uchun eslatma yaratish
php artisan tinker
> $serviceLog = \App\Models\ServiceLog::first();
> app(\App\Services\ReminderService::class)->createRemindersForServiceLog($serviceLog);
```

#### Test mijoz yaratish:

1. Mijoz qo'shing (telefon: +998901234567, telegram_id: 123456789)
2. Avtomobil qo'shing
3. Servis yozuvi qo'shing (avg_monthly_km: 1000, next_service_km: 5000)
4. `php artisan reminders:send` buyrug'ini bajaring

---

### 5. Production Deployment

#### A. Queue Ishlatish (Tavsiya Etiladi):

`.env`:
```env
QUEUE_CONNECTION=database
```

Migration:
```bash
php artisan queue:table
php artisan migrate
```

ReminderService da queue ishlatish:
```php
use Illuminate\Support\Facades\Queue;

public function sendReminder(Reminder $reminder): bool
{
    Queue::push(new SendReminderJob($reminder));
    return true;
}
```

Queue Worker ishga tushirish:
```bash
php artisan queue:work
```

#### B. Log Monitoring:

`storage/logs/laravel.log` faylidagi loglarni kuzating

#### C. Error Handling:

Xatolarni email orqali yuborish (`.env`):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

---

## 📊 Database Ma'lumotlari

Eslatmalar `reminders` jadvalida saqlanadi:

- `status`: 'pending' (kutilmoqda), 'sent' (yuborildi), 'failed' (xato)
- `scheduled_date`: Qachon yuborish kerakligi
- `sent_at`: Qachon yuborilganligi
- `notification_type`: 'sms' yoki 'telegram'

---

## 🔍 Debugging

Agar eslatmalar yuborilmasa:

1. Scheduler ishlayaptimi: `php artisan schedule:list`
2. Reminderslar bormi: `SELECT * FROM reminders WHERE status = 'pending'`
3. Loglarni tekshiring: `tail -f storage/logs/laravel.log`
4. Qo'lda test qiling: `php artisan reminders:send`

---

## 💡 Qo'shimcha Xususiyatlar

### Eslatmalarni Boshqarish Sahifasi

Admin panel yarating:
- Barcha eslatmalarni ko'rish
- Eslatmani qayta yuborish
- Eslatma tarixini ko'rish
- Statistika (nechta yuborilgan, nechta xato)

### Mijoz Preferences

Mijozlar o'zlari tanlashi mumkin:
- SMS yoki Telegram (yoki ikkalasi)
- Qaysi vaqtda yuborilsin
- Necha kun oldin eslatma kerak

---

## ⚠️ Muhim Eslatmalar

1. **SMS narxi**: Har bir SMS uchun to'lov kerak, balansni kuzatib boring
2. **Telegram bot**: Foydalanuvchi avval botni start qilgan bo'lishi kerak
3. **Telefon format**: O'zbekiston formati: +998XXXXXXXXX
4. **Scheduler**: Production serverda cron job ishlashiga ishonch hosil qiling
5. **Queue**: Ko'p eslatma bo'lsa queue ishlatish tavsiya etiladi

---

## 🆘 Yordam

Muammo yuzaga kelsa:
- Laravel loglarni tekshiring
- API provider dokumentatsiyasini o'qing
- Telegram bot ishlayaptimi tekshiring: [@userinfobot](https://t.me/userinfobot)

---

**Omad! Savollaringiz bo'lsa so'rang! 🚀**
