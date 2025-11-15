# OilControl - Avtomobil Servis Eslatma Tizimi (SaaS)

**OilControl** - moy almashtirish va avtomobil servis xizmatlarini ko'rsatuvchi ustaxonalar uchun mijozlarni avtomatik eslatish tizimi.

## Loyiha haqida

Bu loyiha ustaxonalarga mijozlar bazasini yuritish va har 5000 km yoki belgilangan vaqtda moy almashtirish kerakligini SMS/Telegram orqali avtomatik eslatib turish imkonini beradi.

### Asosiy funksiyalar

- ✅ Ustaxonalar ro'yxatdan o'tishi va o'z kabinetiga ega bo'lishi
- ✅ Mijozlar bazasini yaratish va boshqarish
- ✅ Avtomobillar ma'lumotlarini saqlash
- ✅ Servis tarixi (moy almashtirish) yozuvlari
- ✅ Avtomatik eslatmalar tizimi (SMS/Telegram)
- ✅ Obuna rejalar (free, start, pro, business)
- ✅ Dashboard va statistika

## Texnologiyalar

**Backend:**
- Laravel 12
- PHP 8.4
- PostgreSQL/MySQL

**Frontend:**
- InertiaJS
- Vue 3
- Tailwind CSS

**Mobile (keyingi bosqich):**
- Flutter

## O'rnatish

### 1. Repository ni klonlash

```bash
git clone <repository-url>
cd oilcontrol
```

### 2. Dependencylarni o'rnatish

```bash
composer install
npm install
```

### 3. Environment sozlash

`.env.example` faylini `.env` ga nusxalang:

```bash
cp .env.example .env
```

`.env` faylida database ma'lumotlarini to'ldiring:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oilcontrol
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Application key yaratish

```bash
php artisan key:generate
```

### 5. Database yaratish

MySQL/PostgreSQL da `oilcontrol` nomli database yarating:

**MySQL:**
```sql
CREATE DATABASE oilcontrol CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**PostgreSQL:**
```sql
CREATE DATABASE oilcontrol;
```

### 6. Migratsiyalarni ishga tushirish

```bash
php artisan migrate
```

### 7. Frontend assetslarni build qilish

**Development:**
```bash
npm run dev
```

**Production:**
```bash
npm run build
```

### 8. Loyihani ishga tushirish

```bash
php artisan serve
```

Brauzerda: `http://localhost:8000`

## Database strukturasi

### Jadvallar:

1. **users** - Foydalanuvchilar (ustaxona egasi)
2. **workshops** - Ustaxonalar
3. **clients** - Mijozlar
4. **vehicles** - Avtomobillar
5. **service_logs** - Servis yozuvlari (moy almashtirish tarixi)
6. **reminders** - Eslatmalar

### Munosabatlar (Relationships):

```
User → Workshop (1:1)
Workshop → Clients (1:Many)
Client → Vehicles (1:Many)
Vehicle → ServiceLogs (1:Many)
ServiceLog → Reminders (1:Many)
```

## Keyingi qadamlar

### Backend:
- [ ] Spatie Permission o'rnatish (rollar: admin, workshop_owner)
- [ ] Workshop CRUD
- [ ] Client CRUD
- [ ] Vehicle CRUD
- [ ] ServiceLog CRUD
- [ ] Reminder CRUD
- [ ] SMS integratsiya (Playmobile/Eskiz)
- [ ] Telegram Bot integratsiya
- [ ] Laravel Scheduler (cron job) sozlash
- [ ] Obuna va to'lovlar (Payme/Click)

### Frontend:
- [ ] Dashboard sahifasi
- [ ] Workshop boshqaruv paneli
- [ ] Mijozlar ro'yxati va CRUD
- [ ] Avtomobillar CRUD
- [ ] Servis yozuvlari CRUD
- [ ] Eslatmalar ro'yxati
- [ ] Statistika va grafiklar

### Mobile App (Flutter):
- [ ] API yaratish (Laravel Sanctum)
- [ ] Flutter loyihasi yaratish
- [ ] Login/Register
- [ ] Mijozlar CRUD
- [ ] Servis yozuvlari CRUD
- [ ] Push notification

## Litsenziya

MIT License

## Muallif

Sanjar - [GitHub](https://github.com/sanjar8855)
