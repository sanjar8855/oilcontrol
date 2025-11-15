# 🧪 OilControl - Test Qilish Qo'llanmasi

Bu qo'llanma loyihani to'liq test qilish uchun barcha kerakli qadamlarni o'z ichiga oladi.

---

## 📋 1-Qadam: Database Sozlash

### Windows (XAMPP/Laragon)

1. **MySQL ni ishga tushiring**:
   - XAMPP Control Panel → MySQL → Start
   - Yoki Laragon → Start All

2. **Database yarating**:
   ```bash
   # XAMPP Shell yoki CMD da
   mysql -u root

   # MySQL consoleda
   CREATE DATABASE oilcontrol;
   EXIT;
   ```

3. **.env faylini sozlang**:

   `.env` faylini oching va quyidagilarni o'zgartiring:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=oilcontrol
   DB_USERNAME=root
   DB_PASSWORD=
   ```

---

## 🚀 2-Qadam: Migratsiyalar va Seederlar

Loyiha papkasiga kiring va quyidagi buyruqlarni bajaring:

```bash
# 1. Migratsiyalarni bajaring (jadvallar yaratish)
php artisan migrate

# 2. Test ma'lumotlarni yaratish
php artisan db:seed
```

**Natija:**
```
✅ Test ma'lumotlar muvaffaqiyatli yaratildi!

📊 Yaratilgan ma'lumotlar:
   - Foydalanuvchi: 1 ta (admin@oilcontrol.uz)
   - Workshop: 1 ta (Avtomashina Servis Markazi)
   - Mijozlar: 4 ta
   - Avtomobillar: 5 ta
   - Servis yozuvlari: 5 ta
   - Eslatmalar: 4 ta

🔐 Login ma'lumotlari:
   Email: admin@oilcontrol.uz
   Parol: password
```

---

## 💻 3-Qadam: Loyihani Ishga Tushirish

### Terminal 1 - Backend (Laravel):
```bash
php artisan serve
```

**Natija:**
```
Starting Laravel development server: http://127.0.0.1:8000
```

### Terminal 2 - Frontend (Vite):
```bash
npm run dev
```

**Natija:**
```
VITE ready in 543 ms
➜  Local:   http://localhost:5173/
```

---

## 🔐 4-Qadam: Tizimga Kirish

### A. Brauzerda Ochish

URL: **http://127.0.0.1:8000** (yoki http://localhost:8000)

### B. Login Qilish

1. "Log in" tugmasini bosing
2. **Email:** `admin@oilcontrol.uz`
3. **Parol:** `password`
4. "Log in" tugmasini bosing

✅ Muvaffaqiyatli kirsangiz **Dashboard** sahifasiga o'tasiz!

---

## 📱 5-Qadam: Tizimni Sinab Ko'rish

### 🏠 Dashboard (Bosh Sahifa)

**URL:** http://127.0.0.1:8000/dashboard

**Ko'rishingiz mumkin:**
- 📊 Statistika kartochkalari:
  - Jami mijozlar: 4 ta
  - Obuna rejasi: Pro
  - Qolgan kunlar: 365 kun
  - Ustaxona nomi
- 👥 So'nggi mijozlar ro'yxati
- ➕ "Yangi Mijoz Qo'shish" tugmasi

---

### 👥 Mijozlar (Clients)

**URL:** http://127.0.0.1:8000/clients

#### A. Mijozlar Ro'yxati
Ko'rishingiz mumkin bo'lgan mijozlar:
1. **Abbos Karimov** - Telegram ID: 123456789, Avtomobil: 1 ta
2. **Dilshod Tursunov** - Telegram ID: 987654321, Avtomobil: 2 ta
3. **Nodira Rahimova** - Telegram ID yo'q, Avtomobil: 1 ta
4. **Sardor Aliyev** - Telegram ID: 555666777, Avtomobil: 1 ta

#### B. Mijozni Ko'rish (View)
1. Mijoz nomini bosing (masalan: "Abbos Karimov")
2. Ko'rishingiz mumkin:
   - Mijoz ma'lumotlari (ism, telefon, email, Telegram ID)
   - Avtomobillar ro'yxati
   - "Avtomobil Qo'shish" tugmasi
   - "Tahrirlash" va "O'chirish" tugmalari

#### C. Yangi Mijoz Qo'shish
1. "Yangi Mijoz Qo'shish" tugmasini bosing
2. Ma'lumotlarni kiriting:
   - **Mijoz ismi:** Jasur Yusupov
   - **Telefon:** +998905555555
   - **Telegram ID:** 111222333 (ixtiyoriy)
   - **Email:** jasur@gmail.com (ixtiyoriy)
   - **Eslatmalar:** Test mijoz (ixtiyoriy)
3. "Saqlash" tugmasini bosing
4. ✅ Mijoz ro'yxatiga qo'shildi!

#### D. Mijozni Tahrirlash
1. Mijoz sahifasida "Tahrirlash" tugmasini bosing
2. Ma'lumotlarni o'zgartiring
3. "Yangilash" tugmasini bosing

#### E. Mijozni O'chirish
1. Mijoz sahifasida "O'chirish" tugmasini bosing
2. Tasdiqlang
3. ✅ Mijoz o'chirildi!

---

### 🚗 Avtomobillar (Vehicles)

**URL:** http://127.0.0.1:8000/vehicles

#### A. Avtomobillar Ro'yxati
Ko'rishingiz mumkin:
1. Chevrolet Lacetti (2015) - Abbos Karimov
2. Daewoo Gentra (2018) - Dilshod Tursunov
3. Chevrolet Spark (2020) - Dilshod Tursunov
4. Kia Rio (2019) - Nodira Rahimova
5. Hyundai Accent (2021) - Sardor Aliyev

#### B. Avtomobilni Ko'rish
1. Avtomobil nomini bosing
2. Ko'rishingiz mumkin:
   - Avtomobil ma'lumotlari (marka, model, yil, davlat raqami, VIN)
   - Mijoz ma'lumotlari
   - Servis tarixi
   - "Servis Qo'shish" tugmasi

#### C. Yangi Avtomobil Qo'shish

**Variant 1: Mijoz sahifasidan**
1. Mijozni oching (masalan: Abbos Karimov)
2. "Avtomobil Qo'shish" tugmasini bosing
3. Mijoz avtomatik tanlangan bo'ladi

**Variant 2: Avtomobillar sahifasidan**
1. http://127.0.0.1:8000/vehicles
2. "Yangi Avtomobil Qo'shish" tugmasini bosing
3. Mijozni tanlang

**Ma'lumotlarni kiriting:**
- **Mijoz:** Abbos Karimov
- **Marka:** Toyota
- **Model:** Camry
- **Yil:** 2022
- **Davlat raqami:** 01 F 777 LM
- **VIN:** JTNK4RBE0N3123456

"Saqlash" tugmasini bosing.

---

### 🔧 Servis Yozuvlari (Service Logs)

**URL:** http://127.0.0.1:8000/service-logs

#### A. Servis Yozuvlari Ro'yxati
Ko'rishingiz mumkin:
- Barcha servis yozuvlari kartochka ko'rinishida
- Har bir kartochkada: mijoz, avtomobil, servis turi, probeg, narx

#### B. Servis Yozuvini Ko'rish
1. "Ko'rish" tugmasini bosing
2. To'liq ma'lumotlarni ko'rasiz:
   - Avtomobil va mijoz
   - Servis sanasi
   - Probeg (oxirgi va keyingi)
   - Servis turi va narxi
   - Eslatmalar (agar mavjud bo'lsa)

#### C. Yangi Servis Qo'shish

**Variant 1: Avtomobil sahifasidan (tavsiya etiladi)**
1. Avtomobilni oching (masalan: Lacetti)
2. "Servis Qo'shish" tugmasini bosing
3. Avtomobil avtomatik tanlangan

**Variant 2: Servis Yozuvlari sahifasidan**
1. http://127.0.0.1:8000/service-logs
2. "Yangi Servis Qo'shish" tugmasini bosing

**Ma'lumotlarni kiriting:**
- **Avtomobil:** Chevrolet Lacetti - Abbos Karimov
- **Servis sanasi:** 2025-11-15 (bugun)
- **Probeg:** 90000 km
- **Keyingi servis:** 5000 km (default)
- **O'rtacha oylik km:** 800 km
- **Servis turi:** Yog' almashtirish
- **Narx:** 450000 so'm
- **Eslatmalar:** Test servis

"Saqlash" tugmasini bosing.

**⚡ Muhim:** Servis qo'shilganda avtomatik 3 ta eslatma yaratiladi (30, 14, 7 kun oldin)!

---

## 🧪 6-Qadam: Eslatmalarni Test Qilish

### A. Pending Eslatmalarni Ko'rish

```bash
# Tinker ochish
php artisan tinker

# Pending eslatmalarni ko'rish
> \App\Models\Reminder::where('status', 'pending')->count()
# Natija: 3 (yoki 6 agar yangi servis qo'shgan bo'lsangiz)

# Bugungi eslatmalarni ko'rish
> \App\Models\Reminder::where('scheduled_date', '<=', now()->toDateString())
    ->where('status', 'pending')
    ->count()
# Natija: 2 (yoki ko'proq)

# Exit
> exit
```

### B. Eslatma Yuborishni Test Qilish (Qo'lda)

```bash
php artisan reminders:send
```

**Natija:**
```
Bugungi eslatmalarni tekshirish...
Jami 2 ta eslatma topildi.
Eslatma yuborilmoqda: Sardor Aliyev (+998904444444)...
✓ Muvaffaqiyatli yuborildi!
Eslatma yuborilmoqda: Dilshod Tursunov (+998902222222)...
✓ Muvaffaqiyatli yuborildi!

Yakunlandi! Yuborildi: 2, Xato: 0
```

**⚠️ Eslatma:** Haqiqatan yuborish uchun Telegram Bot sozlangan bo'lishi kerak (TELEGRAM_BOT_GUIDE.md ga qarang)

### C. Scheduler Test

```bash
# Schedulerni ko'rish
php artisan schedule:list

# Qo'lda ishga tushirish
php artisan schedule:run
```

---

## 📊 7-Qadam: Barcha Ma'lumotlarni Ko'rish

### Database orqali tekshirish:

```bash
# Tinker orqali
php artisan tinker

# Barcha mijozlar
> \App\Models\Client::count()
# Natija: 4 (yoki 5 agar yangi qo'shgan bo'lsangiz)

# Barcha avtomobillar
> \App\Models\Vehicle::count()
# Natija: 5 (yoki 6)

# Barcha servis yozuvlari
> \App\Models\ServiceLog::count()
# Natija: 5 (yoki 6)

# Barcha eslatmalar
> \App\Models\Reminder::count()
# Natija: 4 (yoki ko'proq)

# Yuborilgan eslatmalar
> \App\Models\Reminder::where('status', 'sent')->count()

# Pending eslatmalar
> \App\Models\Reminder::where('status', 'pending')->count()

# Exit
> exit
```

---

## 🎯 8-Qadam: To'liq Test Scenariosi

### Scenario: Yangi Mijoz va Servis Qo'shish

1. **Yangi mijoz qo'shish:**
   - Mijozlar → Yangi Mijoz Qo'shish
   - Ism: Aziz Sodiqov
   - Telefon: +998906666666
   - Telegram ID: 999888777
   - Saqlash

2. **Avtomobil qo'shish:**
   - Aziz Sodiqov sahifasini oching
   - "Avtomobil Qo'shish" tugmasini bosing
   - Marka: BMW
   - Model: X5
   - Yil: 2023
   - Davlat raqami: 01 G 999 NO
   - Saqlash

3. **Servis qo'shish:**
   - BMW X5 sahifasini oching
   - "Servis Qo'shish" tugmasini bosing
   - Servis sanasi: Bugun
   - Probeg: 15000 km
   - Keyingi servis: 5000 km
   - O'rtacha oylik: 1500 km
   - Servis turi: To'liq texnik ko'rik
   - Narx: 1200000
   - Eslatma: Birinchi servis
   - Saqlash

4. **Natijani tekshirish:**
   - Tinker: `\App\Models\Reminder::latest()->take(3)->get()`
   - 3 ta yangi eslatma yaratilgan bo'lishi kerak

5. **Telegram bot orqali test (agar bot sozlangan bo'lsa):**
   - Telegram da botga `/start` yuboring
   - Bot Telegram ID ni beradi
   - Mijozga bu ID ni qo'shing

---

## 🔍 9-Qadam: Loglarni Ko'rish

Agar xatolik yuz bersa yoki eslatma yuborilmasa:

```bash
# Real-time log
tail -f storage/logs/laravel.log

# Oxirgi 50 qator
tail -50 storage/logs/laravel.log

# Telegram bilan bog'liq loglar
grep "Telegram" storage/logs/laravel.log

# Reminder bilan bog'liq loglar
grep "Reminder" storage/logs/laravel.log
```

---

## ⚙️ 10-Qadam: Database Reset (Qayta Boshlash)

Agar hamma narsani tozalab qaytadan boshlashni xohlasangiz:

```bash
# Database ni tozalash va qayta yaratish
php artisan migrate:fresh --seed
```

**⚠️ Ogohantirish:** Bu barcha ma'lumotlarni o'chiradi va test ma'lumotlarni qayta yaratadi!

---

## 📖 Navigatsiya Xaritasi

```
http://127.0.0.1:8000
│
├── /login (Login sahifasi)
│   └── Email: admin@oilcontrol.uz
│   └── Parol: password
│
├── /dashboard (Bosh sahifa)
│   ├── Statistika
│   ├── So'nggi mijozlar
│   └── Yangi mijoz qo'shish
│
├── /clients (Mijozlar)
│   ├── /clients (Ro'yxat)
│   ├── /clients/create (Yangi qo'shish)
│   ├── /clients/{id} (Ko'rish)
│   └── /clients/{id}/edit (Tahrirlash)
│
├── /vehicles (Avtomobillar)
│   ├── /vehicles (Ro'yxat)
│   ├── /vehicles/create (Yangi qo'shish)
│   ├── /vehicles/{id} (Ko'rish)
│   └── /vehicles/{id}/edit (Tahrirlash)
│
├── /service-logs (Servis Yozuvlari)
│   ├── /service-logs (Ro'yxat)
│   ├── /service-logs/create (Yangi qo'shish)
│   └── /service-logs/{id} (Ko'rish)
│
└── /telegram (Telegram Bot - Admin)
    ├── /telegram/set-webhook (Webhook o'rnatish)
    ├── /telegram/webhook-info (Webhook status)
    ├── /telegram/delete-webhook (Webhook o'chirish)
    └── /telegram/bot-info (Bot ma'lumotlari)
```

---

## 💡 Qo'shimcha Buyruqlar

```bash
# Cache tozalash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optomizatsiya
php artisan optimize

# Database backup (MySQL)
mysqldump -u root oilcontrol > backup.sql

# Database restore
mysql -u root oilcontrol < backup.sql
```

---

## 🆘 Muammolar va Yechimlar

### 1. "Database not found" xatosi
```bash
# MySQL da database yarating
mysql -u root
CREATE DATABASE oilcontrol;
EXIT;
```

### 2. "SQLSTATE[HY000] [2002]" xatosi
- MySQL ishlamayapti, ishga tushiring
- XAMPP/Laragon da MySQL Start qiling

### 3. "Class 'App\Models\Workshop' not found"
```bash
php artisan optimize:clear
composer dump-autoload
```

### 4. Frontend komponentlar ko'rinmayapti
```bash
# Vite ni qayta ishga tushiring
npm run dev
```

### 5. "419 Page Expired" xatosi
- Sahifani refresh qiling (F5)
- Cache tozalang: `php artisan cache:clear`

---

## ✅ Test Checklist

- [ ] Database yaratildi
- [ ] Migratsiyalar bajarilingan
- [ ] Seederlar ishlatildi
- [ ] Backend server ishlayapti (php artisan serve)
- [ ] Frontend server ishlayapti (npm run dev)
- [ ] Login qilindi (admin@oilcontrol.uz / password)
- [ ] Dashboard ochildi
- [ ] Mijozlar ro'yxati ko'rildi
- [ ] Yangi mijoz qo'shildi
- [ ] Avtomobil qo'shildi
- [ ] Servis qo'shildi
- [ ] Eslatmalar yaratildi (Tinker orqali tekshirildi)
- [ ] `php artisan reminders:send` buyrug'i ishladi
- [ ] Loglar tekshirildi

---

## 🎉 Tayyor!

Barcha test scenariolarni o'tgan bo'lsangiz, OilControl tizimi to'liq ishlayotgan demakdir!

Keyingi qadam: **Telegram Bot sozlash** (TELEGRAM_BOT_GUIDE.md)

---

**Test qilishda omad! 🚀**
