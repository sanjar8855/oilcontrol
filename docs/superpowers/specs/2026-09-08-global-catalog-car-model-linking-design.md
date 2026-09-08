# Global katalog — avtomobil modeli mosligi va avto-sinxronizatsiya (dizayn)

**Sana:** 2026-09-08
**Holat:** Tasdiqlangan, implementatsiyaga tayyor

## Muammo

Hozirgi holatda avtomobil modeli ↔ mahsulot mosligi (`car_model_products` jadvali)
har bir workshop'ning **o'z lokal `Product`** yozuviga bog'langan. Natijada:

1. Har bir tadbirkor global katalogdan bir xil mahsulotni (masalan "Motor moyi
   5W-30") o'ziga nusxa olsa ham, qaysi moshina modellariga mos kelishini
   **qaytadan o'zi** kiritishi kerak bo'ladi — bu ishning takrorlanishi va
   tadbirkorning ishi emas (u nima sotishi bilan qiziqadi, mijozning moshinasi
   qaysi model ekanligi bilan emas).
2. Tadbirkor o'z katalogiga to'g'ridan-to'g'ri (`Products/Create` orqali) yangi
   mahsulot qo'shganda, bu mahsulot global katalogda mavjud yoki yo'qligi hech
   tekshirilmaydi — shu sababli global katalog vaqt o'tishi bilan boyib
   bormaydi.

Hozirgi ma'lumotlar holati (2026-09-08 holatiga ko'ra): `global_products`
jadvali bo'sh (0 ta yozuv), `products` jadvalida 40 ta mahsulot bor va
ularning hech biri `global_product_id`ga bog'lanmagan, `car_model_products`da
esa 22 ta mahsulotga tegishli 42 ta bog'lanish mavjud. Ya'ni bu — amaliyotda
hali keng ishlatilmagan, lekin tuzilishi muhim bo'lgan o'zgarish.

## Maqsad

1. Avtomobil modeli ↔ mahsulot mosligini **global katalog darajasiga**
   ko'chirish, shunda bitta joyda kiritilgan moslik barcha uni ishlatuvchi
   workshoplarga avtomatik meros bo'lib o'tadi.
2. Tadbirkor o'z katalogiga yangi mahsulot qo'shganda (qism ham, xizmat turi
   ham — masalan shina damlash, azot damlash, moyka xizmati), tizim buni
   avtomatik ravishda global katalog bilan solishtirib, mos yozuv topsa
   bog'laydi, topmasa global katalogga yangi yozuv sifatida qo'shadi.

## Doirasi tashqarisida (Out of scope)

- Global katalogdagi mahsulotlarni qo'lda birlashtirish/ajratish (dedup)
  uchun admin vositasi — kelajakda kerak bo'lsa alohida vazifa.
- Moshina-moslikni fuzzy/AI orqali aniqlash — hozircha aniq (exact) moslik
  yetarli.

## 1. Ma'lumotlar bazasi sxemasi

Yangi pivot jadval: **`car_model_global_products`**

| ustun | tur | izoh |
|---|---|---|
| `id` | bigint | |
| `car_model_id` | FK → `car_models` | cascade delete |
| `global_product_id` | FK → `global_products` | cascade delete |
| `quantity` | decimal(8,2), default 1 | shu moshina modeli uchun kerakli miqdor (masalan litr) |
| `timestamps` | | |

Unique: `(car_model_id, global_product_id)`.

Eski `car_model_products` jadvali **o'chirilmaydi** — u faqat
`global_product_id`ga hali bog'lanmagan mahsulotlar uchun zaxira (fallback)
sifatida qoladi (amalda bu holat backfill'dan keyin deyarli bo'lmaydi).

### Model o'zgarishlari

- `GlobalProduct::carModels(): BelongsToMany` — yangi, `car_model_global_products` orqali, `withPivot('quantity')`.
- `CarModel::globalProducts(): BelongsToMany` — yangi, xuddi shu pivot orqali.
- `Product::carModels()` relationi **`Product::localCarModels()`** deb qayta nomlanadi (eski `car_model_products` pivotiga bog'langanicha qoladi, faqat nomi o'zgaradi).
- `Product::effectiveCarModels(): Collection` — yangi oddiy metod (Eloquent relation emas):
  ```php
  public function effectiveCarModels(): Collection
  {
      if ($this->global_product_id) {
          return $this->globalProduct?->carModels ?? collect();
      }
      return $this->localCarModels;
  }
  ```
  Barcha kontroller/UI shu metoddan foydalanadi (`carModels()->sync()` kabi eski to'g'ridan-to'g'ri chaqiruvlar `localCarModels()`ga o'tkaziladi va faqat `global_product_id` bo'sh bo'lgan holatda ishlatiladi).

## 2. Avtomatik moslashtirish/qo'shish logikasi

Yangi servis: **`App\Services\GlobalProductMatcher::findOrCreateFor(array $data): GlobalProduct`**

`$data`: `name`, `sku`, `barcode`, `unit`, `description`, `category_name` (workshop kategoriyasi nomi, bo'lishi shart emas).

Qidirish tartibi (barcode/SKU ustunlik, ammo ular ko'pincha bo'sh bo'lishi
mumkinligi hisobga olingan — moy almashtirish shahobchalari odatda barcode
skaner ishlatmaydi):

1. `barcode` bo'sh bo'lmasa → aynan shu qiymat bo'yicha `GlobalProduct` qidiriladi.
2. Topilmasa va `sku` bo'sh bo'lmasa → aynan shu qiymat bo'yicha qidiriladi.
3. Topilmasa → nom normallashtirilib (`LOWER(TRIM(name))`) aynan moslik qidiriladi.
4. Hech narsa topilmasa → yangi `GlobalProduct` yaratiladi: `name`, `sku`,
   `barcode`, `unit`, `description` mahsulotdan, `global_category_id` esa
   `category_name` bo'yicha `GlobalCategory::firstOrCreate()` orqali
   (bo'sh bo'lsa `null`).

### Qo'llanish nuqtalari

- `ProductController::createProductWithInitialStock()` — mahsulot
  yaratilgandan so'ng, **faqat `global_product_id` hali `null` bo'lsa**
  (ya'ni `copyFromCatalog`dan kelmagan bo'lsa), matcher chaqiriladi va
  natija `global_product_id`ga yoziladi. Bu metod umumiy bo'lgani uchun
  `store()` ham, `bulkStore()` ham avtomatik qamrab olinadi.
- `copyFromCatalog()` — o'zgarishsiz, chunki `global_product_id` allaqachon aniq.
- Bu qism to'liq mavjud DB tranzaksiya ichida, sinxron ishlaydi (hajm juda
  kichik — alohida navbat/queue kerak emas).

Bu logika **barcha turdagi mahsulotlarga** bir xilda qo'llaniladi — jismoniy
ehtiyot qismlarga ham ("Moy filtri"), xizmat turlariga ham ("Azot damlash
xizmati", "Moyka xizmati"). Xizmat turlari global katalogga tushadi, lekin
ularga moshina modeli biriktirilishi shart emas (4-bo'limga qarang) — bu
ixtiyoriy, faqat moshinaga bog'liq ehtiyot qismlar uchun to'ldiriladi.

**Muhim texnik cheklov:** `products` jadvalida `unique(workshop_id,
global_product_id)` mavjud (bitta workshop bitta global mahsulotga faqat bir
marta bog'lanishi mumkin). Shu sababli, matcher orqali topilgan/yaratilgan
`GlobalProduct`ni yangi mahsulotga bog'lashdan oldin, shu workshopda allaqachon
shu `global_product_id`ga bog'langan boshqa mahsulot bor-yo'qligi tekshiriladi
— bor bo'lsa, yangi mahsulotning `global_product_id`i `null` qoldiriladi (u
holda 4-bo'limdagi lokal fallback vidjeti orqali qo'lda moshina biriktirish
mumkin bo'lib qoladi). Bu — bitta workshop ichida tasodifan bir xil nomli
ikkita mahsulot alohida-alohida qo'shilgan kamdan-kam holatni xavfsiz
qoldiradi, DB xatosiga olib kelmaydi.

## 3. Mavjud ma'lumotlarni ko'chirish (backfill migratsiyasi)

Bir martalik migratsiya, bitta DB tranzaksiya ichida:

1. `global_product_id`i `null` bo'lgan barcha `Product`larni olib, har biriga
   `GlobalProductMatcher::findOrCreateFor()` chaqiriladi, natija
   `global_product_id`ga yoziladi (bir xil nomli mahsulotlar turli
   workshoplarda bitta global yozuvga birlashadi).
2. Har bir eski `car_model_products` qatori uchun, mos `product`ning yangi
   `global_product_id`si orqali `car_model_global_products`ga
   `(car_model_id, global_product_id, quantity)` yoziladi. Agar shu juftlik
   (`car_model_id`, `global_product_id`) allaqachon mavjud bo'lsa (masalan
   ikki xil workshop bir xil bog'lamani alohida kiritgan bo'lsa),
   **`max(quantity)`** saqlanadi, dublikat qator yaratilmaydi.
3. Eski `car_model_products` jadvali o'zgarishsiz saqlanib qoladi.

Migratsiya oxirida natija (nechta global mahsulot yaratilgani, nechta
bog'lanish ko'chirilgani) log qilinadi — production'ga qo'llashdan oldin
tekshirib chiqish uchun. Production DB'ga qo'llashdan oldin zaxira nusxa
olinadi.

**Xavfsizlik eslatmasi:** loyihaning `phpunit.xml`da testlar uchun alohida
`sqlite :memory:` baza sozlangan (`.env`dagi haqiqiy `sql_oilcontrol`
MySQL bazasiga tegmaydi) — implementatsiya va testlash shu izolyatsiyadan
foydalanadi, production ma'lumotlariga risk yo'q.

## 4. UI va ruxsatlar

**Tadbirkor tomonida:**
- `Products/Create.vue` — "Avto markalariga biriktirish" maydoni olib
  tashlanadi (endi kerak emas, moslik global darajada).
- `Products/Show.vue` — moshina mosligi **faqat o'qish uchun** ro'yxat
  sifatida ko'rsatiladi (`effectiveCarModels()` orqali), agar
  `global_product_id` mavjud bo'lsa.
- Agar `global_product_id` hali `null` bo'lsa (kamdan-kam holat) — hozirgi
  tahrirlanadigan vidjet (`CarMakeController::attachProduct/detachProduct/
  updateProductQuantity`, `localCarModels()` orqali) o'zgarishsiz ishlayveradi.

**Global admin tomonida (`GlobalProducts/Edit.vue`):**
- Yangi bo'lim: moshina modelini tanlash + har biri uchun miqdor (litr)
  kiritish jadvali (`CarMakes/Index.vue`dagi mavjud pattern asosida).
- Yangi endpointlar `GlobalProductController`ga, `can:global-products.manage`
  gate ostida:
  - `POST /global-products/{globalProduct}/car-models`
  - `PUT /global-products/{globalProduct}/car-models/{carModel}`
  - `DELETE /global-products/{globalProduct}/car-models/{carModel}`

Yangi permission kerak emas — mavjud `global-products.manage` gate'idan
foydalaniladi.

## 5. Chegaraviy holatlar

- Bir xil nomli, lekin haqiqatda boshqa mahsulot ikkita workshopda: nom
  bo'yicha moslashtirish ularni bitta global yozuvga birlashtirib qo'yishi
  mumkin — bu boshlang'ich bosqichda qabul qilinadigan taxminiy xato,
  kerak bo'lsa admin keyinchalik `GlobalProducts/Index`da tuzatadi.
- Workshop kategoriyasi bo'lmasa (`category_id` null) — global kategoriya
  ham `null` qoladi, xato bermaydi.
- Bir vaqtda bir xil yangi mahsulotni ikki so'rov yaratishi (race condition)
  — amaliyot hajmi juda kichik, alohida lock kerak emas.

## Testlash rejasi (Feature testlar)

1. `store()` — global katalogda mos yozuv bo'lmasa, avtomatik yaratiladi va
   `global_product_id` bog'lanadi.
2. Xuddi shu barcode/SKU/nom bilan ikkinchi mahsulot qo'shilganda — yangi
   `GlobalProduct` yaratilmaydi, mavjudiga bog'lanadi.
3. `copyFromCatalog` orqali qo'shilganda matcher chaqirilmaydi.
4. `GlobalProduct`ga moshina biriktirilsa, shu global mahsulotga bog'langan
   barcha workshop mahsulotlarining `effectiveCarModels()`ida ko'rinadi.
5. Backfill migratsiyasi: eski bog'lanishlar to'g'ri ko'chishi, dublikatlarda
   `max(quantity)` olinishi.

## Keyingi qadam

Ushbu spec asosida `writing-plans` ko'nikmasi orqali batafsil implementatsiya
rejasi tuziladi.
