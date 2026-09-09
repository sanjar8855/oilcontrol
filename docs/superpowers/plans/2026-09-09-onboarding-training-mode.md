# Yangi foydalanuvchi uchun o'qitish (onboarding) rejimi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Yangi ro'yxatdan o'tgan workshop uchun 3 bosqichli majburiy o'qitish siklini (mahsulot tanlash → mijoz+moshina qo'shish → savdo qilish) qurish, sikl tugamaguncha (yoki "chiqish" bosilmaguncha) dashboardni yopiq qilib turish.

**Architecture:** `workshops` jadvaliga `onboarding_step`/`onboarding_completed_at` qo'shiladi. Yangi `EnsureOnboardingComplete` middleware (`EnsureActiveWorkshop` naqshi asosida) `web` middleware guruhiga qo'shilib, onboarding tugamagan workshoplarni joriy qadamiga qayta yo'naltiradi. Yangi `OnboardingController` + 2 ta minimal Vue sahifa (`Onboarding/Products.vue`, `Onboarding/Vehicle.vue`) 1- va 2-qadamlarni bajaradi, mavjud `ProductController`/`ClientController` mantiqi (endi umumiy `ProductCreationService`ga chiqarilgan) qayta ishlatiladi. 3-qadam **yangi sahifa yaratmaydi** — foydalanuvchi haqiqiy `Vehicles/Show.vue` sahifasiga o'tkaziladi, u yerdagi mavjud "Savdo qilish" tugmasi atrofida spotlight ko'rsatiladi; savdo (`ServiceLogController::store`) muvaffaqiyatli saqlangach sikl yakunlanadi va dashboardga redirect qilinadi.

**Tech Stack:** Laravel 11 (PHP), Inertia.js + Vue 3, MySQL, Pest/PHPUnit (`RefreshDatabase`, `tests/Concerns/CreatesTestWorkshop`). Loyihada JS test frameworki yo'q — Vue sahifalari qo'lda (dev serverda) tekshiriladi.

**Spec:** `docs/superpowers/specs/2026-09-09-onboarding-training-mode-design.md`

## Global Constraints

- Faqat **bugundan keyin ro'yxatdan o'tadigan** yangi workshoplar onboarding'ni ko'radi — mavjud workshoplar migratsiya paytida darhol "tugagan" deb belgilanadi va hech qachon to'silmaydi.
- Onboarding holati **workshop darajasida** saqlanadi (foydalanuvchi darajasida emas).
- Server-side gating: middleware orqali, frontendga ishonilmaydi.
- Bosqichlar orasida **na oldinga, na orqaga** o'tib bo'lmaydi — faqat joriy qadam ochiladi.
- "Chiqish" (skip) tugmasi har doim, barcha bosqichlarda ko'rinadi va bosilganda sikl butunlay yakunlangan deb belgilanadi.
- Mavjud ishlaydigan backend mantiq (global katalogdan nusxalash, mijoz+moshina yaratish, savdo yozuvi) **qayta yoziladigan emas, qayta ishlatiladigan** — faqat umumiy joylarga chiqariladi.
- Migratsiyalar faqat qo'shimcha (additive) — hech qanday mavjud ustun/jadval o'chirilmaydi yoki buzilmaydi.

---

## Task 1: `workshops` jadvaliga onboarding maydonlari + model yordamchilari

**Files:**
- Create: `database/migrations/2026_09_09_090000_add_onboarding_fields_to_workshops_table.php`
- Modify: `app/Models/Workshop.php`
- Test: `tests/Feature/WorkshopOnboardingStateTest.php`

**Interfaces:**
- Produces: `Workshop::$fillable` ga `onboarding_step` (nullable string), `onboarding_completed_at` (nullable datetime cast) qo'shiladi; `Workshop::isOnboarding(): bool`; `Workshop::advanceOnboarding(?string $nextStep): void` — keyingi barcha tasklar shulardan foydalanadi.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/WorkshopOnboardingStateTest.php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class WorkshopOnboardingStateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_workshops_table_has_onboarding_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('workshops', [
            'onboarding_step', 'onboarding_completed_at',
        ]));
    }

    public function test_a_workshop_created_without_an_explicit_step_is_not_onboarding(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();

        $this->assertNull($workshop->onboarding_step);
        $this->assertFalse($workshop->isOnboarding());
    }

    public function test_advance_onboarding_moves_to_the_next_step(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $workshop->advanceOnboarding('vehicle');

        $fresh = $workshop->fresh();
        $this->assertSame('vehicle', $fresh->onboarding_step);
        $this->assertTrue($fresh->isOnboarding());
        $this->assertNull($fresh->onboarding_completed_at);
    }

    public function test_advance_onboarding_to_null_marks_it_completed(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);

        $workshop->advanceOnboarding(null);

        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertFalse($fresh->isOnboarding());
        $this->assertNotNull($fresh->onboarding_completed_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter WorkshopOnboardingStateTest`
Expected: FAIL — `Schema::hasColumns` returns false / `isOnboarding` method does not exist.

- [ ] **Step 3: Create the migration**

```php
<?php
// database/migrations/2026_09_09_090000_add_onboarding_fields_to_workshops_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->string('onboarding_step')->nullable()->after('is_active');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });

        // Mavjud workshoplar hech qachon onboarding ko'rmasligi kerak.
        DB::table('workshops')
            ->whereNull('onboarding_step')
            ->whereNull('onboarding_completed_at')
            ->update(['onboarding_completed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });
    }
};
```

- [ ] **Step 4: Add fillable/casts/helpers to the Workshop model**

In `app/Models/Workshop.php`, add to `$fillable` (after `'is_active'`):

```php
        'onboarding_step',
        'onboarding_completed_at',
```

Add to `$casts` (after `'is_active' => 'boolean'`):

```php
        'onboarding_completed_at' => 'datetime',
```

Add methods (near `isOnTrial()`):

```php
    /**
     * Workshop hali majburiy o'qitish siklini (mahsulot -> moshina -> savdo)
     * tugatmagan bo'lsa true.
     */
    public function isOnboarding(): bool
    {
        return $this->onboarding_step !== null;
    }

    /**
     * Onboarding siklini keyingi qadamga o'tkazadi. $nextStep null bo'lsa,
     * sikl yakunlangan deb belgilanadi (tabiiy yakun yoki "chiqish").
     */
    public function advanceOnboarding(?string $nextStep): void
    {
        $this->update([
            'onboarding_step' => $nextStep,
            'onboarding_completed_at' => $nextStep === null ? now() : null,
        ]);
    }
```

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate && php artisan test --filter WorkshopOnboardingStateTest`
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_09_090000_add_onboarding_fields_to_workshops_table.php app/Models/Workshop.php tests/Feature/WorkshopOnboardingStateTest.php
git commit -m "$(cat <<'EOF'
feat: add onboarding state fields to workshops

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 2: Ro'yxatdan o'tishda yangi workshop onboarding'ni boshlaydi

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php:47-56`
- Test: `tests/Feature/Auth/RegistrationTest.php`

**Interfaces:**
- Consumes: `Workshop::create()` (Task 1's `onboarding_step` fillable maydoni).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Auth/RegistrationTest.php` (inside the class):

```php
    public function test_a_new_workshop_starts_the_onboarding_cycle(): void
    {
        $this->post('/register', [
            'name' => 'Onboarding Test',
            'phone' => '+998901112244',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $workshop = \App\Models\Workshop::where('owner_name', 'Onboarding Test')->firstOrFail();

        $this->assertSame('products', $workshop->onboarding_step);
        $this->assertTrue($workshop->isOnboarding());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RegistrationTest`
Expected: FAIL — `onboarding_step` is null.

- [ ] **Step 3: Set the initial step on registration**

In `app/Http/Controllers/Auth/RegisteredUserController.php`, in the `Workshop::create([...])` call, add one line after `'is_active' => true,`:

```php
            'is_active' => true,
            'onboarding_step' => 'products',
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RegistrationTest`
Expected: PASS (3 tests, including the pre-existing 2)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/RegisteredUserController.php tests/Feature/Auth/RegistrationTest.php
git commit -m "$(cat <<'EOF'
feat: start onboarding cycle for newly registered workshops

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 3: `ProductCreationService`ga mahsulot yaratish mantiqini chiqarish (refaktor, xatti-harakat o'zgarmaydi)

Bu task **faqat kod ko'chirish** — `ProductController`dagi ikkita xususiy metod (`createProductWithInitialStock`, `linkToGlobalCatalog`) va `copyFromCatalog()`ning ichki tsikli yangi `App\Services\ProductCreationService`ga ko'chiriladi, shunda `OnboardingController` (Task 4) ham xuddi shu mantiqdan foydalana oladi. Mavjud `ProductGlobalCatalogSyncTest` xatti-harakat o'zgarmaganini tekshiradigan regressiya testi sifatida ishlatiladi.

**Files:**
- Create: `app/Services/ProductCreationService.php`
- Modify: `app/Services/GlobalProductMatcher.php` (yangi `catalogQuery()` metodi)
- Modify: `app/Http/Controllers/ProductController.php` (`store`, `bulkStore`, `catalog`, `copyFromCatalog`; ikkita xususiy metod olib tashlanadi)
- Test: `tests/Feature/ProductCreationServiceTest.php`

**Interfaces:**
- Produces: `ProductCreationService::createWithInitialStock(Workshop $workshop, User $user, array $validated): Product`; `ProductCreationService::copySelectionToWorkshop(Workshop $workshop, User $user, array $items, ?int $branchId): array{created: int, skipped: int}` — `$items` elementlari: `global_product_id`, `purchase_price`, `selling_price`, `stock_quantity` (ixtiyoriy), `min_stock_level` (ixtiyoriy). `GlobalProductMatcher::catalogQuery(?string $search, ?int $categoryId = null): \Illuminate\Database\Eloquent\Builder`.

- [ ] **Step 1: Write the failing test (new service, direct call)**

```php
<?php
// tests/Feature/ProductCreationServiceTest.php
namespace Tests\Feature;

use App\Models\GlobalProduct;
use App\Services\ProductCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class ProductCreationServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_copy_selection_to_workshop_creates_products_and_reports_counts(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $result = app(ProductCreationService::class)->copySelectionToWorkshop(
            $workshop,
            $user,
            [['global_product_id' => $globalProduct->id, 'purchase_price' => 0, 'selling_price' => 0, 'stock_quantity' => 0]],
            null
        );

        $this->assertSame(['created' => 1, 'skipped' => 0], $result);
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'global_product_id' => $globalProduct->id,
        ]);
    }

    public function test_copy_selection_to_workshop_skips_already_copied_products(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $workshop->products()->create([
            'global_product_id' => $globalProduct->id,
            'name' => $globalProduct->name,
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 0,
            'selling_price_uzs' => 0,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $result = app(ProductCreationService::class)->copySelectionToWorkshop(
            $workshop,
            $user,
            [['global_product_id' => $globalProduct->id, 'purchase_price' => 0, 'selling_price' => 0]],
            null
        );

        $this->assertSame(['created' => 0, 'skipped' => 1], $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ProductCreationServiceTest`
Expected: FAIL — class `App\Services\ProductCreationService` not found.

- [ ] **Step 3: Create `ProductCreationService`**

Move `createProductWithInitialStock()` (currently `app/Http/Controllers/ProductController.php:179-246`) and `linkToGlobalCatalog()` (`:256-277`) into the new service, and add `copySelectionToWorkshop()` built from the `copyFromCatalog()` loop (`:458-511`):

```php
<?php
// app/Services/ProductCreationService.php
namespace App\Services;

use App\Models\GlobalProduct;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Str;

/**
 * Mahsulot yaratishning umumiy yo'li — ProductController (qo'lda qo'shish,
 * ko'p qo'shish, katalogdan nusxalash) va OnboardingController (1-qadam)
 * o'rtasida umumiy: boshlang'ich qoldiq StockMovementService orqali qayd
 * qilinadi, xarid narxi ta'minotchiga qarz yoki Xarajat sifatida yoziladi,
 * mahsulot esa GlobalProductMatcher orqali global katalog bilan
 * moslashtiriladi/bog'lanadi.
 */
class ProductCreationService
{
    public function __construct(private GlobalProductMatcher $matcher)
    {
    }

    public function createWithInitialStock(Workshop $workshop, User $user, array $validated): Product
    {
        $product = $workshop->products()->create($validated);

        if (is_null($product->global_product_id)) {
            $this->linkToGlobalCatalog($product, $workshop);
        }

        if ($product->stock_quantity > 0 && $product->track_inventory) {
            $stockService = new StockMovementService();

            $stockService->recordIncoming(
                productId: $product->id,
                quantity: $product->stock_quantity,
                unitCostUsd: $product->purchase_price_usd,
                unitCostUzs: $product->purchase_price_uzs,
                currency: $product->currency,
                referenceType: 'InitialStock',
                referenceId: null,
                notes: "Boshlang'ich qoldiq"
            );

            InventoryTransaction::create([
                'workshop_id' => $workshop->id,
                'branch_id' => $product->branch_id,
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock_quantity,
                'quantity_before' => 0,
                'quantity_after' => $product->stock_quantity,
                'unit_price' => $product->getPurchasePrice(),
                'total_price' => $product->stock_quantity * $product->getPurchasePrice(),
                'reason' => 'Boshlang\'ich qoldiq',
                'transaction_date' => now(),
            ]);

            $totalCost = $product->stock_quantity * $product->getPurchasePrice();

            if ($product->supplier_id) {
                (new SupplierLedgerService())->recordPurchase(
                    workshopId: $workshop->id,
                    supplierId: $product->supplier_id,
                    amount: $totalCost,
                    currency: $product->currency,
                    description: "Mahsulot sotib olish: {$product->name} ({$product->stock_quantity} {$product->unit})",
                    referenceType: 'Product',
                    referenceId: $product->id,
                    userId: $user->id,
                );
            } else {
                $workshop->expenses()->create([
                    'branch_id' => $product->branch_id,
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => "Boshlang'ich qoldiq: {$product->stock_quantity} {$product->unit}",
                    'amount' => $totalCost,
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            }
        }

        return $product;
    }

    /**
     * Tanlangan global mahsulotlarni workshopga nusxa ko'chiradi. Allaqachon
     * nusxa olingan (global_product_id workshopda mavjud) elementlar
     * o'tkazib yuboriladi.
     *
     * @param  array<int, array{global_product_id: int, purchase_price?: float, selling_price?: float, stock_quantity?: int, min_stock_level?: int}>  $items
     * @return array{created: int, skipped: int}
     */
    public function copySelectionToWorkshop(Workshop $workshop, User $user, array $items, ?int $branchId): array
    {
        $alreadyCopied = $workshop->products()
            ->whereNotNull('global_product_id')
            ->pluck('global_product_id')
            ->all();

        $created = 0;
        $skipped = 0;

        $globalProducts = GlobalProduct::with('globalCategory')
            ->whereIn('id', collect($items)->pluck('global_product_id'))
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            if (in_array($item['global_product_id'], $alreadyCopied, true)) {
                $skipped++;
                continue;
            }

            $globalProduct = $globalProducts->get($item['global_product_id']);
            if (!$globalProduct) {
                continue;
            }

            $categoryId = null;
            if ($globalProduct->globalCategory) {
                $categoryName = $globalProduct->globalCategory->name;
                $categoryId = $workshop->categories()->firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => Str::slug($categoryName), 'is_active' => true]
                )->id;
            }

            $this->createWithInitialStock($workshop, $user, [
                'global_product_id' => $globalProduct->id,
                'category_id' => $categoryId,
                'name' => $globalProduct->name,
                'sku' => $globalProduct->sku,
                'description' => $globalProduct->description,
                'unit' => $globalProduct->unit,
                'barcode' => $globalProduct->barcode,
                'currency' => 'UZS',
                'purchase_price_uzs' => $item['purchase_price'] ?? 0,
                'selling_price_uzs' => $item['selling_price'] ?? 0,
                'purchase_price' => $item['purchase_price'] ?? 0,
                'selling_price' => $item['selling_price'] ?? 0,
                'stock_quantity' => $item['stock_quantity'] ?? 0,
                'min_stock_level' => $item['min_stock_level'] ?? 0,
                'is_active' => true,
                'track_inventory' => true,
                'branch_id' => $branchId,
            ]);

            $alreadyCopied[] = $globalProduct->id;
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function linkToGlobalCatalog(Product $product, Workshop $workshop): void
    {
        $globalProduct = $this->matcher->findOrCreateFor([
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'description' => $product->description,
            'category_name' => $product->category?->name,
        ]);

        $alreadyLinkedInWorkshop = $workshop->products()
            ->where('id', '!=', $product->id)
            ->where('global_product_id', $globalProduct->id)
            ->exists();

        if ($alreadyLinkedInWorkshop) {
            return;
        }

        $product->update(['global_product_id' => $globalProduct->id]);
    }
}
```

- [ ] **Step 4: Add `catalogQuery()` to `GlobalProductMatcher`**

In `app/Services/GlobalProductMatcher.php`, add `use Illuminate\Database\Eloquent\Builder;` to the imports, and add this method:

```php
    /**
     * Umumiy katalogdagi aktiv mahsulotlar bo'yicha (nomi/SKU) qidiruv
     * so'rovini quradi. `ProductController::catalog()` va
     * `OnboardingController::products()` ikkalasi ham shundan foydalanadi.
     */
    public function catalogQuery(?string $search, ?int $categoryId = null): Builder
    {
        $query = GlobalProduct::with('globalCategory')->where('is_active', true);

        if ($categoryId) {
            $query->where('global_category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name');
    }
```

- [ ] **Step 5: Refactor `ProductController` to use the new service**

Remove `createProductWithInitialStock()` (`:173-246`) and `linkToGlobalCatalog()` (`:248-277`) entirely.

Change `store()` signature and body (`:279-314`) — replace the method-injection signature and the call:

```php
    public function store(Request $request, ProductCreationService $productCreationService): RedirectResponse
```

```php
        DB::beginTransaction();
        try {
            $productCreationService->createWithInitialStock($workshop, $user, $validated);

            DB::commit();
```

Change `bulkStore()` signature and inner call (`:328-393`):

```php
    public function bulkStore(Request $request, ProductCreationService $productCreationService): RedirectResponse
```

```php
                $productCreationService->createWithInitialStock($workshop, $user, $productData);
```

Change `catalog()` (`:400-430`):

```php
    public function catalog(Request $request, GlobalProductMatcher $matcher): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $products = $matcher
            ->catalogQuery(
                $request->filled('search') ? $request->string('search')->toString() : null,
                $request->filled('category_id') ? $request->integer('category_id') : null
            )
            ->paginate(30)
            ->withQueryString();

        $copiedGlobalProductIds = $workshop->products()
            ->whereNotNull('global_product_id')
            ->pluck('global_product_id');

        return Inertia::render('Products/Catalog', [
            'products' => $products,
            'categories' => GlobalCategory::orderBy('name')->get(['id', 'name']),
            'copiedGlobalProductIds' => $copiedGlobalProductIds,
            'filters' => $request->only(['category_id', 'search']),
        ]);
    }
```

Change `copyFromCatalog()` (`:438-525`):

```php
    public function copyFromCatalog(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.global_product_id' => 'required|integer|exists:global_products,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.stock_quantity' => 'nullable|integer|min:0',
            'items.*.min_stock_level' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();
        $branchId = $user->canAccessAllBranches() ? ($request->input('branch_id') ?? $user->branch_id) : $user->branch_id;

        DB::beginTransaction();
        try {
            $result = $productCreationService->copySelectionToWorkshop($workshop, $user, $validated['items'], $branchId);

            DB::commit();

            $message = "{$result['created']} ta mahsulot qo'shildi!";
            if ($result['skipped'] > 0) {
                $message .= " ({$result['skipped']} tasi allaqachon qo'shilgan edi)";
            }

            return redirect()->route('products.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }
```

Add `use App\Services\ProductCreationService;` to the controller's imports. Remove `use Illuminate\Support\Str;` (no longer used in this file — only `Str::slug` in the moved code).

- [ ] **Step 6: Run tests to verify everything still passes**

Run: `php artisan test --filter "ProductCreationServiceTest|ProductGlobalCatalogSyncTest|ProductShowCarModelsTest"`
Expected: PASS — the pre-existing `ProductGlobalCatalogSyncTest` passing unchanged proves the refactor didn't change behavior.

- [ ] **Step 7: Commit**

```bash
git add app/Services/ProductCreationService.php app/Services/GlobalProductMatcher.php app/Http/Controllers/ProductController.php tests/Feature/ProductCreationServiceTest.php
git commit -m "$(cat <<'EOF'
refactor: extract product creation into ProductCreationService

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 4: Onboarding 1-qadam — mahsulot tanlash (backend + marshrutlar + Vue sahifasi)

**Files:**
- Create: `app/Http/Controllers/OnboardingController.php`
- Modify: `routes/web.php` (yangi `onboarding.*` marshrut guruhi)
- Create: `resources/js/Layouts/OnboardingLayout.vue`
- Create: `resources/js/Components/OnboardingExitLink.vue`
- Create: `resources/js/Pages/Onboarding/Products.vue`
- Test: `tests/Feature/OnboardingProductsStepTest.php`

**Interfaces:**
- Consumes: `ProductCreationService::copySelectionToWorkshop()`, `GlobalProductMatcher::catalogQuery()` (Task 3); `Workshop::advanceOnboarding()` (Task 1).
- Produces: marshrutlar `onboarding.products` (GET), `onboarding.products.store` (POST), `onboarding.skip` (POST); Vue komponentlari `OnboardingLayout.vue` (`step` propi bilan) va `OnboardingExitLink.vue` — Task 5/7 ham shulardan foydalanadi.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OnboardingProductsStepTest.php
namespace Tests\Feature;

use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingProductsStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_products_step_page_renders(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->get(route('onboarding.products'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Products'));
    }

    public function test_selecting_at_least_one_product_advances_to_the_vehicle_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [$globalProduct->id],
        ]);

        $response->assertRedirect(route('onboarding.vehicle'));
        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'global_product_id' => $globalProduct->id,
        ]);
    }

    public function test_submitting_zero_products_fails_validation(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [],
        ]);

        $response->assertSessionHasErrors('global_product_ids');
        $this->assertSame('products', $workshop->fresh()->onboarding_step);
    }

    public function test_skip_ends_onboarding_and_redirects_to_dashboard(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->post(route('onboarding.skip'));

        $response->assertRedirect(route('dashboard'));
        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertNotNull($fresh->onboarding_completed_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter OnboardingProductsStepTest`
Expected: FAIL — route `onboarding.products` not defined.

- [ ] **Step 3: Create `OnboardingController`**

```php
<?php
// app/Http/Controllers/OnboardingController.php
namespace App\Http\Controllers;

use App\Services\GlobalProductMatcher;
use App\Services\ProductCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function products(Request $request, GlobalProductMatcher $matcher): Response
    {
        $search = $request->filled('search') ? $request->string('search')->toString() : null;

        return Inertia::render('Onboarding/Products', [
            'products' => $matcher->catalogQuery($search)->get(['id', 'name', 'unit']),
        ]);
    }

    public function storeProducts(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'global_product_ids' => 'required|array|min:1',
            'global_product_ids.*' => 'integer|exists:global_products,id',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $items = collect($validated['global_product_ids'])->map(fn ($id) => [
            'global_product_id' => $id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'stock_quantity' => 0,
            'min_stock_level' => 0,
        ])->all();

        DB::beginTransaction();
        try {
            $productCreationService->copySelectionToWorkshop($workshop, $user, $items, $user->branch_id);
            $workshop->advanceOnboarding('vehicle');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
        }

        return redirect()->route('onboarding.vehicle');
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->currentWorkshop()->advanceOnboarding(null);

        return redirect()->route('dashboard');
    }
}
```

- [ ] **Step 4: Register routes**

In `routes/web.php`, immediately inside `Route::middleware('auth')->group(function () {` (right after line 64, before the `workshops/switch` block), add:

```php
    // Yangi workshop uchun majburiy o'qitish sikli
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/products', [OnboardingController::class, 'products'])->name('products');
        Route::post('/products', [OnboardingController::class, 'storeProducts'])->name('products.store');
        Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });
```

Add `use App\Http\Controllers\OnboardingController;` to the imports at the top of the file.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter OnboardingProductsStepTest`
Expected: PASS (4 tests)

- [ ] **Step 6: Create the frontend shell components and page**

```vue
<!-- resources/js/Components/OnboardingExitLink.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({});

const exit = () => {
    if (confirm("O'qitish rejimidan chiqmoqchimisiz?")) {
        form.post(route('onboarding.skip'));
    }
};
</script>

<template>
    <button
        type="button"
        @click="exit"
        class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
    >
        O'qitish rejimidan chiqish
    </button>
</template>
```

```vue
<!-- resources/js/Layouts/OnboardingLayout.vue -->
<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import OnboardingExitLink from '@/Components/OnboardingExitLink.vue';

defineProps({
    step: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <ApplicationLogo class="h-16 w-16 fill-current text-gray-500" />
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ step }}/3</span>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-md sm:max-w-lg sm:rounded-lg dark:bg-gray-800">
            <slot />
        </div>

        <div class="mt-4">
            <OnboardingExitLink />
        </div>
    </div>
</template>
```

```vue
<!-- resources/js/Pages/Onboarding/Products.vue -->
<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    products: Array,
});

const selected = reactive(new Set());

const toggle = (id) => {
    if (selected.has(id)) {
        selected.delete(id);
    } else {
        selected.add(id);
    }
};

const selectedCount = computed(() => selected.size);

const form = useForm({ global_product_ids: [] });

const submit = () => {
    form.transform(() => ({ global_product_ids: Array.from(selected) }))
        .post(route('onboarding.products.store'));
};
</script>

<template>
    <Head title="Mahsulot tanlash" />

    <OnboardingLayout :step="1">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Kerakli mahsulotlarni tanlang
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Umumiy katalogdan kamida 1 ta mahsulot tanlang — narx va qoldiqni keyinroq to'ldirasiz.
        </p>

        <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ form.errors.error }}
        </div>

        <div v-if="products.length === 0" class="mb-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
            Katalogda hozircha mahsulot yo'q.
        </div>

        <ul class="mb-4 max-h-96 divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
            <li v-for="product in products" :key="product.id" class="flex items-center gap-3 py-2">
                <input
                    :id="`product-${product.id}`"
                    type="checkbox"
                    :checked="selected.has(product.id)"
                    @change="toggle(product.id)"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label :for="`product-${product.id}`" class="text-sm text-gray-700 dark:text-gray-300">
                    {{ product.name }} <span class="text-gray-400">({{ product.unit }})</span>
                </label>
            </li>
        </ul>

        <button
            type="button"
            :disabled="selectedCount === 0 || form.processing"
            @click="submit"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Davom etish ({{ selectedCount }} ta tanlandi)
        </button>
    </OnboardingLayout>
</template>
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OnboardingController.php routes/web.php resources/js/Layouts/OnboardingLayout.vue resources/js/Components/OnboardingExitLink.vue resources/js/Pages/Onboarding/Products.vue tests/Feature/OnboardingProductsStepTest.php
git commit -m "$(cat <<'EOF'
feat: add onboarding step 1 - product selection

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 5: Onboarding 2-qadam — mijoz + moshina qo'shish

**Files:**
- Modify: `app/Http/Controllers/OnboardingController.php` (yangi `vehicle()`, `storeVehicle()`)
- Modify: `routes/web.php` (`onboarding.vehicle`, `onboarding.vehicle.store`)
- Create: `resources/js/Pages/Onboarding/Vehicle.vue`
- Test: `tests/Feature/OnboardingVehicleStepTest.php`

**Interfaces:**
- Consumes: `OnboardingLayout.vue`, `Workshop::advanceOnboarding()` (Task 1/4).
- Produces: marshrutlar `onboarding.vehicle` (GET), `onboarding.vehicle.store` (POST) — muvaffaqiyatli submit `vehicles.show`ga redirect qiladi.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OnboardingVehicleStepTest.php
namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingVehicleStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_vehicle_step_page_renders(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->get(route('onboarding.vehicle'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Vehicle'));
    }

    public function test_creating_a_client_and_vehicle_advances_to_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ]);

        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $response->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertSame('sale', $workshop->fresh()->onboarding_step);
        $this->assertSame('Aziz Karimov', $vehicle->client->name);
    }

    public function test_missing_plate_number_fails_validation(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'make' => 'Nexia',
        ]);

        $response->assertSessionHasErrors('plate_number');
        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter OnboardingVehicleStepTest`
Expected: FAIL — route `onboarding.vehicle` not defined.

- [ ] **Step 3: Add controller methods**

In `app/Http/Controllers/OnboardingController.php`, add `use App\Models\Vehicle;` to the imports, and append these two methods to the class:

```php
    public function vehicle(): Response
    {
        return Inertia::render('Onboarding/Vehicle');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'make' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $client = $workshop->clients()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'branch_id' => $user->branch_id,
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate_number' => $validated['plate_number'],
            'make' => $validated['make'],
        ]);

        $workshop->advanceOnboarding('sale');

        return redirect()->route('vehicles.show', $vehicle);
    }
```

- [ ] **Step 4: Register routes**

In `routes/web.php`, inside the `onboarding.` route group added in Task 4, add two lines after `products.store`:

```php
        Route::get('/vehicle', [OnboardingController::class, 'vehicle'])->name('vehicle');
        Route::post('/vehicle', [OnboardingController::class, 'storeVehicle'])->name('vehicle.store');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter OnboardingVehicleStepTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Create the frontend page**

```vue
<!-- resources/js/Pages/Onboarding/Vehicle.vue -->
<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    phone: '',
    plate_number: '',
    make: '',
});

const submit = () => {
    form.post(route('onboarding.vehicle.store'));
};
</script>

<template>
    <Head title="Mijoz va moshina qo'shish" />

    <OnboardingLayout :step="2">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Mijoz va moshina qo'shing
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Birinchi mijozingizni va uning moshinasini kiriting.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mijoz ismi</label>
                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Davlat raqami</label>
                <input v-model="form.plate_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.plate_number" class="mt-1 text-sm text-red-600">{{ form.errors.plate_number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moshina modeli</label>
                <input v-model="form.make" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.make" class="mt-1 text-sm text-red-600">{{ form.errors.make }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Davom etish
            </button>
        </form>
    </OnboardingLayout>
</template>
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OnboardingController.php routes/web.php resources/js/Pages/Onboarding/Vehicle.vue tests/Feature/OnboardingVehicleStepTest.php
git commit -m "$(cat <<'EOF'
feat: add onboarding step 2 - client and vehicle creation

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 6: `EnsureOnboardingComplete` gating middleware

**Files:**
- Create: `app/Http/Middleware/EnsureOnboardingComplete.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/OnboardingGateTest.php`

**Interfaces:**
- Consumes: `Workshop::isOnboarding()`, `$workshop->onboarding_step` (Task 1); marshrutlar `onboarding.products`, `onboarding.vehicle`, `onboarding.skip` (Task 4/5); mavjud `vehicles.show`, `service-logs.store`, `logout` marshrutlari.
- Muhim invariant: `sale` bosqichida middleware `onboarding.*`, `vehicles.create/store/edit/update/destroy` kabi barcha boshqa marshrutlarni to'sib qo'yganligi sababli, workshopda **aynan bitta** moshina mavjud bo'ladi (2-qadamda yaratilgan) — shuning uchun middleware uni to'g'ridan-to'g'ri qidirib topa oladi, alohida "onboarding vehicle id" saqlash shart emas.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OnboardingGateTest.php
namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingGateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_dashboard_redirects_to_the_products_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.products'));
    }

    public function test_dashboard_redirects_to_the_vehicle_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.vehicle'));
    }

    public function test_dashboard_redirects_to_the_onboarding_vehicles_show_page_on_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A123AA', 'make' => 'Chevrolet']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('vehicles.show', $vehicle));
    }

    public function test_the_current_step_route_stays_reachable(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get(route('onboarding.products'))->assertOk();
    }

    public function test_cannot_skip_ahead_to_a_later_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get(route('onboarding.vehicle'))->assertRedirect(route('onboarding.products'));
    }

    public function test_cannot_go_back_to_an_earlier_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $this->actingAs($user)->get(route('onboarding.products'))->assertRedirect(route('onboarding.vehicle'));
    }

    public function test_skip_route_is_always_reachable(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->post(route('onboarding.skip'))->assertRedirect(route('dashboard'));
    }

    public function test_a_workshop_that_finished_onboarding_is_never_redirected(): void
    {
        [$user] = $this->createDirectorWithWorkshop();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter OnboardingGateTest`
Expected: FAIL — no redirect happens (middleware doesn't exist yet).

- [ ] **Step 3: Create the middleware**

```php
<?php
// app/Http/Middleware/EnsureOnboardingComplete.php
namespace App\Http\Middleware;

use App\Models\Vehicle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Onboarding hali tugamagan workshoplarni joriy qadamiga qayta yo'naltiradi.
 * Faqat joriy qadamning o'z marshruti (va universal logout/skip) ochiq
 * qoladi — na oldinga, na orqaga o'tib bo'lmaydi.
 */
class EnsureOnboardingComplete
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $workshop = $user->currentWorkshop();

        if (!$workshop || !$workshop->isOnboarding()) {
            return $next($request);
        }

        if ($request->routeIs('logout') || $request->routeIs('onboarding.skip')) {
            return $next($request);
        }

        if ($workshop->onboarding_step === 'sale') {
            if ($request->routeIs('vehicles.show') || $request->routeIs('service-logs.store')) {
                return $next($request);
            }

            $vehicle = Vehicle::whereHas('client', fn ($q) => $q->where('workshop_id', $workshop->id))->first();

            return $vehicle
                ? redirect()->route('vehicles.show', $vehicle)
                : redirect()->route('onboarding.vehicle');
        }

        $currentStepPattern = $workshop->onboarding_step === 'vehicle' ? 'onboarding.vehicle*' : 'onboarding.products*';

        if ($request->routeIs($currentStepPattern)) {
            return $next($request);
        }

        return redirect()->route($workshop->onboarding_step === 'vehicle' ? 'onboarding.vehicle' : 'onboarding.products');
    }
}
```

- [ ] **Step 4: Register the middleware**

In `bootstrap/app.php`, add one line to the `$middleware->web(append: [...])` array, after `CheckSubscription::class,`:

```php
            \App\Http\Middleware\EnsureOnboardingComplete::class,
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter OnboardingGateTest`
Expected: PASS (8 tests)

- [ ] **Step 6: Run the full test suite to check for regressions**

Run: `php artisan test`
Expected: PASS — no pre-existing test should now fail (workshops created via `CreatesTestWorkshop` have `onboarding_step = null`, so the middleware is a no-op for them).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/EnsureOnboardingComplete.php bootstrap/app.php tests/Feature/OnboardingGateTest.php
git commit -m "$(cat <<'EOF'
feat: gate the app behind the onboarding cycle until it is complete

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 7: Onboarding 3-qadam — haqiqiy moshina sahifasida spotlight va sikl yakuni

**Files:**
- Modify: `app/Http/Controllers/VehicleController.php:186-191` (`show()`)
- Modify: `resources/js/Pages/Vehicles/Show.vue`
- Modify: `app/Http/Controllers/ServiceLogController.php` (`store()`)
- Test: `tests/Feature/OnboardingSaleStepTest.php`

**Interfaces:**
- Consumes: `Workshop::advanceOnboarding()` (Task 1), `OnboardingExitLink.vue` (Task 4).
- Produces: `VehicleController::show()` javobiga `isOnboardingHighlight: bool` propi qo'shiladi; `ServiceLogController::store()` onboarding `sale` bosqichida yakunlangach `dashboard`ga redirect qiladi (aks holda avvalgidek `vehicles.show`ga).

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/OnboardingSaleStepTest.php
namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingSaleStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    private function createOnboardingVehicle($workshop): Vehicle
    {
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);

        return Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A777AA', 'make' => 'Nexia']);
    }

    public function test_vehicle_show_page_is_highlighted_during_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->actingAs($user)->get(route('vehicles.show', $vehicle));

        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', true));
    }

    public function test_vehicle_show_page_is_not_highlighted_outside_onboarding(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A888AA', 'make' => 'Nexia']);

        $response = $this->actingAs($user)->get(route('vehicles.show', $vehicle));

        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', false));
    }

    public function test_completing_a_sale_during_onboarding_finishes_the_cycle_and_redirects_to_dashboard(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => "Ish haqi", 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('dashboard'));
        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertNotNull($fresh->onboarding_completed_at);
    }

    public function test_a_normal_sale_outside_onboarding_still_redirects_to_the_vehicle_page(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A999AA', 'make' => 'Nexia']);

        $response = $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => "Ish haqi", 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('vehicles.show', $vehicle->id));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter OnboardingSaleStepTest`
Expected: FAIL — `isOnboardingHighlight` prop missing; sale always redirects to `vehicles.show`.

- [ ] **Step 3: Add the highlight prop to `VehicleController::show()`**

In `app/Http/Controllers/VehicleController.php`, change the `Inertia::render('Vehicles/Show', [...])` call (around line 186) to add one entry:

```php
        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'products' => $products,
            'carModelInfo' => $this->buildCarModelInfo($vehicle, $workshop, $user),
            'isOnboardingHighlight' => $workshop->onboarding_step === 'sale',
        ]);
```

- [ ] **Step 4: Mark onboarding complete in `ServiceLogController::store()`**

In `app/Http/Controllers/ServiceLogController.php`, right after `$user = $request->user();` (line 140), add:

```php
        $workshop = $user->currentWorkshop();
        $wasOnboarding = $workshop->onboarding_step === 'sale';
```

Right before `DB::commit();` (line 246), add:

```php
            if ($wasOnboarding) {
                $workshop->advanceOnboarding(null);
            }

```

Replace the final return (lines 263-264):

```php
            if ($wasOnboarding) {
                return redirect()->route('dashboard')
                    ->with('success', "Tabriklaymiz! Birinchi savdongiz muvaffaqiyatli yakunlandi.");
            }

            return redirect()->route('vehicles.show', $vehicle->id)
                ->with('success', 'Servis yozuvi muvaffaqiyatli qo\'shildi!');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter OnboardingSaleStepTest`
Expected: PASS (4 tests)

- [ ] **Step 6: Add the spotlight UI to `Vehicles/Show.vue`**

Add `isOnboardingHighlight: Boolean` to `defineProps` (`resources/js/Pages/Vehicles/Show.vue:8-12`):

```js
const props = defineProps({
    vehicle: Object,
    products: Array,
    carModelInfo: Object,
    isOnboardingHighlight: Boolean,
});
```

Add the import at the top (after the `Multiselect` CSS import, line 6):

```js
import OnboardingExitLink from '@/Components/OnboardingExitLink.vue';
```

Replace the sale-button block (`:236-244`):

```vue
                <!-- Onboarding: 3/3 ko'rsatma -->
                <div v-if="isOnboardingHighlight" class="mb-3 flex items-center justify-between rounded-md bg-indigo-50 p-3 dark:bg-indigo-900/20">
                    <p class="text-sm text-indigo-800 dark:text-indigo-200">
                        3/3: Endi quyidagi tugmani bosib birinchi savdoingizni amalga oshiring.
                    </p>
                    <OnboardingExitLink />
                </div>

                <!-- Savdo tugmasi -->
                <div class="mb-6">
                    <button
                        @click="toggleSaleForm"
                        :class="[
                            'w-full rounded-md bg-green-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-green-500',
                            isOnboardingHighlight && !showSaleForm ? 'animate-pulse ring-4 ring-green-300 ring-offset-2' : '',
                        ]"
                    >
                        {{ showSaleForm ? '✕ Yopish' : '🛒 Savdo qilish' }}
                    </button>
                </div>
```

- [ ] **Step 7: Run the full test suite to check for regressions**

Run: `php artisan test`
Expected: PASS — all previous tests still green.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/VehicleController.php app/Http/Controllers/ServiceLogController.php resources/js/Pages/Vehicles/Show.vue tests/Feature/OnboardingSaleStepTest.php
git commit -m "$(cat <<'EOF'
feat: add onboarding step 3 - sale spotlight and cycle completion

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 8: To'liq sikl integratsion testi

Bu task yangi kod qo'shmaydi — barcha oldingi tasklar to'g'ri birga ishlashini boshidan oxirigacha (ro'yxatdan o'tish → 3 qadam → dashboard) bitta testda tasdiqlaydi.

**Files:**
- Test: `tests/Feature/OnboardingFullCycleTest.php`

**Interfaces:**
- Consumes: Task 1-7'ning barcha marshrut/model interfeyslari.

- [ ] **Step 1: Write the test**

```php
<?php
// tests/Feature/OnboardingFullCycleTest.php
namespace Tests\Feature;

use App\Models\GlobalProduct;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFullCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_registered_workshop_completes_the_full_onboarding_cycle(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->post('/register', [
            'name' => 'Full Cycle Test',
            'phone' => '+998907778899',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = \App\Models\User::where('phone', '+998907778899')->firstOrFail();
        $workshop = \App\Models\Workshop::where('user_id', $user->id)->firstOrFail();

        $this->get('/dashboard')->assertRedirect(route('onboarding.products'));

        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $this->post(route('onboarding.products.store'), ['global_product_ids' => [$globalProduct->id]])
            ->assertRedirect(route('onboarding.vehicle'));

        $this->get('/dashboard')->assertRedirect(route('onboarding.vehicle'));

        $vehicleResponse = $this->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ]);
        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $vehicleResponse->assertRedirect(route('vehicles.show', $vehicle));

        $this->get('/dashboard')->assertRedirect(route('vehicles.show', $vehicle));

        $this->get(route('vehicles.show', $vehicle))
            ->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', true));

        $saleResponse = $this->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => 'Ish haqi', 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);
        $saleResponse->assertRedirect(route('dashboard'));

        $this->assertNull($workshop->fresh()->onboarding_step);
        $this->assertNotNull($workshop->fresh()->onboarding_completed_at);
        $this->get('/dashboard')->assertOk();
    }

    public function test_a_pre_existing_workshop_never_sees_onboarding(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user = \App\Models\User::factory()->create();
        $user->assignRole('director');
        \App\Models\Workshop::create([
            'user_id' => $user->id,
            'name' => 'Old Workshop',
            'phone' => '+998901112233',
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --filter OnboardingFullCycleTest`
Expected: PASS (2 tests). If any step fails, use `superpowers:systematic-debugging` to trace which task's interface doesn't match — don't patch symptoms.

- [ ] **Step 3: Run the entire suite one more time**

Run: `php artisan test`
Expected: PASS — full green suite.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/OnboardingFullCycleTest.php
git commit -m "$(cat <<'EOF'
test: add end-to-end coverage for the full onboarding cycle

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01XYCjGZLQ9RFF3wvMBSfwJF
EOF
)"
```

---

## Task 9: Qo'lda tekshirish (dev serverda)

Kod yozilmaydi — bu UI/UX'ni haqiqiy brauzerda tasdiqlash uchun yakuniy tekshiruv (Vue sahifalari uchun avtomatik test frameworki yo'q).

- [ ] **Step 1: Start the dev server**

Run: `npm run dev` (alohida terminalda, fon rejimida) va `php artisan serve` (yoki mavjud local domenni ishlating).

- [ ] **Step 2: Fresh registration walkthrough**

1. `/register`da yangi telefon raqami bilan ro'yxatdan o'ting.
2. Darhol `/onboarding/products`ga tushishini tasdiqlang — sahifada sidebar/menyu **yo'qligini**, faqat logo + "1/3" + ro'yxat + "Davom etish" (o'chirilgan) + "Chiqish" havolasi borligini tekshiring.
3. Hech narsa tanlamasdan "Davom etish"ning o'chirilganini tasdiqlang; 1 ta mahsulot belgilab yoqilishini tekshiring.
4. Yuboring — `/onboarding/vehicle`ga o'tishini, "2/3" ko'rsatilishini tekshiring.
5. Mijoz+moshina formasi to'ldiring, yuboring — haqiqiy moshina sahifasiga (`/vehicles/{id}`) o'tishini, **to'liq oddiy dashboard chrome bilan** (sidebar bor) ochilishini, "Savdo qilish" tugmasi atrofida pulse effekti borligini va tepada ko'rsatma + "Chiqish" havolasi ko'rinishini tasdiqlang.
6. "Savdo qilish"ni bosib, oddiy (masalan qo'lda kiritilgan) xizmat yozib yuboring — muvaffaqiyatli saqlangach `/dashboard`ga o'tishini va endi to'liq dashboard ochilishini tasdiqlang.
7. Sahifani yangilab (`F5`) yoki qayta login qilib, endi `/dashboard`ga to'sqinliksiz kirishini tasdiqlang.

- [ ] **Step 3: Skip walkthrough**

Yangi boshqa raqam bilan yana ro'yxatdan o'ting, 1-qadamda "Chiqish"ni bosing — darhol `/dashboard`ga o'tishini va qayta login qilganda ham onboarding qayta chiqmasligini tasdiqlang.

- [ ] **Step 4: Report results**

Agar biror qadam kutilganidek ishlamasa, aniq nima ro'y berganini yozib, tegishli taskka qaytib tuzating (root-cause bo'yicha, symptom emas).
