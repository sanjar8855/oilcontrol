# Yangi foydalanuvchi uchun o'qitish (onboarding) rejimi — dizayn

**Sana:** 2026-09-09
**Holat:** Tasdiqlangan, implementatsiyaga tayyor

## Muammo

Yangi ro'yxatdan o'tgan tadbirkor (`RegisteredUserController::store`) darhol
to'liq dashboardga (`DashboardController::index`) tushib qoladi — bo'sh
statistika, bo'sh mahsulotlar ro'yxati, hech qanday yo'l-yo'riqsiz. Tizimning
asosiy ish oqimini (mahsulot qo'shish → mijoz/moshina qo'shish → savdo
qilish) birinchi marta o'zi bosib o'tmaguncha, tizim nima uchun kerakligini
tushunmaydi.

## Maqsad

Yangi ro'yxatdan o'tgan workshop uchun majburiy, ortiqcha elementlarsiz
3 bosqichli o'qitish siklini o'tkazish:

1. Umumiy katalogdan kamida 1 ta mahsulot tanlab, o'z inventariga qo'shish.
2. Mijoz + moshina qo'shish.
3. Shu moshinaning haqiqiy sahifasiga o'tib, "Savdo qilish" tugmasi orqali
   birinchi savdo/xizmat yozuvini yaratish.

Sikl to'liq bajarilmaguncha (yoki foydalanuvchi ochiq "Chiqish" tugmasini
bosmaguncha) dashboard va boshqa ish sahifalari ochilmaydi. Istalgan bosqichda
doim ko'rinadigan "O'qitish rejimidan chiqish" tugmasi bosilsa, sikl darhol
yakunlangan deb belgilanadi va dashboard ochiladi.

## Doirasi tashqarisida (Out of scope)

- Hozirgi mavjud (bugungi sanadan oldin ro'yxatdan o'tgan) workshoplar —
  ularga onboarding hech qachon ko'rsatilmaydi (backfill orqali darhol
  "tugagan" deb belgilanadi).
- Onboarding qadamlarini administrator tomonidan sozlash/o'chirish UI'si.
- Mobil ilova yoki Telegram bot oqimi — faqat web (Inertia) qamrab olinadi.

## 1. Ma'lumotlar bazasi sxemasi

`workshops` jadvaliga migratsiya bilan 2 ta ustun qo'shiladi:

| ustun | tur | izoh |
|---|---|---|
| `onboarding_step` | string, nullable | `products` \| `vehicle` \| `sale` \| `null` |
| `onboarding_completed_at` | timestamp, nullable | sikl qachon tugagani (tabiiy yakun yoki "chiqish" orqali) |

Bir xil migratsiya ichida backfill: mavjud barcha qatorlar uchun
`onboarding_step = null`, `onboarding_completed_at = created_at`. Hech qanday
mavjud ustun yoki qator o'chirilmaydi/o'zgartirilmaydi — faqat 2 ta yangi
ustun qo'shiladi va ular darhol to'ldiriladi.

### Model o'zgarishlari

`Workshop::fillable`ga `onboarding_step`, `onboarding_completed_at`
qo'shiladi. Yordamchi metodlar:

```php
public function isOnboarding(): bool
{
    return $this->onboarding_step !== null;
}

public function advanceOnboarding(string $nextStep): void
{
    $this->update([
        'onboarding_step' => $nextStep,
        'onboarding_completed_at' => $nextStep === null ? now() : null,
    ]);
}
```

### Ro'yxatdan o'tish oqimi

`RegisteredUserController::store` — yangi workshop yaratilganda
`onboarding_step` boshlang'ich qiymati `'products'` qilib qo'yiladi (faqat
shu joyda, mavjud oqimga boshqa hech narsa qo'shilmaydi).

## 2. Server-side gating (middleware)

Yangi `App\Http\Middleware\EnsureOnboardingComplete`, `EnsureActiveWorkshop`
naqshi asosida:

- `bootstrap/app.php`dagi `web` stackiga `EnsureActiveWorkshop`dan keyin
  qo'shiladi.
- Login qilingan va workshop mavjud bo'lsa: `workshop->isOnboarding()`
  bo'lsa, quyidagi istisnolardan tashqari **barcha** so'rovlarni joriy
  `onboarding_step`ga mos onboarding sahifasiga (yoki, `sale` bosqichida,
  onboardingda yaratilgan bitta moshinaning sahifasiga) redirect qiladi:
  - `onboarding.*` marshrutlari (wizard sahifalari + skip)
  - `logout`
  - `vehicles.show` — **faqat** shu workshop uchun onboardingda yaratilgan
    moshina id'si bilan (boshqa moshinalar sahifasi berilmaydi, chunki hali
    mavjud emas)
  - `service-logs.store`
- `onboarding_step === null` bo'lsa, middleware hech narsa qilmaydi.

Bosqichlar orasida orqaga qaytishga ruxsat yo'q: `onboarding_step = 'vehicle'`
bo'lgan foydalanuvchi `/onboarding/products`ga kirmoqchi bo'lsa, joriy
qadamiga qayta yo'naltiriladi.

## 3. Marshrutlar va kontroller

Yangi `App\Http\Controllers\OnboardingController`, guruh prefiksi
`/onboarding`, nomlangan marshrutlar `onboarding.*`:

| Metod | Marshrut | Vazifa |
|---|---|---|
| GET | `/onboarding/products` | Katalog ro'yxatini ko'rsatish |
| POST | `/onboarding/products` | Tanlangan mahsulotlarni nusxalash, qadamni `vehicle`ga o'tkazish |
| GET | `/onboarding/vehicle` | Minimal mijoz+moshina forma |
| POST | `/onboarding/vehicle` | Mijoz+moshina yaratish, qadamni `sale`ga o'tkazish, yaratilgan moshina sahifasiga redirect |
| POST | `/onboarding/skip` | Istalgan bosqichdan darhol yakunlash, dashboardga redirect |

### Mavjud kodni qayta ishlatish

- `ProductController::catalog()` ichidagi global katalog ro'yxatini olish
  logikasi `GlobalProductMatcher::listAvailableFor(Workshop $workshop)`
  metodiga chiqariladi (bu servis global-katalog moslashtirish mantiqini
  allaqachon saqlab turibdi, shuning uchun ro'yxat olish logikasi ham shu
  yerga tabiiy joylashadi) — `ProductController::catalog()` ham,
  `OnboardingController::products()` ham shu metoddan foydalanadi.
  `copy-from-catalog` submit logikasi ham xuddi shu servisdagi umumiy
  metodga chiqarilib, ikkala controller'dan chaqiriladi.
- `ClientController::storeWithVehicle`dagi mijoz+moshina yaratish logikasi
  xuddi shunday umumiy metodga chiqariladi, `OnboardingController::vehicle()`
  undan foydalanadi.
- Har ikkala holatda ham **controller endpointlari o'zgarmaydi** — faqat
  ichidagi biznes-mantiq umumiy metodga chiqarilib, ikki joydan chaqiriladi.

## 4. Frontend

Yangi `resources/js/Layouts/OnboardingLayout.vue` — `GuestLayout.vue`ga
o'xshash, minimal: logo, "N/3" progress-indikator, pastda doim ko'rinadigan
"O'qitish rejimidan chiqish" havolasi (`OnboardingExitLink.vue` komponenti,
`onboarding.skip`ga POST qiladi). Sidebar, header menyu va boshqa dashboard
elementlari yo'q.

- `resources/js/Pages/Onboarding/Products.vue` — checkbox ro'yxat (nom,
  birlik, kategoriya), "Davom etish" tugmasi kamida 1 ta belgilanmaguncha
  o'chirilgan.
- `resources/js/Pages/Onboarding/Vehicle.vue` — minimal maydonlar: mijoz
  ismi, telefon, moshina davlat raqami, moshina modeli (to'liq
  Client/Vehicle formalaridagi ixtiyoriy maydonlar yo'q).
- `resources/js/Pages/Vehicles/Show.vue` — backenddan keladigan
  `isOnboardingHighlight` propi `true` bo'lsa (workshop `sale` bosqichida va
  bu aynan o'sha moshina bo'lsa), mavjud "🛒 Savdo qilish" tugmasi atrofida
  CSS pulse/halo effekti va qisqa ko'rsatma matni ko'rsatiladi, shu bilan
  birga sahifada `OnboardingExitLink` ham chiqadi. Sahifaning qolgan qismi
  (chrome, layout) **o'zgarishsiz** — bu haqiqiy ish sahifasi, wizard emas.

## 5. Sikl yakuni

`ServiceLogController::store`ga bitta shart qo'shiladi: saqlash
muvaffaqiyatli bo'lgach, agar joriy workshop `onboarding_step === 'sale'`
bo'lsa, `advanceOnboarding(null)` chaqiriladi. Frontenddan hech qanday
qo'shimcha "onboarding" parametri yuborilmaydi — holat butunlay server
tomonidan workshop yozuvidan aniqlanadi, shuning uchun soxtalashtirib
bo'lmaydi.

## 6. Chegaraviy holatlar

- **Xodimlar**: workshop egasi onboardingni tugatmasdan xodim taklif qilsa,
  xodim ham tizimga kirganda onboarding oqimini ko'radi — holat foydalanuvchi
  emas, workshop darajasida saqlanadi. Bu maqsadga muvofiq (do'kon hali
  sozlanmagan).
- **Bo'sh global katalog**: agar katalogda hech narsa bo'lmasa, "Davom
  etish" baribir ko'rsatiladi va ogohlantirish chiqadi — foydalanuvchi
  "Chiqish" orqali chiqib ketishi mumkin, sikl uni qamab qo'ymaydi.
- **Migratsiya xavfsizligi**: yangi ustunlar `nullable`, mavjud qatorlar
  bir xil migratsiya ichida backfill qilinadi — hech narsa o'chirilmaydi.

## Testlash rejasi (Feature testlar)

1. Yangi workshop ro'yxatdan o'tgach, `/dashboard`ga kirish urinishi
   `/onboarding/products`ga redirect qilishini tekshiradi.
2. Kamida 1 mahsulot tanlab yuborilganda, mahsulot workshop inventariga
   qo'shilishini va `onboarding_step`ning `vehicle`ga o'tishini tekshiradi.
3. Mijoz+moshina yaratilgach `onboarding_step`ning `sale`ga o'tishini va
   redirect yaratilgan moshina sahifasiga ekanini tekshiradi.
4. `service-logs.store` muvaffaqiyatli bo'lgach `onboarding_completed_at`
   to'lishini va endi `/dashboard` ochilishini tekshiradi.
5. `/onboarding/skip` istalgan bosqichdan darhol yakunlashini tekshiradi.
6. Migratsiyadan oldin mavjud bo'lgan (eski) workshop hech qachon
   onboardingga redirect qilinmasligini tekshiradi.
7. Bosqichlar orasida oldinga o'tib bo'lmasligini (masalan `vehicle`
   bosqichida `/onboarding/products`ga kirsa joriy qadamga qaytarilishini)
   tekshiradi.

## Keyingi qadam

Ushbu spec asosida `writing-plans` ko'nikmasi orqali batafsil implementatsiya
rejasi tuziladi.
