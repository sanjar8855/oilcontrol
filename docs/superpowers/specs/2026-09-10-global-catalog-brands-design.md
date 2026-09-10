# Global katalogda brend (ishlab chiqaruvchi) tushunchasi — dizayn

**Sana:** 2026-09-10
**Holat:** Tasdiqlangan, implementatsiyaga tayyor

## Muammo

Hozirgi holatda mahsulotlar (Lukoil, Valvoline, ZIC kabi) ishlab chiqaruvchi
brendiga bog'lanmagan — bunday tushuncha umuman yo'q. Global katalog sahifasi
(`GlobalProductController::index`, superadmin uchun) va workshop'ning
"katalogdan tanlash" sahifasi (`ProductController::catalog`) allaqachon
kategoriya bo'yicha filtrlash imkoniyatiga ega, lekin brend bo'yicha
filtrlash yo'q — bu ikkala sahifada ham topishni qiyinlashtiradi.

## Maqsad

1. Mahsulotlarni (global katalog darajasida) brendga bog'lash imkoniyati.
2. Global katalog va workshop'ning tanlash sahifasida brend bo'yicha
   filtrlash — kategoriya filtri bilan bir xil qulaylikda.

## Doirasi tashqarisida (Out of scope)

- Workshop darajasidagi mustaqil brend (faqat global katalog darajasi).
- Brendlarni boshqarish uchun alohida ro'yxat/tahrirlash/o'chirish sahifasi —
  faqat mahsulot formasidan "tez qo'shish" orqali yaratiladi (kategoriya
  qanday ishlasa, deyarli shunga o'xshab, faqat erkin matn emas, tayyor
  ro'yxatdan tanlash orqali).
- Mavjud 40 ta mahsulotni avtomatik brendga bog'lash — superadmin har birini
  Global mahsulot tahrirlash sahifasida qo'lda belgilaydi.
- Onboarding (o'qitish rejimi) mahsulot tanlash qadamiga brend filtri
  qo'shilmaydi — u ataylab minimal qurilgan.

## 1. Ma'lumotlar bazasi sxemasi

Yangi jadval **`brands`**:

| ustun | tur | izoh |
|---|---|---|
| `id` | bigint | |
| `name` | string, unique | |
| `is_active` | boolean, default true | |
| `timestamps` | | |

`global_products` jadvaliga yangi ustun: `brand_id` (nullable FK → `brands`,
`nullOnDelete`).

### Model o'zgarishlari

- Yangi `App\Models\Brand` — `fillable: name, is_active`, `globalProducts(): HasMany`.
- `GlobalProduct::brand(): BelongsTo` — yangi.
- `GlobalProduct::$fillable`ga `brand_id` qo'shiladi.

## 2. Brend yaratish va biriktirish

Yangi kichik `App\Http\Controllers\BrandController::store(Request $request)`:

- Validatsiya: `name => required|string|max:255`.
- Nom normallashtirilib (`trim`, case-insensitive) mavjud brend qidiriladi
  (`Brand::whereRaw('LOWER(TRIM(name)) = ?', [...])`) — topilsa o'shani,
  topilmasa yangisini yaratib, JSON sifatida (`id`, `name`) qaytaradi.
- Marshrut: `POST /brands`, `can:global-products.manage` gate ostida
  (yangi ruxsat kerak emas).

`GlobalProducts/Create.vue` va `Edit.vue`:

- "Brend" dropdown maydoni (mavjud brendlar ro'yxatidan tanlash).
- Dropdown ostida "+ Yangi brend qo'shish" havolasi — bosilganda kichik matn
  maydoni ochiladi, `POST /brands` chaqiriladi, natija darhol dropdown
  ro'yxatiga qo'shilib avtomatik tanlanadi (sahifa qayta yuklanmaydi).

`GlobalProductController::store()`/`update()` validatsiyasiga
`brand_id => nullable|exists:brands,id` qo'shiladi.

## 3. Brend bo'yicha filtrlash

`GlobalProductMatcher::catalogQuery()` imzosi kengaytiriladi:

```php
public function catalogQuery(?string $search, ?int $categoryId = null, ?int $brandId = null): Builder
```

Ichida `if ($brandId) { $query->where('brand_id', $brandId); }`.

Bu metod hozir `ProductController::catalog()` va `OnboardingController::products()`
tomonidan ishlatiladi — shu bitta joyga qo'shilgan filtr ikkalasida ham
ishlaydi (Onboarding'da UI qo'shilmaydi, lekin metod imzosi barchasi uchun
umumiy bo'lib qoladi).

`GlobalProductController::index()` hozirgi qo'lda yozilgan
`where('global_category_id', ...)` so'rovi o'rniga shu umumiy
`catalogQuery()` metodidan foydalanadigan qilib moslashtiriladi, brend
filtri ham qo'shiladi.

Frontend: `GlobalProducts/Index.vue` va `Products/Catalog.vue` — mavjud
"Kategoriya" dropdowni yonida "Brend" dropdowni, xuddi shu naqsh
(`filters.brand_id`, debounce'siz, `router.get()` orqali darhol). Har bir
mahsulot qatorida brend nomi kategoriya belgisi yonida kichik chip sifatida
ko'rsatiladi.

## 4. Chegaraviy holatlar

- Katta-kichik harf/bo'sh joy farqi bilan takroriy brend nomi —
  `BrandController::store()`dagi normallashtirish orqali oldini olinadi.
- Brend keyinchalik o'chirilsa — `nullOnDelete`, mahsulotlar xatosiz qoladi.
- Migratsiya to'liq additive — mavjud hech narsa o'zgarmaydi.
- Yangi ruxsat kerak emas.

## Testlash rejasi (Feature testlar)

1. Tez qo'shish endpointi yangi brend yaratadi va qaytaradi.
2. Bir xil nom (katta-kichik harf farqi) qayta yuborilsa dublikat yaratilmaydi.
3. `GlobalProductController::store()`/`update()` `brand_id`ni saqlaydi.
4. `catalogQuery()` `brand_id` bo'yicha to'g'ri filtrlaydi.
5. Global katalog sahifasi brend filtri bilan faqat mos mahsulotlarni qaytaradi.
6. Workshop tanlash sahifasi ham brend filtri bilan ishlaydi.
7. Oddiy foydalanuvchi tez-qo'shish endpointiga kira olmaydi.

## Keyingi qadam

To'g'ridan-to'g'ri implementatsiya (TDD bilan, shu seansda) — foydalanuvchi
tezlikni so'ragani uchun to'liq subagent-driven jarayon o'rniga inline
implementatsiya qilinadi.
