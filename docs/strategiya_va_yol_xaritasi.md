# OilControl — Kelishilgan Strategiya va Yo'l Xaritasi
**Versiya:** 1.0 · **Sana:** 2026-08-11 · **Holat:** tasdiqlangan, kod yozish kutilmoqda

> Raqobat tahlili: [`raqobat_tahlili_2026.md`](./raqobat_tahlili_2026.md) (1–4-bo'limlar kuchda, 5–7-bo'limlar shu hujjat bilan almashtirildi).

---

## 1. Pozitsiya

> **Hamroh Oil — tarmoqlar uchun qimmat ERP ($50–180/oy).
> OilControl — 300 ming so'mga to'liq tizim, funksiyasi kesilmagan, Telegram'da yashaydi.**

Raqobatchilar funksiyani tarifga bo'lib sotadi: Hamroh Oil'da P&L, FIFO, rollar — faqat $100 tarifda; SMS — faqat $180 tarifda. **Biz buni qilmaymiz.** Bizda eng arzon tarifda ham tizim to'liq ishlaydi, faqat **hajm** cheklanadi (filial, xodim soni).

**Sotuvdagi bitta jumla:** *"300 ming so'mga Hamroh Oil'ning 1.2 million so'mlik tarifidagi hamma narsa bor — faqat bitta filial uchun."*

---

## 2. Tarif tuzilishi

**Trial: 30 kun, karta so'ralmaydi.** (Tizim barqarorlashgach 14 kunga tushiriladi — bozor standarti.)

| | **Start** | **Pro** | **Maxsus** |
|---|---|---|---|
| Narx | **300 000 so'm/oy** | **600 000 so'm/oy** | kelishuv asosida |
| Filial | 1 ta *(BOX soni cheklanmaydi)* | 3 ta | cheksiz |
| Faol foydalanuvchi | 5 ta | 20 ta | cheksiz |
| Mijoz / avtomobil / xizmat | cheksiz | cheksiz | cheksiz |
| Ombor, FIFO, inventarizatsiya | ✅ | ✅ | ✅ |
| Multi-valyuta, konsignatsiya | ✅ | ✅ | ✅ |
| Ta'minotchi ledger | ✅ | ✅ | ✅ |
| Nasiya / mijoz qarzi | ✅ | ✅ | ✅ |
| Ish haqi | ✅ | ✅ | ✅ |
| P&L, hisobotlar, Excel/PDF | ✅ | ✅ | ✅ |
| Telegram eslatma + Mini App | ✅ | ✅ | ✅ |
| Eslatma effektivligi hisoboti | ✅ asosiy | ✅ to'liq | ✅ to'liq |
| **Filiallarni solishtirish hisoboti** | — | ✅ | ✅ |
| Qo'llab-quvvatlash | Telegram | prioritet | shaxsiy menejer |

**Nima YO'Q (ataylab):** POS terminali, qog'oz chek, SMS, QR stiker, mijoz mobil ilovasi (Mini App o'rniga), online bron (kelajakda).

**"Chek yo'q" bo'shlig'ini nima to'ldiradi:** xizmat tugagach mijozga **Telegram'da elektron kvitansiya** boradi — nima quyildi, necha litr, qancha turdi, keyingi muddat. Qog'oz yo'qoladi, Telegram'daki qolib ketadi.

---

## 3. Yo'l xaritasi

### Bosqich 0 — Ishonch *(1–2 hafta)*
Hozirgi landing bizni havaskor ko'rsatyapti. Birinchi ish — shuni tuzatish.

- [ ] Telefon: **+998 93 705 88 55** (hozirgi `+998 90 123 45 67` — placeholder)
- [ ] `© 2024` → `© 2026`
- [ ] Soxta raqamlar (`100+ foydalanuvchi`, `10 000+ operatsiya`, `99.9%`) o'rniga: **"1 ta faol moyxona bizda ishlaydi"** — kam bo'lsa ham rost. Yoki raqamlar blokini butunlay olib tashlab, o'rniga real skrinshot/demo video.
- [ ] Tarif jadvalini yangilash: 30 kun trial, Start 300k, Pro 600k, Maxsus
- [ ] **Texnik SEO:** `<title>`/`meta description` har sahifada, OG + Twitter card, `sitemap.xml`, `robots.txt`, `schema.org/SoftwareApplication` (narx bilan), Lighthouse ≥90
- [ ] Landing'dagi va'dalarni kod bilan solishtirib chiqish — yozilgan hamma narsa ishlashi shart

### Bosqich 1 — Pul oqimi *(2–3 hafta)* 🔴 eng muhim
Hozir obuna tugaydi, foydalanuvchi ishlayveradi. Ya'ni tizim pul yig'a olmaydi.

- [ ] `subscription_payments` jadvali — `method: cash | p2p | click | payme`, `provider_transaction_id`, `provider_payload` (json), `status`, `paid_at`, `confirmed_by`
- [ ] Tarif limitlari — `config/plans.php` + `workshops.limits_override` (json, Maxsus tarif uchun)
- [ ] `CheckSubscription` middleware → **read-only rejim**: GET va Excel/PDF eksport ochiq, barcha POST/PUT/PATCH/DELETE bloklangan
- [ ] Limit nazorati: filial qo'shish, foydalanuvchi qo'shish (faol login qila oladigan **barcha** user sanaladi — director ham)
- [ ] Superadmin paneli: to'lov qo'shish → obuna avtomatik uzayadi, to'lov tarixi
- [ ] Ogohlantirish: 7 / 3 / 1 kun qolganda direktorga Telegram + panelda banner

> **Click/Payme:** bazani hozirdan moslab yozamiz, integratsiyaning o'zi keyinroq yoqiladi. Merchant kalitlari kelganda faqat controller + webhook qo'shiladi, migratsiya sindirilmaydi.

### Bosqich 2 — Eslatma sifati *(2–3 hafta)*
Eslatma — mahsulotning asosiy va'dasi. Hozir u taxminiy ishlaydi.

- [ ] Eslatmani **odometr asosida** qayta hisoblash: probeg yangilanganda `Reminder` qayta quriladi (hozir faqat ServiceLog yaratilganda bir marta hisoblanadi)
- [ ] **Botga ulash oqimi** — pastdagi "Asosiy risk" bo'limiga qarang
- [ ] **Eslatma effektivligi hisoboti:** yuborildi → yetdi → ochildi → **30 kun ichida qaytdi %**, qaytganlar tushumi, eslatmasiz qaytish bilan taqqoslash
- [ ] Asosiy versiya Start'da, davr/usta/filial kesimi Pro'da

### Bosqich 3 — Telegram Mini App *(3–4 hafta)*
Hamroh Oil'ning mobil ilovasiga bizning javobimiz. Mijoz hech narsa yuklab olmaydi.

**Nolldan yozilmaydi — mavjud bot Mini App'ga o'tkaziladi.** Hozirgi bot (`TelegramBotService` + `TelegramWebhookController`) ishlab turibdi: `/start` → telefon → `clients.telegram_id` bog'lanadi → eslatma keladi. Ya'ni **auth qatlami va bog'lanish bazasi allaqachon tayyor** — Mini App aynan `clients.telegram_id` ustuniga suyanadi.

**Bot yo'q qilinmaydi. Bot = kanal (xabar keladi), Mini App = interfeys (ko'rish va boshqarish).**

- [ ] 🔴 **Xavfsizlik: matn orqali raqam yuborishni o'chirish** — pastdagi 5.1-bo'limga qarang. Mini App'dan **oldin** hal bo'lishi shart
- [ ] Deep-link ulash: `t.me/bot?start=<client_token>` — usta paneldan link/QR chiqaradi, mijoz telefonida bir marta bosiladi
- [ ] `initData` HMAC-SHA256 verifikatsiyasi (backendda, bot token bilan) → `Client`
- [ ] **Ko'p-moyxona holati:** hozirgi kod ataylab bitta `telegram_id` ni bir nechta `Client` ga bog'laydi (mijoz turli moyxonalarda bo'lishi mumkin). **Qaror:** garajda hamma avtomobil **bitta ro'yxatda**, har birining tagida moyxona nomi yoziladi. Moyxona tanlagichi yo'q — mijoz uchun bu bitta garaj, kim xizmat ko'rsatgani ikkinchi darajali
- [ ] **Mening garajim** — avtomobillar, servis tarixi, qaysi moy quyilgani, probeg
- [ ] **Keyingi muddat** — sana/km + countdown
- [ ] **Kvitansiya** — har xizmatdan keyin avtomatik keladi; **qarz balansi** (nasiya qoldig'i)
- [ ] Eslatma xabari ostiga `web_app` tugmasi: "Garajimni ochish"; BotFather'da Menu Button → Web App URL
- [ ] Bron — **hozircha yo'q**, kelajakda. `Booking` modeli bazada bor, lekin webhook'da `callback_query` handler yo'q — **hozir ishlatilmayotgan kod**, chalkashmaslik uchun eslatib qo'yamiz

### Bosqich 4 — Rus tili *(2 hafta)*
- [ ] `users.locale` (panel tili) + `clients.locale` (mijoz tili)
- [ ] Laravel lang fayllari → Inertia shared props orqali Vue'ga
- [ ] Admin panel to'liq tarjima
- [ ] **Excel/PDF eksport panel tiliga ergashadi** — rus tilida ishlayotgan bo'lsa, hujjat ham rus tilida
- [ ] Telegram bot: mijoz birinchi ulanganda **tilni so'raydi**, keyin davom etadi
- [ ] Landing `/ru` + `hreflang`

### Bosqich 5 — O'sish
- [ ] Filiallarni solishtirish hisoboti (Pro tarif)
- [ ] Instagram blog (1–2 haftadan keyin) → kontent yig'ilgach tizim blogiga ko'chirish + SEO
- [ ] Diler kanali — moy tarqatuvchi dilerlar bazasi orqali moyxonalarga chiqish

---

## 4. Texnik qarorlar (kod yozishdan oldin eslab qolish)

**BOX masalasi.** Hozir modellashtirilmaydi — hisob **filial kesimida**. Kim ishlaganini `User` (usta) orqali bilamiz, ish haqi shundan hisoblanadi.
*Kelajakda chalkashlik bo'lmasligi uchun:* box qo'shilganda `boxes` jadvali (`branch_id` FK) + `service_logs.box_id` **nullable** bo'ladi. Shuning uchun hozirdan **`service_logs.branch_id` har doim to'g'ri to'ldirilishi shart** — shunda box qo'shilganda eski yozuvlarni backfill qilish kerak bo'lmaydi, `box_id` shunchaki `null` qolib ketaveradi.

**To'lov jadvali.** Obuna to'lovi (`subscription_payments`) va mijoz to'lovi (`payments`) — **ikki xil narsa, aralashtirmaymiz**. Mijoz to'lovi naqd/p2p bo'lib qolaveradi, integratsiya kerak emas.

**Limitlar.** `config/plans.php` da (DB'da emas) — narx o'zgarsa deploy bilan ketadi, migratsiya kerak emas. Maxsus tarif uchun `workshops.limits_override` json ustuni.

**Read-only.** Middleware darajasida, controller ichida emas — bitta joyda, chetlab o'tib bo'lmaydi.

---

## 5. Asosiy risk — buni hal qilmasak, strategiya qulaydi

**Telegram-first butun strategiyamiz mijoz botga ulangan bo'lishiga bog'liq.** Telegram'da telefon raqami bo'yicha xabar yuborib bo'lmaydi — bizga mijozning `chat_id` si kerak. Ulanmagan mijoz = eslatma yo'q = Mini App yo'q = mahsulotning asosiy qiymati yo'q.

Siz SMS'ni ataylab olib tashladingiz (to'g'ri qaror — pul turadi), demak **ulanish konversiyasi 90%+ bo'lishi shart**. Buning uchun:

1. **Deep link:** `t.me/oilcontrol_bot?start=<client_token>` — har mijozga unikal
2. **Ulanish xizmat paytida bo'ladi, keyin emas.** Usta chek yozayotganda ekranda tugma chiqadi → mijozning telefonida bir marta bosiladi. "Keyin o'zi ulanadi" — ulanmaydi.
3. **Panelda ko'rsatkich:** har filial bo'yicha "botga ulangan mijozlar: 68%". Ustaxona egasi buni ko'rsa, ustalarni majburlaydi.
4. Siz aytgan muammo — mijozlar telefon bilan qiynaladi — aynan shuning uchun **usta o'zi ulab beradi**, mijozga topshiriq berilmaydi.

Bu Bosqich 2 ning eng muhim qismi. Mini App yozishdan **oldin** hal bo'lishi kerak.

### 5.1 Xavfsizlik nuqsoni — Mini App'dan oldin yopilishi shart

`TelegramBotService::handlePhoneNumber()` matn sifatida yozilgan **istalgan** raqamni qabul qiladi va o'sha raqamli `Client` ni yuboruvchining `chat_id` siga bog'lab qo'yadi. `handleContactShared()` da spoofing himoyasi bor (`contact.user_id === from.id`), lekin matn yo'li uni **butunlay chetlab o'tadi**.

Bundan tashqari `$wasLinkedElsewhere` mantig'i — begona odam raqamni yozsa, mijozni **avvalgi egasidan tortib oladi**.

Hozir bot faqat eslatma yuborardi, zarar cheklangan edi. Mini App'da **qarz balansi, kvitansiya, servis tarixi** bo'ladi — bu shaxsiy moliyaviy ma'lumot. Ya'ni nuqson **P0 darajaga ko'tariladi**.

**Yechim** (Bosqich 2 da bajariladi):
1. Matn orqali raqam qabul qilishni **butunlay o'chirish** — `looksLikePhoneNumber` yo'li olib tashlanadi
2. Faqat ikki yo'l qoladi: **deep-link token** (asosiy — usta ulaydi) va **`request_contact` tugmasi** (zaxira — Telegram raqamni o'zi tasdiqlaydi, soxtalashtirib bo'lmaydi)
3. Mijoz allaqachon boshqa `telegram_id` ga bog'langan bo'lsa, avtomatik tortib olinmaydi — moyxona xodimi tasdiqlashi kerak

### 5.2 Deep-link ulash oqimi (batafsil)

**Asosiy tushuncha:** usta ekrani va mijoz telefoni — **ikki xil qurilma**. Ustaning kompyuteridagi havolani mijoz telefoniga "bosib" bo'lmaydi. QR kod aynan shu ko'prikni yasaydi.

**Texnik tomoni.** Har `Client` ga tasodifiy, bir martalik kalit beriladi (`clients.telegram_link_token`). Havola shundan yasaladi:

```
t.me/oilcontrol_bot?start=Ab3xK9mZ
```

Havola ochilganda Telegram botni ko'rsatadi va **START** tugmasi chiqadi. Bosilgach webhook'ga `/start Ab3xK9mZ` keladi → kalit bo'yicha mijoz topiladi → `chat_id` saqlanadi → kalit kuyadi. Telefon raqami **so'ralmaydi**.

**Holat 1 — mijoz yoningizda (asosiy yo'l, ~10 soniya):**
usta paneldan "Botga ulash" bosadi → ekranda **QR** chiqadi → usta **mijozning telefonini olib** QR ni skanerlaydi → Telegram ochiladi → usta START bosadi → tayyor.

**Holat 2 — mijoz yonida yo'q:**
usta "Havolani nusxalash" bosadi → mijozga SMS/Telegram orqali yuboradi → mijoz o'zi bosadi.

QR va havola — **bitta narsaning ikki ko'rinishi**, ikkalasi bir ekranda yonma-yon turadi.

> **Diqqat — bu rad etilgan "QR stiker" emas.** QR stikerni mashina oynasiga yopishtiriladi va uni **mijozning o'zi** skanerlaydi — shuning uchun rad etildi (mijozlar telefon bilan qiynaladi). Bu yerdagi QR esa **usta ekranida turadi va uni usta o'zi skanerlaydi**. Mijozga hech qanday topshiriq berilmaydi.

### 5.3 Telefon raqami ≠ Telegram bog'lanishi 🔑

Bu — hozirgi dizayndagi asosiy xatoning ildizi. Ikkisi **ikki xil vazifani** bajaradi va bir-biriga bog'lanmasligi kerak:

| Maydon | Vazifasi | Kim ishlatadi |
|---|---|---|
| `clients.phone` | **Aloqa** — usta qo'ng'iroq qiladi | Usta, moyxona |
| `clients.telegram_id` | **Xabar kanali** — eslatma, kvitansiya, Mini App | Tizim |

Hozirgi `findClientsByPhone()` telefonni **join key** sifatida ishlatadi (oxirgi 9 raqam bo'yicha). Lekin telefon aslida aloqa maydoni, kalit emas. Deep-link token buni to'g'irlaydi — bog'lanishda raqam **umuman ishtirok etmaydi**.

**Hal bo'ladigan holatlar:**

1. **Mijozda 2 ta raqam, Telegram boshqasida.** Moyxonaga A raqamni bergan, Telegram B da.
   → `phone` = A da **qolaveradi** (usta shu raqamga qo'ng'iroq qiladi). Usta havola yuboradi, mijoz uni B dagi Telegram'ida ochadi. Ikkalasi ham to'g'ri.
   ❌ **Raqamni B ga almashtirmaslik kerak** — aks holda usta mijozga qo'ng'iroq qila olmay qoladi.

2. **Mijozda Telegram umuman yo'q, o'g'lida bor.** (Yoshi katta haydovchi, tugmali telefon — O'zbekistonda juda ko'p uchraydi.)
   → Usta havolani o'g'liga yuboradi. Eslatma o'g'liga keladi, u otasiga aytadi. Mashina o'g'lining garajida ham ko'rinadi.
   Telefon bo'yicha bog'lashda bu **hech qachon ishlamaydi** — o'g'lining raqami bazada yo'q. Token bilan qo'shimcha kod yozilmasdan ishlaydi.

**Mijoz kartochkasida ko'rinishi shart** — usta eslatma kimga ketayotganini bilishi kerak:

```
Karimov Jasur
  Telefon:  +998 90 123 45 67        [qo'ng'iroq]
  Telegram: ✓ @jasur_ogli            [uzish]
            (Otabek Karimov)
```

---

## 6. Vaqt bosimi

Hamroh Oil saytida aloqa raqami hali `+998 __ ___ __ __` — ular ham **to'liq ishga tushmagan**. Oiler.uz o'zi "1+ ulangan servis" deb yozgan. UzOilCRM'da landing umuman yo'q.

**Ya'ni hozir bozorda hech kim yetakchi emas.** Keyingi 3–4 oy — kim birinchi bo'lib real mijoz bazasi yig'sa, o'sha yutadi. Bosqich 0 va 1 ni cho'zmaslik kerak.
