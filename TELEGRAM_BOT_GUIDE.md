# 🤖 Telegram Bot O'rnatish Qo'llanmasi

OilControl loyihangiz uchun Telegram Bot orqali avtomatik servis eslatmalari yuborish tizimini to'liq sozlash qo'llanmasi.

---

## ✅ Tayyor Bo'lgan Funksiyalar

- ✅ **TelegramBotService** - Bot bilan ishlash uchun to'liq service
- ✅ **TelegramWebhookController** - Webhook orqali xabarlarni qabul qilish
- ✅ **ReminderService** - Faqat Telegram orqali eslatma yuborish
- ✅ **Avtomatik eslatmalar** - 30, 14, 7 kun oldin
- ✅ **Laravel Scheduler** - Har kuni soat 9:00 da avtomatik tekshirish

---

## 📋 1-Qadam: Telegram Bot Yaratish

### A. BotFather da Yangi Bot Yaratish

1. Telegram ilovasini oching
2. [@BotFather](https://t.me/BotFather) ni qidiring va `/start` bosing
3. `/newbot` buyrug'ini yuboring
4. **Bot nomini kiriting**:
   ```
   OilControl Reminder Bot
   ```
5. **Bot username kiriting** (oxirida `_bot` bo'lishi kerak):
   ```
   oilcontrol_reminder_bot
   ```
   Yoki o'zingiz yoqtirgan nom:
   ```
   avtomashina_xizmat_bot
   ```

6. BotFather sizga **Bot Token** beradi. Masalan:
   ```
   1234567890:ABCdefGHIjklMNOpqrsTUVwxyz123456789
   ```

   **⚠️ Bu Tokenni hech kimga bermang! Maxfiy saqlang!**

---

## 🔧 2-Qadam: Loyihaga Sozlash

### A. .env Faylini Yangilash

`.env` faylingizni oching va quyidagilarni qo'shing:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz123456789
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

**Eslatma:**
- `TELEGRAM_BOT_TOKEN` - BotFather bergan token
- `TELEGRAM_WEBHOOK_URL` - Sizning domen + `/telegram/webhook`
- Local development uchun ngrok ishlatishingiz mumkin (quyida ko'rsatilgan)

---

## 🌐 3-Qadam: Webhookni O'rnatish

### Variant 1: Production Serverda (Domen bor)

Domeningiz tayyor bo'lsa (masalan: `https://oilcontrol.uz`):

1. Brauzerda ochib webhook o'rnating:
   ```
   https://oilcontrol.uz/telegram/set-webhook
   ```

2. Javob:
   ```json
   {
     "ok": true,
     "result": true,
     "description": "Webhook was set"
   }
   ```

3. Tekshirish:
   ```
   https://oilcontrol.uz/telegram/webhook-info
   ```

### Variant 2: Local Development (ngrok bilan)

Local serverda test qilish uchun ngrok ishlatamiz:

1. **ngrok o'rnating**: [ngrok.com](https://ngrok.com) dan yuklab oling

2. **ngrok ishga tushiring**:
   ```bash
   ngrok http 8000
   ```

   Natija:
   ```
   Forwarding https://abc123.ngrok.io -> http://localhost:8000
   ```

3. **.env ni yangilang**:
   ```env
   TELEGRAM_WEBHOOK_URL=https://abc123.ngrok.io/telegram/webhook
   ```

4. **Webhook o'rnating**:
   ```
   https://abc123.ngrok.io/telegram/set-webhook
   ```

**⚠️ Eslatma:** ngrok har safar yangi URL beradi. Har safar webhookni qayta o'rnatishingiz kerak!

---

## 🧪 4-Qadam: Test Qilish

### A. Botni Sinab Ko'ring

1. Telegram da botingizni toping: `@oilcontrol_reminder_bot`

2. `/start` bosing

3. Bot javob berishi kerak — telefon raqamingizni so'raydi va "📱 Telefon raqamni yuborish" tugmasini chiqaradi:
   ```
   🚗 OilControl Bot'ga xush kelibsiz!

   Assalomu alaykum, [Ismingiz]!

   Servis eslatmalarini olish uchun ustaxonaga bergan telefon
   raqamingizni yuboring.

   📱 Pastdagi tugmani bosing yoki raqamni qo'lda yozing
   (masalan: +998901234567).
   ```

4. Tugmani bosing (yoki raqamni qo'lda yozing). Agar raqam tizimda mavjud mijozning raqami bilan mos kelsa, bot avtomatik bog'laydi:
   ```
   ✅ Muvaffaqiyatli bog'landi, [Ismingiz]!

   • Abbos Karimov (OilControl Servis)

   🔔 Endi servis eslatmalarini shu yerda olasiz.
   ```

   Bitta Telegram akkaunt bir nechta mijoz yozuviga (masalan, boshqa telefon raqamiga yoki boshqa ustaxonadagi hisobga) ham bog'lanishi mumkin — buning uchun keyingi raqamni ham botga yuborish kifoya.

5. Boshqa buyruqlar:
   - `/help` - Yordam
   - `/myid` - Telegram ID va shu akkauntga bog'langan mijozlar ro'yxatini ko'rsatadi

### B. Mijoz Qo'shish va Test

1. **Loyihaga kiring** va yangi mijoz qo'shing:
   - Ism: Abbos Karimov
   - Telefon: +998901234567 (mijoz botga xuddi shu raqamni yuboradi)

   **Eslatma:** Telegram ID ni qo'lda kiritish shart emas — mijoz botga o'z telefon raqamini yuborganda tizim uni avtomatik topib bog'laydi.

2. **Avtomobil qo'shing**:
   - Marka: Chevrolet
   - Model: Lacetti
   - Yil: 2020

3. **Servis qo'shing**:
   - Servis sanasi: Bugun
   - Probeg: 50,000 km
   - Keyingi servis: 5,000 km
   - O'rtacha oylik km: 1,000 km
   - Servis turi: Yog' almashtirish

4. **Mijozga botda `/start` bosdiring va telefon raqamini yubordiring** (yuqoridagi 4-qadamga qarang).

5. **Eslatma yuborishni test qiling**:
   ```bash
   php artisan reminders:send
   ```

6. Agar mijoz raqami orqali botga bog'langan bo'lsa, bot eslatma yuboradi!

---

## ⚙️ 5-Qadam: Schedulerni Sozlash

### Windows (XAMPP/Laragon)

#### Task Scheduler orqali:

1. **Task Scheduler**ni oching (Windows qidiruv orqali)

2. **"Create Basic Task"** bosing

3. **Task ma'lumotlari**:
   - Name: `Laravel Scheduler - OilControl`
   - Description: `Run Laravel scheduler every minute`

4. **Trigger**: `Daily` tanlab, soatni `00:00` qiling

5. **Action**: `Start a program`

6. **Program/script**:
   ```
   C:\xampp\php\php.exe
   ```

7. **Add arguments**:
   ```
   C:\xampp\htdocs\oilcontrol\artisan schedule:run
   ```

8. **Settings**da:
   - ✅ "Run task as soon as possible after a scheduled start is missed"
   - ✅ "Stop the task if it runs longer than: 1 hour"

9. **Advanced**:
   - "Repeat task every: **1 minute**"
   - "for a duration of: **Indefinitely**"

### Linux (Production Server)

Crontab sozlash:

```bash
crontab -e
```

Quyidagini qo'shing:

```
* * * * * cd /var/www/oilcontrol && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🎨 6-Qadam: Botni Sozlash (Ixtiyoriy)

### Bot Rasmini O'rnatish

1. BotFather ga `/setuserpic` yuboring
2. Botingizni tanlang
3. Rasm yuklang (avtomobil yoki logo)

### Bot Tavsifini O'rnatish

1. BotFather ga `/setdescription` yuboring
2. Botingizni tanlang
3. Tavsif yozing:
   ```
   Avtomobil servis eslatma boti. Mashinangiz servisga muhtoj
   bo'lganida avtomatik eslatma yuboradi. 🚗🔔
   ```

### Bot Buyruqlarini O'rnatish

1. BotFather ga `/setcommands` yuboring
2. Botingizni tanlang
3. Quyidagilarni yuboring:
   ```
   start - Botni boshlash va telefon raqamni bog'lash
   help - Yordam
   myid - Bog'langan mijozlarni va Telegram ID ni ko'rish
   ```

---

## 📊 7-Qadam: Monitoring va Debugging

### Loglarni Tekshirish

Laravel loglar:
```bash
tail -f storage/logs/laravel.log
```

Telegram yuborilgan xabarlar:
```bash
grep "Telegram" storage/logs/laravel.log
```

### Webhook Statusini Tekshirish

Brauzerda:
```
https://yourdomain.com/telegram/webhook-info
```

Yoki:
```bash
curl https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo
```

### Bot Ishlayaptimi Tekshirish

```
https://yourdomain.com/telegram/bot-info
```

---

## 🔍 Muammolarni Hal Qilish

### 1. Bot javob bermayapti

**Tekshiring:**
- Webhook to'g'ri o'rnatilganmi? `/telegram/webhook-info`
- Token to'g'rimi? `.env` faylida
- Server ishlab turganmi?
- ngrok yangi URL berganmi? (local da)

**Hal qilish:**
```bash
# Webhookni qayta o'rnating
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/telegram/webhook"

# Yoki brauzerda
https://yourdomain.com/telegram/set-webhook
```

### 2. Eslatmalar yuborilmayapti

**Tekshiring:**
```bash
# Pending eslatmalar bormi?
php artisan tinker
> \App\Models\Reminder::where('status', 'pending')->count()

# Qo'lda yuborish
php artisan reminders:send
```

**Sabablari:**
- Mijoz hali botga telefon raqamini yubormagan (Telegram ID yo'q)
- Mijozning tizimdagi telefon raqami botga yuborgan raqamdan farq qiladi
- Bot token noto'g'ri
- Schedulerni ishlamayapti

### 3. Scheduler ishlamayapti

**Tekshirish:**
```bash
php artisan schedule:list
```

**Windows da:**
- Task Scheduler ishlab turganini tekshiring
- Task History ni ko'ring

**Linux da:**
```bash
# Crontab mavjudmi?
crontab -l

# Cron service ishlab turganmi?
systemctl status cron
```

### 4. Mijoz botga bog'lanmayapti

**Hal qilish:**
- Mijoz botga `/start` yuborsin va telefon raqamini jo'natsin (tugma orqali yoki qo'lda yozib)
- Bot yubotgan raqamni tizimdagi mijozlar bilan oxirgi 9 raqami bo'yicha solishtiradi (prefiks: `+998`, `998`, `0` — farqi yo'q)
- Agar "raqam topilmadi" degan javob kelsa, mijozning tizimdagi telefon raqami (`Mijozlar` bo'limida) botga yuborilgan raqam bilan mos emas — to'g'irlang
- Muvaffaqiyatli bog'langanda mijoz `/myid` orqali o'ziga bog'langan barcha mijoz yozuvlarini ko'rishi mumkin

---

## 💡 Qo'shimcha Xususiyatlar

### Eslatmalarni Boshqarish Sahifasi (Kelajakda)

Admin panel:
- Barcha eslatmalarni ko'rish
- Eslatmani qayta yuborish
- Statistika

### Mijoz Sozlamalari (Kelajakda)

Mijozlar o'zlari:
- Qaysi vaqtda eslatma olishni tanlash
- Necha kun oldin eslatma kerakligini belgilash
- Eslatmalarni o'chirish/yoqish

---

## 🎯 Asosiy Buyruqlar (Eslab Qolish Uchun)

### Development:

```bash
# Loyihani ishga tushirish
php artisan serve
npm run dev

# ngrok ishga tushirish
ngrok http 8000

# Webhook o'rnatish
# Brauzer: http://localhost:8000/telegram/set-webhook

# Test yuborish
php artisan reminders:send

# Schedulerni test qilish
php artisan schedule:run
```

### Production:

```bash
# Webhook o'rnatish
https://yourdomain.com/telegram/set-webhook

# Webhook tekshirish
https://yourdomain.com/telegram/webhook-info

# Crontab sozlash
crontab -e
# Qo'shing: * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🆘 Yordam Kerakmi?

Muammo yuzaga kelsa:

1. **Loglarni tekshiring**: `storage/logs/laravel.log`
2. **Webhook statusni ko'ring**: `/telegram/webhook-info`
3. **Qo'lda test qiling**: `php artisan reminders:send`
4. **Telegram API hujjatlarni o'qing**: [core.telegram.org/bots/api](https://core.telegram.org/bots/api)

---

## ✅ Tayyor!

Endi sizning OilControl loyihangiz to'liq ishlaydi:

1. ✅ Mijozlar botga `/start` bosib, telefon raqamlarini yuborishadi
2. ✅ Tizim raqamni avtomatik topib, Telegram akkauntni mijoz yozuviga bog'laydi (qo'lda hech narsa kiritish shart emas)
3. ✅ Servis qo'shilganda avtomatik 3 ta eslatma yaratiladi
4. ✅ Har kuni soat 9:00 da Scheduler eslatmalarni yuboradi
5. ✅ Mijozlar Telegram orqali eslatma olishadi

**Omad! 🚀**

---

**Versiya:** 1.0
**Sana:** 2025-11-15
**Muallif:** OilControl Development Team
