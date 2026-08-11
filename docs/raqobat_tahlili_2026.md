# OilControl — Raqobat Tahlili va Strategik Pozitsiya
**Sana:** 2026-08-11 | **Muallif:** Product/Tech review

> ⚠️ **1–4-bo'limlar (raqobat tahlili) kuchda.**
> **5–7-bo'limlar (zaifliklar, roadmap, narx) eskirgan** — tasdiqlangan yakuniy versiya: [`strategiya_va_yol_xaritasi.md`](./strategiya_va_yol_xaritasi.md)

---

## 1. Bizning hozirgi holat (kod bo'yicha faktlar)

**Stack:** Laravel 12 · PHP 8.2+ · Inertia 2 · Vue 3 · Tailwind · Sanctum · Spatie Permission · maatwebsite/excel · dompdf · Ziggy

**Ishlab turgan modullar (23 model, 34 controller, 51 migratsiya):**

| Blok | Tarkib |
|---|---|
| Multi-tenant | `Workshop` → `Branch` (filial), superadmin impersonation (`WorkshopSwitchController`) |
| Rollar | Spatie: `superadmin` / `director` / `manager` / `employee`, 15 ta permission, filial-darajali cheklov |
| Mijoz | `Client` → `Vehicle` (plate unique) → `ServiceLog` |
| Avto baza | `CarMake` + `CarModel` + `car_model_products` (**model ↔ mos moy/filtr + sig'im**) |
| Ombor | `Product`, `GlobalProduct` katalog (copy-from-catalog), `StockMovement` (FIFO), `Inventory`/`InventoryItem` (inventarizatsiya), min_stock ogohlantirish |
| Moliya | `Payment` (cash/card/click/transfer), nasiya (`payment_status`, `due_date`), chegirma, **multi-valyuta ($/so'm)**, **konsignatsiya/realizatsiya** |
| Kontragent | `Supplier` + `SupplierTransaction` (ledger — kimga qancha qarz) |
| Xarajat/HR | `Expense`, `Salary` + oylik hisobot |
| Hisobot | P&L (revenue, labor, COGS, expenses, net_profit, credit_extended), top mahsulot, marka kesimi, ombor qiymati → **Excel + PDF eksport** |
| Aloqa | Telegram bot (webhook), `Reminder` (30/14/7 kun), scheduler `reminders:send` |
| Bron | `Booking` — **Telegram orqali navbat olish** (telegram_user_id bilan) |
| Mobil | Sanctum API (8 ta API controller) + `FLUTTER_ROADMAP.md` |

**Landing (oilcontrol.uz):** narx e'lon qilingan — Bepul / Professional 500 000 so'm / Maxsus.

---

## 2. Raqobatchilar

### 2.1 Hamroh Oil (shepm.uz → hamrohoil.uz) — **eng kuchli raqobatchi**
- **Pozitsiya:** moyxona (BOX) uchun to'liq **ERP/SaaS**, tarmoq boshqaruvi.
- **Modullar:** POS + chek + QR to'lov, ombor + omborlararo ko'chirish + FIFO, kassa/inkassatsiya/avans, ish haqi, P&L + Balans + kontragent hisoboti, markaziy katalog, to'liq rollar.
- **Katta qurol:** **mijoz mobil ilovasi** — "Mening garajim", moliya, nasiya, jarima/sug'urta/texko'rik (tez kunda), servis bron (tez kunda).
- **Narx:** Start $50/oy (600k), Standart $100/oy (1.2mln), Pro $180/oy (2.16mln). Har qo'shimcha filial +$45–50/oy. 14 kun trial, yillik to'lovda 2 oy bepul.
- **Marketing:** Next.js, SEO to'liq (OG, twitter card, keywords), narx jadvali, FAQ, tarif taqqoslash — **professional darajada**.
- **Zaif tomoni:** qimmat (1 boxli kichik moyxona uchun og'ir), mobil ilovaning yarim funksiyasi "tez kunda", SMS faqat Pro tarifda, aloqa raqami hali placeholder (`+998 __ ___ __ __`) — ya'ni ular ham **hali to'liq ishga tushmagan**.

### 2.2 Oiler.uz (Samarqand)
- **Pozitsiya:** "auto service CRM", 10+ modul.
- **Kuchli:** **QR stiker** (mashinaga yopishtiriladi → skan → servis tarixi), avtomatik SMS (tug'ilgan kun, minnatdorchilik, qarz eslatmasi), qarzdorlik nazorati, usta bo'yicha ish haqi, **blog (SEO trafik)**, **3 tilli (UZ/RU/EN)**.
- **Zaif:** o'z saytida "**1+ Connected Services**" deb yozgan — mijoz bazasi deyarli yo'q; narx yashirin (faqat demo so'rash); mobil ilova yo'q; ombor moduli sayoz ko'rinadi.

### 2.3 UzOilCRM.com — **eng zaif**
- Sayt ochilishi bilan to'g'ridan-to'g'ri `/login` ga tashlaydi. **Landing sahifasi umuman yo'q**, narx yo'q, demo yo'q, marketing 0.
- Meta'dan ko'rinishicha funksiya bizga eng yaqin: mijoz, transport, xizmat tarixi, **Telegram eslatma**, hisobotlar.
- Xulosa: texnik jihatdan raqobatchi, **bozor jihatdan raqobatchi emas** — ularni SEO va landing bilan bemalol ortda qoldiramiz.

### 2.4 inAuto.uz — **boshqa segment, lekin uzoq muddatli xavf**
- Bu B2B CRM emas, **B2C marketplace ilovasi** (Google Play + App Store'da bor): xizmat qidirish va bron, yoqilg'i shoxobchalari narxi, xarajat hisoblagichi, hamyon, garaj, avto ijara.
- **Xavf:** ularda haydovchilar bazasi to'planyapti. Saytda "Biznes uchun" bo'limi bor — ertaga servislarga panel bersa, bizning kanalimizga kiradi.
- **Imkoniyat:** raqobat emas, **integratsiya sherigi** bo'lishi mumkin (ular lead beradi, biz servisni boshqaramiz).

---

## 3. Taqqoslash matritsasi

| Funksiya | OilControl | Hamroh Oil | Oiler.uz | UzOilCRM | inAuto |
|---|:--:|:--:|:--:|:--:|:--:|
| Ombor + FIFO tannarx | ✅ | ✅ | ⚠️ | ? | ❌ |
| Inventarizatsiya | ✅ | ✅ | ? | ? | ❌ |
| **Multi-valyuta ($/so'm)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Konsignatsiya/realizatsiya** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Ta'minotchi ledger (qarz)** | ✅ | ⚠️ | ❌ | ❌ | ❌ |
| **Model ↔ moy/filtr + sig'im avtomatik** | ✅ | ⚠️ katalog | ❌ | ❌ | ❌ |
| Nasiya / mijoz qarzi | ✅ | ✅ | ✅ | ? | ❌ |
| P&L hisobot + Excel/PDF | ✅ | ✅ | ⚠️ | ? | ❌ |
| Ish haqi | ✅ | ✅ | ✅ | ❌ | ❌ |
| Ko'p filial | ✅ | ✅ (pullik) | ⚠️ | ❌ | ❌ |
| Rollar/ruxsatlar | ✅ 4 rol | ✅ | ⚠️ | ? | — |
| **Telegram eslatma** | ✅ | ⚠️ (ilova+SMS) | ❌ | ✅ | — |
| **Telegram orqali bron** | ✅ | ⏳ "tez kunda" | ❌ | ❌ | ✅ |
| SMS eslatma | ❌ | ✅ (Pro) | ✅ | ❌ | — |
| Mijoz mobil ilovasi | ❌ | ✅ | ❌ | ❌ | ✅ |
| QR stiker | ❌ | ⚠️ QR to'lov | ✅ | ❌ | ❌ |
| Online to'lov (Payme/Click) obuna | ❌ | ⚠️ | ❌ | ❌ | ✅ |
| Ko'p tillilik | ❌ (uz) | ❌ (uz) | ✅ 3 til | ❌ | ⚠️ |
| Ochiq narx | ✅ | ✅ | ❌ | ❌ | — |
| SEO / blog | ⚠️ | ✅ | ✅ | ❌ | ⚠️ |
| Public API | ⚠️ ichki | ❌ | ❌ | ❌ | ❌ |

---

## 4. Bizning haqiqiy ustunliklarimiz (moat)

1. **Model ↔ mahsulot bog'lanishi sig'im bilan.** Usta "Cobalt" ni tanlaydi — tizim mos moy, filtr va necha litr kerakligini o'zi chiqaradi. Bu chek yaratish vaqtini 3 barobar qisqartiradi. Raqobatchilarda faqat "katalog" bor, **aql yo'q**. Bu — eng kuchli demo momenti.
2. **Telegram-first arxitektura.** Bron + eslatma + xabar — hammasi Telegram'da. Mijoz hech narsa yuklab olmaydi. Hamroh Oil mijozni ilova o'rnatishga majburlaydi (konversiya ~10-20%), biz esa u allaqachon ishlatadigan joyda ushlaymiz. **SMS xarajati ham 0.**
3. **Konsignatsiya + multi-valyuta + ta'minotchi ledger.** Bu O'zbekiston moy bozorining haqiqiy og'rig'i: tovar dollarda dilerdan realizatsiyaga olinadi, so'mda sotiladi. Hech bir raqobatchida bu yo'q.
4. **Narx.** 500k so'm/oy vs Hamroh Oil 600k–2 160k. Biz 1–3 boxli moyxonalar segmentini butunlay egallashimiz mumkin.
5. **Bron moduli allaqachon kodda bor** — Hamroh Oil'da "tez kunda". Biz bugun yetkazib bera olamiz.
6. **Sanctum API + Flutter roadmap tayyor** — mijoz ilovasiga o'tish arxitektura jihatdan ochiq.

---

## 5. Bizning zaif tomonlarimiz (ochiq ayting)

| # | Muammo | Ta'sir | Prioritet |
|---|---|---|---|
| 1 | Landing'da **soxta ma'lumot**: `+998 90 123 45 67`, `© 2024`, "100+ Faol Foydalanuvchilar" | Ishonchni **darhol** yo'qotadi. Hamroh Oil yonida havaskor ko'rinamiz | **P0** |
| 2 | **Obuna nazorati yo'q** — `subscription_expires_at` bor, lekin middleware yo'q. Trial tugaydi, odam ishlayveradi | Pul kelmaydi | **P0** |
| 3 | Payme/Click **billing integratsiyasi yo'q** (payments'dagi 'click' — qo'lda belgi) | Qo'lda pul yig'ish = o'smaydi | **P0** |
| 4 | **SMS fallback yo'q.** Telegram'ga ulanmagan mijozga hech narsa bormaydi | Eslatma qamrovi ~50-60% | **P1** |
| 5 | **Eslatma mantig'i zaif:** `next_service_km / avg_monthly_km` bo'yicha taxminiy sana, faqat ServiceLog yaratilganda. Real probeg yangilansa qayta hisoblanmaydi | Noto'g'ri vaqtda eslatma = mijoz bezor bo'ladi | **P1** |
| 6 | Mijoz ilovasi / kabineti yo'q (Hamroh Oil'ning asosiy qurolі) | Sotuvda yutqazamiz | **P2** |
| 7 | QR stiker yo'q (Oiler'ning arzon va effektiv qurolі) | — | **P2** |
| 8 | Faqat o'zbek tili (RU yo'q — Toshkent bozorining katta qismi) | Toshkentda yopiq eshik | **P2** |
| 9 | Test qamrovi deyarli yo'q, git commit'lar hammasi `.` | Jamoa o'sganda texnik qarz portlaydi | **P2** |
| 10 | SEO/blog yo'q — Oiler blog bilan organik trafik yig'yapti | Lead qimmatga tushadi | **P2** |

---

## 6. Strategik pozitsiya (tanlash kerak)

> **"Hamroh Oil — tarmoqlar uchun ERP. OilControl — bitta boxli moyxona 1 kunda ishga tushiradigan, Telegram'da yashaydigan tizim."**

Ular bilan funksiya bo'yicha yugurish **noto'g'ri strategiya** — ularning byudjeti va jamoasi kattaroq. To'g'ri strategiya: **arzon + tez ishga tushish + Telegram** uchligida ularning yetib bora olmaydigan segmentini olish, keyin pastdan yuqoriga ko'tarilish.

### Yo'l xaritasi

**1-oy (pul oqimi va ishonch):**
- Landing'ni tozalash: haqiqiy telefon, real raqamlar yoki umuman raqamsiz, 2026 yil, demo video
- `CheckSubscription` middleware + trial tugash ekrani
- Click/Payme obuna integratsiyasi
- 14 kun trial (30 emas — Hamroh Oil ham 14, bozor standarti)

**2-oy (eslatma sifatini ko'tarish):**
- Eslatmani odometr asosida qayta hisoblash (probeg yangilanganda `Reminder` qayta quriladi)
- Eskiz.uz SMS fallback: Telegram ulanmagan bo'lsa → SMS
- Eslatma effektivligi hisoboti (yuborildi → qaytdi konversiyasi) — **bu Hamroh Oil'da ham yo'q, sotuvda kuchli argument**

**3–4-oy (differensiatsiya):**
- **Telegram Mini App** — mijoz garaji: avtomobil tarixi, keyingi muddat, bron, qarz. Flutter ilovadan **10 barobar arzon va konversiyasi yuqori**. Bu bizning javobimiz Hamroh Oil'ning ilovasiga
- QR stiker generatori (PDF chop etish → mashina oynasiga)
- RU tili

**5–6-oy (o'sish):**
- Blog + SEO ("moy almashtirish dasturi", "moyxona ERP" kalit so'zlari — Hamroh Oil allaqachon shu keywordlarni egallagan, kechikmaslik kerak)
- Filiallarni solishtirish hisoboti (Hamroh Oil buni Pro tarifda sotadi)
- inAuto bilan integratsiya muzokarasi (lead kanali)

---

## 7. Narx strategiyasi bo'yicha tavsiya

Hozirgi 500k so'm/oy — **yagona tarif kam**. Tavsiya:

| Tarif | Narx | Kimga |
|---|---|---|
| Start | 300 000 so'm/oy | 1 box, 2 foydalanuvchi, Telegram eslatma, ombor, POS |
| Pro | 600 000 so'm/oy | + filial, ish haqi, P&L/FIFO hisobot, SMS paketi, cheksiz foydalanuvchi |
| Tarmoq | 400 000 so'm/oy har filial | 3+ filial, solishtirish hisoboti, shaxsiy menejer |

Sabab: Hamroh Oil'ning eng arzoni 600k. Bizning 300k — **ular kira olmaydigan narx darajasi**, lekin funksiyamiz Start'idan kam emas. Pro esa ularning Start narxida ularning Standart funksiyasini beradi.
