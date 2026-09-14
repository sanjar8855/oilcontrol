# Telegram orqali ro'yxatdan o'tishni tasdiqlash + hisobotlar boti — dizayn

**Sana:** 2026-09-14
**Holat:** Tasdiqlangan, implementatsiya rejasiga o'tishga tayyor

## Maqsad

Yangi ro'yxatdan o'tgan ustaxona egalari (SaaS mijozlari) Telegram orqali
tasdiqlansin, va shu tasdiqlash jarayonida bog'langan Telegram chat kelajakda
tizim tomonidan yuboriladigan hisobot/ogohlantirish xabarlari uchun doimiy
kanal sifatida qolsin.

## Kontekst: mavjud Telegram infratuzilmasi

Loyihada allaqachon bitta Telegram bot ishlayapti (`TELEGRAM_BOT_TOKEN`, bot
ID `8525489608`, `App\Services\TelegramBotService`,
`App\Http\Controllers\TelegramWebhookController`, `POST /telegram/webhook`).
U ikki narsa uchun ishlatiladi:

1. Ustaxonalarning **o'z mijozlariga** (`Client` modeli) servis eslatma va
   kvitansiya yuborish (telefon raqami yoki deep-link token orqali bog'lash,
   mini-app).
2. `users.telegram_chat_id` ustuni (2026-08-11 dan mavjud) — hozir Profil
   sahifasida **qo'lda** kiritiladi va faqat
   `App\Console\Commands\SendSubscriptionExpiryWarnings` uchun ishlatiladi.

Bu spec **yangi, alohida bot** (`@oilcontrol_customers_bot`, bot ID
`8957400095`, token `.env`da `TELEGRAM_USERS_BOT_TOKEN`) qo'shadi — u faqat
SaaS mijozlari (ustaxona egalari) bilan ishlaydi va yuqoridagi (1)-band bilan
aralashmaydi. `users.telegram_chat_id` ustuni qayta ishlatiladi, lekin endi
qo'lda emas, shu yangi bot orqali avtomatik to'ldiriladi.

## A) Bot va webhook

- `.env`: `TELEGRAM_USERS_BOT_TOKEN`, `TELEGRAM_USERS_BOT_USERNAME`
  (`oilcontrol_customers_bot`), `TELEGRAM_USERS_BOT_WEBHOOK_SECRET` (32-baytli
  tasodifiy hex) — **allaqachon sozlangan**.
- `config/services.php`ga `telegram.users_bot_token`,
  `telegram.users_bot_username`, `telegram.users_bot_webhook_secret` qo'shiladi
  (mavjud `telegram.*` kalitlar bilan bir qatorda).
- Yangi `App\Services\UserTelegramBotService` — mavjud `TelegramBotService`dan
  mustaqil klass (u `Client` modeliga bog'liq, bu yerga mos emas). Mas'uliyati:
  - `/start <token>` ni qayta ishlash (deep-link orqali chat_id ni userga
    bog'lash + kod yuborish)
  - 6 xonali tasdiqlash kodini generatsiya qilib yuborish
  - Onboarding natijasi xabarini yuborish (E-band)
  - Obuna ogohlantirish xabarini yuborish (D-band)
- Yangi `App\Http\Controllers\UserTelegramWebhookController` +
  `POST /telegram/users-webhook` route. So'rov Telegramning
  `X-Telegram-Bot-Api-Secret-Token` headerini `TELEGRAM_USERS_BOT_WEBHOOK_SECRET`
  bilan solishtirib tekshiradi — mos kelmasa `403`.
- Yangi Artisan buyrug'i `php artisan telegram:set-users-webhook` — webhookni
  `setWebhook` API chaqiruvi orqali secret_token bilan birga o'rnatadi.

## B) Ma'lumotlar bazasi

- Yangi migratsiya: `users.telegram_verified_at` (nullable timestamp,
  `telegram_chat_id`dan keyin). **Mavjud userlar migratsiyada
  `telegram_verified_at = now()` bilan backfill qilinadi** — ular
  bloklanmaydi, faqat yangi ro'yxatdan o'tuvchilar tasdiqlashi shart bo'ladi.
- Yangi jadval `telegram_verifications`:
  - `id`, `user_id` (FK, `users.id`, cascade delete)
  - `link_token` (string, unique) — deep-link uchun bir martalik token
  - `code_hash` (string) — 6 xonali kod hash'i (plaintext saqlanmaydi)
  - `code_expires_at` (timestamp) — kod yaratilgandan 5 daqiqa keyin
  - `attempts` (unsigned tinyint, default 0) — noto'g'ri urinishlar soni
  - `last_sent_at` (timestamp, nullable) — resend throttle uchun
  - timestamps

`users.telegram_chat_id` ustuni **o'zgarishsiz qayta ishlatiladi** (yangi
migratsiya kerak emas — u allaqachon nullable+unique).

## C) Ro'yxatdan o'tish → tasdiqlash oqimi

1. Foydalanuvchi hozirgidek ism/telefon/parol bilan ro'yxatdan o'tadi →
   hisob (`User` + `Workshop`) darhol yaratiladi, `telegram_verified_at` bo'sh
   qoladi.
2. `EnsureTelegramIsVerified` middleware (Laravelning `verified` patterniga
   o'xshash) tasdiqlanmagan userni `/telegram/verify` sahifasiga yo'naltiradi
   — u yerdan tashqari hech qaysi `auth` sahifasiga kirolmaydi (logout bundan
   mustasno).
3. `/telegram/verify` sahifasi: agar `telegram_verifications` yozuvi bo'lmasa
   yoki eskirgan bo'lsa, yangi `link_token` generatsiya qilinadi va
   `https://t.me/oilcontrol_customers_bot?start=<link_token>` tugmasi
   ko'rsatiladi.
4. Foydalanuvchi botda "Start" bosadi → webhook `link_token` orqali
   `telegram_verifications` yozuvini topadi → shu userning
   `telegram_chat_id` ustunini yangilaydi (hali `telegram_verified_at`
   o'rnatilmaydi) → 6 xonali kod generatsiya qilib botdan yuboradi (5 daqiqa
   amal qiladi, `code_hash` sifatida saqlanadi).
5. Foydalanuvchi kodni saytdagi (`/telegram/verify`) maydonga kiritadi →
   `code_hash` bilan solishtiriladi:
   - To'g'ri va muddati o'tmagan bo'lsa: `telegram_verified_at = now()`,
     `telegram_verifications` yozuvi o'chiriladi, dashboardga yo'naltiriladi.
   - Noto'g'ri bo'lsa: `attempts++`; 5-urinishdan keyin kod bekor qilinadi,
     "yangi kod so'rang" xabari ko'rsatiladi.
6. "Kodni qayta yuborish" tugmasi — `last_sent_at`dan 60 soniya o'tmaguncha
   bosilmaydi (throttle).

Chat_id `/start`da (kod tasdiqlanishidan oldin) yozilishi qasddan — ikki
kanalli tasdiqlash: token faqat autentifikatsiyalangan sayt sessiyasi ichida
ko'rinadi, kod esa faqat o'sha chatga yuboriladi; ikkalasini ham bilish
(saytga kirish + o'sha Telegram chatni o'qiy olish) haqiqiy egalikni
tasdiqlaydi. `telegram_verified_at` kod tasdiqlangunga qadar bo'sh
qolganidan, dashboard shu vaqtgacha ochilmaydi.

## D) Obuna ogohlantirishini yangi botga ko'chirish

- `SendSubscriptionExpiryWarnings` buyrug'i endi `TelegramBotService` o'rniga
  `UserTelegramBotService`dan foydalanadi (`telegram_chat_id` manbasi
  o'zgarmaydi, faqat qaysi bot orqali yuborilishi o'zgaradi). Xabar faqat
  `telegram_verified_at` to'ldirilgan userlarga yuboriladi — `telegram_chat_id`
  bor-u, lekin tasdiqlash yakunlanmagan (masalan foydalanuvchi jarayonni
  yarim yo'lda tashlab ketgan) holatlar hisobga olinmaydi.
- `ProfileUpdateRequest`dagi qo'lda `telegram_chat_id` validatsiyasi va
  `UpdateProfileInformationForm.vue`dagi tahrirlanadigan matn maydoni olib
  tashlanadi. O'rniga profil sahifasida faqat holat ko'rsatiladi:
  - Tasdiqlangan bo'lsa: "✅ Telegram ulangan" (chat_id/username emas,
    faqat holat — maxfiylik uchun)
  - Tasdiqlanmagan bo'lsa: "Ulash" havolasi → `/telegram/verify` sahifasiga
    olib boradi (C-banddagi oqim qayta ishlatiladi)

## E) Onboarding 4-bosqich natijasini botga yuborish

`OnboardingController::finish()` metodida, `cleanup->purge()`dan **oldin**
(ma'lumotlar hali o'chirilmagan paytda), agar
`$user->telegram_verified_at` bo'lsa:

- `result()` metodidagi hisoblash logikasi (`saleTotal`, `profit`,
  `reminders` soni) umumiy private metodga (`resultSummary(Workshop $workshop)`)
  chiqariladi va ikkala metodda ham ishlatiladi.
- `UserTelegramBotService::sendOnboardingResult()` shu ma'lumotni qisqa
  formatlangan xabar sifatida `telegram_chat_id`ga yuboradi, matnda aniq
  ta'kidlanadi — bu **o'quv/namunaviy** natija ("Bu — sinov uchun yaratilgan
  namunaviy savdo. Kelajakda haqiqiy savdolaringiz bo'yicha xuddi shunday
  xabar shu yerga kelib turadi.").
- Xabar yuborilmasa ham (masalan Telegram API xatosi) `finish()` oqimi
  to'xtamaydi — best-effort, xato loglanadi va onboarding normal yakunlanadi.

## F) Xavfsizlik

- Webhook so'rovlari `X-Telegram-Bot-Api-Secret-Token` headeri bilan
  tekshiriladi (A-band).
- Kod qayta yuborish: 60 soniyada bir marta (`last_sent_at`).
- Noto'g'ri urinishlar: 5 tadan keyin kod bekor qilinadi, yangi kod talab
  qilinadi.
- Kod bazada faqat hash (`Hash::make`) sifatida saqlanadi, plaintext emas.
- Eski `telegram_verifications` yozuvlari **lazy** tozalanadi: fon vazifasi
  yo'q, foydalanuvchi `/telegram/verify` sahifasini keyingi safar ochganda
  (yoki "Qayta yuborish"ni bosganda) eskirgan/ishlatilgan yozuv yangisi bilan
  almashtiriladi (`updateOrCreate` bo'yicha `user_id`).

## Chegaradan tashqarida

Kelajakdagi to'liq kunlik hisobot/kuzatuv funksiyasi (aniq tarkib: savdo
statistikasi, ombor holati va h.k.) bu specga kirmaydi — faqat shu bot orqali
kelajakda yuborilishi mumkin bo'lgan **infratuzilma** (bot, chat_id bog'lanishi)
tayyorlanadi. Xabar formati va jadvali alohida loyiha sifatida keyinroq
brainstorming qilinadi.
