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

3. Bot javob berishi kerak:
   ```
   🚗 OilControl Bot'ga xush kelibsiz!

   Assalomu alaykum, [Ismingiz]!

   📱 Sizning Telegram ID:
   1234567890

   📋 Bu ID ni nima qilish kerak?
   Bu ID raqamini avtomobil servisiga bering...
   ```

4. Boshqa buyruqlar:
   - `/help` - Yordam
   - `/myid` - Faqat Telegram ID ni ko'rsatadi

### B. Mijoz Qo'shish va Test

1. **Loyihaga kiring** va yangi mijoz qo'shing:
   - Ism: Abbos Karimov
   - Telefon: +998901234567
   - **Telegram ID: 1234567890** (botdan olgan ID)

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

4. **Eslatma yuborishni test qiling**:
   ```bash
   php artisan reminders:send
   ```

5. Agar mijozning Telegram ID to'g'ri bo'lsa, bot eslatma yuboradi!

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
   start - Botni boshlash va Telegram ID olish
   help - Yordam
   myid - Telegram ID ni ko'rish
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
- Mijozda Telegram ID yo'q
- Telegram ID noto'g'ri
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

### 4. Telegram ID topilmayapti

**Hal qilish:**
- Botga `/start` yuboring
- Bot sizga Telegram ID ni ko'rsatadi
- `<code>1234567890</code>` ko'rinishida
- Bu ID ni mijozga qo'shing

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

1. ✅ Mijozlar o'z Telegram ID larini botdan olishadi
2. ✅ Siz mijozlarga Telegram ID ni qo'shasiz
3. ✅ Servis qo'shilganda avtomatik 3 ta eslatma yaratiladi
4. ✅ Har kuni soat 9:00 da Scheduler eslatmalarni yuboradi
5. ✅ Mijozlar Telegram orqali eslatma olishadi

**Omad! 🚀**

---

**Versiya:** 1.0
**Sana:** 2025-11-15
**Muallif:** OilControl Development Team
