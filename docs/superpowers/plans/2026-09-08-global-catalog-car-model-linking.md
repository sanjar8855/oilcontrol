# Global katalog — avtomobil modeli mosligi va avto-sinxronizatsiya Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Avtomobil modeli ↔ mahsulot mosligini workshop-local darajadan global katalog darajasiga ko'chirish, va tadbirkor to'g'ridan-to'g'ri qo'shgan yangi mahsulotlarni avtomatik global katalog bilan moslashtirish/qo'shish.

**Architecture:** Yangi `car_model_global_products` pivot jadvali `GlobalProduct` ↔ `CarModel`ni bog'laydi. `Product::effectiveCarModels()` global bog'lanishni (agar mavjud bo'lsa) yoki eski lokal pivotni (fallback) qaytaradi. Yangi `GlobalProductMatcher` servisi mahsulot yaratilganda barcode → SKU → nom bo'yicha mos `GlobalProduct`ni topadi yoki yaratadi.

**Tech Stack:** Laravel 12 (PHP 8.2), Inertia + Vue 3, Spatie Permission, PHPUnit 11 (sqlite `:memory:` test bazasi), MySQL production baza.

**Spec:** `docs/superpowers/specs/2026-09-08-global-catalog-car-model-linking-design.md`

## Global Constraints

- Test bazasi har doim sqlite `:memory:` (`phpunit.xml`) — production MySQL bazaga (`sql_oilcontrol`) hech qanday test yoki backfill buyrug'i to'g'ridan-to'g'ri tegmasligi kerak; production'ga qo'llashdan oldin zaxira nusxa olinadi (buyruq qo'lda, ehtiyotkorlik bilan ishga tushiriladi).
- `products` jadvalida `unique(workshop_id, global_product_id)` mavjud — matcher integratsiyasi bu cheklovni buzmasligi kerak (spec addendum, 2026-09-08).
- Eski `car_model_products` jadvali va uning UI'si (`CarMakeController::attachProduct/updateProductQuantity/detachProduct`) o'chirilmaydi — faqat `global_product_id`i `null` bo'lgan mahsulotlar uchun fallback sifatida qoladi.
- Barcha yangi PHP kod loyihaning mavjud uslubiga mos (Uzbek izohlar faqat "nega" tushunarsiz bo'lgan joyda, `test_snake_case` test metod nomlari, `RefreshDatabase` trait).

---

## Task 1: `car_model_global_products` pivot jadvali

**Files:**
- Create: `database/migrations/2026_09_08_120000_create_car_model_global_products_table.php`
- Test: `tests/Feature/CarModelGlobalProductsSchemaTest.php`

**Interfaces:**
- Produces: `car_model_global_products` jadvali — ustunlar `car_model_id` (FK→car_models), `global_product_id` (FK→global_products), `quantity` (decimal 8,2, default 1), `timestamps`; unique(`car_model_id`, `global_product_id`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarModelGlobalProductsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_model_global_products_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('car_model_global_products'));
        $this->assertTrue(Schema::hasColumns('car_model_global_products', [
            'id', 'car_model_id', 'global_product_id', 'quantity', 'created_at', 'updated_at',
        ]));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CarModelGlobalProductsSchemaTest`
Expected: FAIL — table `car_model_global_products` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_model_global_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('global_product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->timestamps();

            $table->unique(['car_model_id', 'global_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_global_products');
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CarModelGlobalProductsSchemaTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_08_120000_create_car_model_global_products_table.php tests/Feature/CarModelGlobalProductsSchemaTest.php
git commit -m "feat: add car_model_global_products pivot table"
```

---

## Task 2: Model relationships — `GlobalProduct::carModels()`, `CarModel::globalProducts()`, `Product::localCarModels()` / `effectiveCarModels()`

**Files:**
- Modify: `app/Models/GlobalProduct.php`
- Modify: `app/Models/CarModel.php`
- Modify: `app/Models/Product.php:108-121` (rename `carModels()`)
- Create: `tests/Concerns/CreatesTestWorkshop.php` (shared test helper, used by every feature test from here on)
- Test: `tests/Unit/Models/ProductEffectiveCarModelsTest.php`

**Interfaces:**
- Produces: `GlobalProduct::carModels(): BelongsToMany` (pivot `car_model_global_products`, `withPivot('quantity')`).
- Produces: `CarModel::globalProducts(): BelongsToMany` (same pivot, inverse side).
- Produces: `Product::localCarModels(): BelongsToMany` (renamed from `carModels()`, same old pivot `car_model_products`, unchanged behavior).
- Produces: `Product::effectiveCarModels(): \Illuminate\Database\Eloquent\Collection` — returns `globalProduct->carModels` when `global_product_id` is set, else `localCarModels`.
- Produces: `Tests\Concerns\CreatesTestWorkshop::createDirectorWithWorkshop(): array` returns `[User $director, Workshop $workshop]`; `::createSuperAdmin(): User`.

- [ ] **Step 1: Create the shared test helper trait**

```php
<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RolePermissionSeeder;

trait CreatesTestWorkshop
{
    /**
     * @return array{0: User, 1: Workshop}
     */
    protected function createDirectorWithWorkshop(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('director');

        $workshop = Workshop::create([
            'user_id' => $user->id,
            'name' => 'Test Workshop',
            'phone' => '+998901234567',
        ]);

        return [$user, $workshop];
    }

    protected function createSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
```

This trait has no test of its own — it's exercised by every test that uses it starting with Step 2 below.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Unit\Models;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class ProductEffectiveCarModelsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_effective_car_models_reads_from_global_product_when_linked(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();

        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);

        $globalProduct = GlobalProduct::create([
            'global_category_id' => GlobalCategory::create(['name' => 'Motor moylari'])->id,
            'name' => 'Motor moyi 5W-30',
            'unit' => 'litr',
            'is_active' => true,
        ]);
        $globalProduct->carModels()->attach($model->id, ['quantity' => 3.5]);

        $product = $workshop->products()->create([
            'global_product_id' => $globalProduct->id,
            'name' => 'Motor moyi 5W-30',
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 50000,
            'selling_price_uzs' => 70000,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $effective = $product->effectiveCarModels();

        $this->assertCount(1, $effective);
        $this->assertSame($model->id, $effective->first()->id);
        $this->assertEquals(3.5, $effective->first()->pivot->quantity);
    }

    public function test_effective_car_models_falls_back_to_local_pivot_when_not_linked(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();

        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);

        $product = $workshop->products()->create([
            'name' => 'Shina damlash xizmati',
            'unit' => 'dona',
            'currency' => 'UZS',
            'purchase_price_uzs' => 0,
            'selling_price_uzs' => 10000,
            'is_active' => true,
            'track_inventory' => false,
        ]);
        $product->localCarModels()->attach($model->id, ['quantity' => 1]);

        $effective = $product->effectiveCarModels();

        $this->assertCount(1, $effective);
        $this->assertSame($model->id, $effective->first()->id);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=ProductEffectiveCarModelsTest`
Expected: FAIL — `Call to undefined method App\Models\GlobalProduct::carModels()` (and `localCarModels` undefined).

- [ ] **Step 4: Add the relation to `GlobalProduct`**

In `app/Models/GlobalProduct.php`, add the `BelongsToMany` import and a new relation method:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

```php
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_global_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
```

- [ ] **Step 5: Add the inverse relation to `CarModel`**

In `app/Models/CarModel.php` (already imports `BelongsToMany`), add:

```php
    public function globalProducts(): BelongsToMany
    {
        return $this->belongsToMany(GlobalProduct::class, 'car_model_global_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
```

- [ ] **Step 6: Rename `Product::carModels()` to `localCarModels()` and add `effectiveCarModels()`**

In `app/Models/Product.php`, add the import:

```php
use Illuminate\Database\Eloquent\Collection;
```

Replace:

```php
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
```

with:

```php
    public function localCarModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Global katalogga bog'langan bo'lsa moshina mosligi global mahsulotdan
     * meros olinadi; aks holda eski lokal bog'lanish ishlatiladi (fallback,
     * hozircha faqat backfill qilinmagan mahsulotlar uchun qoladi).
     */
    public function effectiveCarModels(): Collection
    {
        if ($this->global_product_id) {
            return $this->globalProduct?->carModels ?? new Collection();
        }

        return $this->localCarModels;
    }
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=ProductEffectiveCarModelsTest`
Expected: PASS

- [ ] **Step 8: Fix the two remaining call sites of the renamed relation**

`app/Http/Controllers/ProductController.php:189` (inside `createProductWithInitialStock`) and `database/seeders/ProductSeeder.php:191,226` call `$product->carModels()`. Task 4 rewrites `createProductWithInitialStock` entirely (the `car_models` request field is removed there), so only fix the seeder now:

In `database/seeders/ProductSeeder.php`, replace both occurrences of `$product->carModels()->sync(` with `$product->localCarModels()->sync(`.

- [ ] **Step 9: Verify the seeder still runs**

Run: `php artisan db:seed --class=ProductSeeder --env=testing` is not meaningful against sqlite `:memory:` (it resets per-process); instead just run static analysis by grepping for leftover references:

Run: `grep -rn "\->carModels(" app/ database/`
Expected: no output (only `CarMake::carModels()` / `CarModel` static helpers using the unrelated `carModels` relation name on `CarMake` remain — verify by eye that any hits are `CarMake::carModels()`, not `Product::carModels()`).

- [ ] **Step 10: Commit**

```bash
git add app/Models/GlobalProduct.php app/Models/CarModel.php app/Models/Product.php database/seeders/ProductSeeder.php tests/Concerns/CreatesTestWorkshop.php tests/Unit/Models/ProductEffectiveCarModelsTest.php
git commit -m "refactor: move car-model compatibility relation to global catalog level"
```

---

## Task 3: `GlobalProductMatcher` service

**Files:**
- Create: `app/Services/GlobalProductMatcher.php`
- Test: `tests/Unit/Services/GlobalProductMatcherTest.php`

**Interfaces:**
- Consumes: none beyond `App\Models\GlobalProduct`, `App\Models\GlobalCategory`.
- Produces: `GlobalProductMatcher::findOrCreateFor(array $data): GlobalProduct`. `$data` keys: `name` (string, required), `sku` (?string), `barcode` (?string), `unit` (string), `description` (?string), `category_name` (?string).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use App\Services\GlobalProductMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalProductMatcherTest extends TestCase
{
    use RefreshDatabase;

    private GlobalProductMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new GlobalProductMatcher();
    }

    public function test_it_matches_by_barcode_first(): void
    {
        $existing = GlobalProduct::create([
            'name' => 'Motor moyi 5W-30 (eski nom)',
            'unit' => 'litr',
            'barcode' => '4870001112223',
            'is_active' => true,
        ]);

        $result = $this->matcher->findOrCreateFor([
            'name' => 'Butunlay boshqa nom',
            'sku' => null,
            'barcode' => '4870001112223',
            'unit' => 'litr',
            'description' => null,
            'category_name' => null,
        ]);

        $this->assertSame($existing->id, $result->id);
        $this->assertSame(1, GlobalProduct::count());
    }

    public function test_it_matches_by_sku_when_barcode_absent(): void
    {
        $existing = GlobalProduct::create([
            'name' => 'Moy filtri',
            'unit' => 'dona',
            'sku' => 'OF-1234',
            'is_active' => true,
        ]);

        $result = $this->matcher->findOrCreateFor([
            'name' => 'Boshqacha nom',
            'sku' => 'OF-1234',
            'barcode' => null,
            'unit' => 'dona',
            'description' => null,
            'category_name' => null,
        ]);

        $this->assertSame($existing->id, $result->id);
    }

    public function test_it_matches_by_normalized_name_when_no_barcode_or_sku(): void
    {
        $existing = GlobalProduct::create([
            'name' => 'Azot damlash xizmati',
            'unit' => 'dona',
            'is_active' => true,
        ]);

        $result = $this->matcher->findOrCreateFor([
            'name' => '  azot damlash xizmati  ',
            'sku' => null,
            'barcode' => null,
            'unit' => 'dona',
            'description' => null,
            'category_name' => null,
        ]);

        $this->assertSame($existing->id, $result->id);
    }

    public function test_it_creates_a_new_global_product_when_nothing_matches(): void
    {
        $result = $this->matcher->findOrCreateFor([
            'name' => 'Moyka xizmati',
            'sku' => null,
            'barcode' => null,
            'unit' => 'dona',
            'description' => 'Tashqi yuvish',
            'category_name' => 'Xizmatlar',
        ]);

        $this->assertDatabaseHas('global_products', [
            'id' => $result->id,
            'name' => 'Moyka xizmati',
        ]);
        $this->assertSame('Xizmatlar', GlobalCategory::find($result->global_category_id)->name);
    }

    public function test_it_creates_without_category_when_category_name_is_null(): void
    {
        $result = $this->matcher->findOrCreateFor([
            'name' => 'Noma\'lum mahsulot',
            'sku' => null,
            'barcode' => null,
            'unit' => 'dona',
            'description' => null,
            'category_name' => null,
        ]);

        $this->assertNull($result->global_category_id);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=GlobalProductMatcherTest`
Expected: FAIL — class `App\Services\GlobalProductMatcher` not found.

- [ ] **Step 3: Write the service**

```php
<?php

namespace App\Services;

use App\Models\GlobalCategory;
use App\Models\GlobalProduct;

class GlobalProductMatcher
{
    /**
     * Berilgan mahsulot ma'lumotlariga mos GlobalProduct'ni topadi yoki
     * topilmasa yangisini yaratadi. Avval barcode, keyin SKU, keyin
     * normallashtirilgan (trim+lowercase) nom bo'yicha aniq moslik
     * qidiriladi — amaliyotda moy almashtirish shahobchalari barcode/SKU
     * kiritmasligi mumkin, shu sabab nom asosiy mezon bo'lib qoladi.
     */
    public function findOrCreateFor(array $data): GlobalProduct
    {
        $barcode = trim((string) ($data['barcode'] ?? ''));
        if ($barcode !== '') {
            $match = GlobalProduct::where('barcode', $barcode)->first();
            if ($match) {
                return $match;
            }
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku !== '') {
            $match = GlobalProduct::where('sku', $sku)->first();
            if ($match) {
                return $match;
            }
        }

        $normalizedName = $this->normalizeName($data['name']);
        $match = GlobalProduct::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->first();
        if ($match) {
            return $match;
        }

        return GlobalProduct::create([
            'global_category_id' => $this->resolveCategoryId($data['category_name'] ?? null),
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'unit' => $data['unit'],
            'barcode' => $data['barcode'] ?? null,
            'is_active' => true,
        ]);
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function resolveCategoryId(?string $categoryName): ?int
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return null;
        }

        return GlobalCategory::firstOrCreate(['name' => $categoryName])->id;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=GlobalProductMatcherTest`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/GlobalProductMatcher.php tests/Unit/Services/GlobalProductMatcherTest.php
git commit -m "feat: add GlobalProductMatcher for auto-matching products into the global catalog"
```

---

## Task 4: Wire `GlobalProductMatcher` into `ProductController::store()` and remove the `car_models` field

**Files:**
- Modify: `app/Http/Controllers/ProductController.php:143-292`
- Test: `tests/Feature/ProductGlobalCatalogSyncTest.php`

**Interfaces:**
- Consumes: `GlobalProductMatcher::findOrCreateFor(array): GlobalProduct` (Task 3).
- Produces: `ProductController::createProductWithInitialStock(Workshop, User, array): Product` — same signature minus the now-removed `array $carModelIds = []` parameter. After creation, if the product has no `global_product_id`, it is auto-linked (or left `null` if the workshop already has another product on that global product — see spec addendum).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class ProductGlobalCatalogSyncTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_creating_a_product_auto_creates_a_matching_global_product(): void
    {
        [$user] = $this->createDirectorWithWorkshop();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Azot damlash xizmati',
            'unit' => 'dona',
            'currency' => 'UZS',
            'purchase_price_uzs' => 0,
            'selling_price_uzs' => 15000,
            'stock_quantity' => 0,
            'min_stock_level' => 0,
            'is_active' => true,
            'track_inventory' => false,
        ]);

        $response->assertRedirect(route('products.index'));

        $globalProduct = GlobalProduct::where('name', 'Azot damlash xizmati')->first();
        $this->assertNotNull($globalProduct);
        $this->assertDatabaseHas('products', [
            'name' => 'Azot damlash xizmati',
            'global_product_id' => $globalProduct->id,
        ]);
    }

    public function test_creating_a_product_with_an_existing_name_links_to_the_same_global_product(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();

        $globalProduct = GlobalProduct::create([
            'name' => 'Moy filtri Bosch',
            'unit' => 'dona',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Moy filtri Bosch',
            'unit' => 'dona',
            'currency' => 'UZS',
            'purchase_price_uzs' => 20000,
            'selling_price_uzs' => 35000,
            'stock_quantity' => 5,
            'min_stock_level' => 1,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $this->assertSame(1, GlobalProduct::where('name', 'Moy filtri Bosch')->count());
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'name' => 'Moy filtri Bosch',
            'global_product_id' => $globalProduct->id,
        ]);
    }

    public function test_it_does_not_violate_the_unique_workshop_global_product_constraint(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();

        $globalProduct = GlobalProduct::create([
            'name' => 'Tormoz suyuqligi',
            'unit' => 'litr',
            'is_active' => true,
        ]);
        $workshop->products()->create([
            'global_product_id' => $globalProduct->id,
            'name' => 'Tormoz suyuqligi (birinchi)',
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 10000,
            'selling_price_uzs' => 18000,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Tormoz suyuqligi',
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 10000,
            'selling_price_uzs' => 18000,
            'stock_quantity' => 3,
            'min_stock_level' => 1,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'name' => 'Tormoz suyuqligi',
            'global_product_id' => null,
        ]);
    }

    public function test_copy_from_catalog_does_not_create_a_duplicate_global_product(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();

        $globalProduct = GlobalProduct::create([
            'name' => 'Antifriz G12',
            'unit' => 'litr',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('products.copy-from-catalog'), [
            'items' => [[
                'global_product_id' => $globalProduct->id,
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'stock_quantity' => 10,
            ]],
        ]);

        $this->assertSame(1, GlobalProduct::where('name', 'Antifriz G12')->count());
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'global_product_id' => $globalProduct->id,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProductGlobalCatalogSyncTest`
Expected: FAIL — new products are created with `global_product_id` still `null` (matcher not wired in yet).

- [ ] **Step 3: Remove the `car_models` field from validation**

In `app/Http/Controllers/ProductController.php`, inside `productValidationRules()`, delete:

```php
            // Bog'langan avtomobil turlari
            'car_models' => 'nullable|array',
            'car_models.*' => 'integer|exists:car_models,id',
```

- [ ] **Step 4: Rewrite `createProductWithInitialStock()` to drop `car_models` handling and call the matcher**

Add the import at the top of the file:

```php
use App\Services\GlobalProductMatcher;
```

Replace:

```php
    private function createProductWithInitialStock(Workshop $workshop, User $user, array $validated, array $carModelIds = []): Product
    {
        unset($validated['car_models']);

        $product = $workshop->products()->create($validated);

        if (!empty($carModelIds)) {
            // Miqdor keyinchalik "Avto markalari" sahifasida aniqlashtiriladi, hozircha 1
            $product->carModels()->sync(collect($carModelIds)->mapWithKeys(
                fn ($carModelId) => [$carModelId => ['quantity' => 1]]
            ));
        }

        // StockMovementService orqali boshlang'ich qoldiqni qo'shish
```

with:

```php
    private function createProductWithInitialStock(Workshop $workshop, User $user, array $validated): Product
    {
        $product = $workshop->products()->create($validated);

        if (is_null($product->global_product_id)) {
            $this->linkToGlobalCatalog($product, $workshop);
        }

        // StockMovementService orqali boshlang'ich qoldiqni qo'shish
```

Then add the new private helper right after `createProductWithInitialStock()` closes (before `public function store(`):

```php
    /**
     * Yangi mahsulotni global katalog bilan moslashtiradi (yoki katalogga
     * qo'shadi) va bog'laydi. `products` jadvalidagi
     * unique(workshop_id, global_product_id) cheklovi tufayli, agar shu
     * workshopda allaqachon shu global mahsulotga bog'langan boshqa
     * mahsulot bo'lsa, bog'lanmay qoladi (product.global_product_id null
     * bo'lib qoladi, "Avto markalari" bo'limida lokal fallback ishlaydi).
     */
    private function linkToGlobalCatalog(Product $product, Workshop $workshop): void
    {
        $globalProduct = app(GlobalProductMatcher::class)->findOrCreateFor([
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
```

- [ ] **Step 5: Update the two call sites that passed `$carModelIds`**

In `store()`, replace:

```php
        $carModelIds = $validated['car_models'] ?? [];

        DB::beginTransaction();
        try {
            $this->createProductWithInitialStock($workshop, $user, $validated, $carModelIds);
```

with:

```php
        DB::beginTransaction();
        try {
            $this->createProductWithInitialStock($workshop, $user, $validated);
```

`bulkStore()` and `copyFromCatalog()` already call `createProductWithInitialStock($workshop, $user, $productData)` with no third argument — no change needed there.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ProductGlobalCatalogSyncTest`
Expected: PASS (4 tests)

- [ ] **Step 7: Run the full backend test suite to check for regressions**

Run: `php artisan test`
Expected: PASS — no test still relies on the removed `car_models` request field (confirmed by the earlier grep in Task 2 Step 9 showing no frontend usage either).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/ProductController.php tests/Feature/ProductGlobalCatalogSyncTest.php
git commit -m "feat: auto-sync newly created products into the global catalog"
```

---

## Task 5: `ProductController::show()` exposes `effectiveCarModels()`; `Products/Show.vue` shows read-only compatibility when global-linked

**Files:**
- Modify: `app/Http/Controllers/ProductController.php:505-535` (`show()`)
- Modify: `resources/js/Pages/Products/Show.vue`
- Test: `tests/Feature/ProductShowCarModelsTest.php`

**Interfaces:**
- Consumes: `Product::effectiveCarModels()` (Task 2).
- Produces: Inertia `Products/Show` prop `product.car_models` — always the *effective* list (global-inherited or local-fallback), regardless of which relation backs it.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class ProductShowCarModelsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_show_exposes_car_models_inherited_from_the_global_product(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();

        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);

        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $globalProduct->carModels()->attach($model->id, ['quantity' => 3.5]);

        $product = $workshop->products()->create([
            'global_product_id' => $globalProduct->id,
            'name' => 'Motor moyi 5W-30',
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 50000,
            'selling_price_uzs' => 70000,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($user)->get(route('products.show', $product));

        $response->assertInertia(fn ($page) => $page
            ->component('Products/Show')
            ->where('product.car_models.0.id', $model->id)
        );
    }
}
```

Note: the pivot `quantity` value's exact serialized type (string vs float, e.g. `"3.50"` vs `3.5`) depends on the DB driver and isn't asserted here — it's already covered precisely in `ProductEffectiveCarModelsTest` (Task 2) via `assertEquals()`, which tolerates that difference. Keep this test focused on "is the right car model present," not on numeric formatting.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProductShowCarModelsTest`
Expected: FAIL — `product.car_models` currently comes from the (now nonexistent) `carModels` relation, so the assertion path resolves to `null`.

- [ ] **Step 3: Update `ProductController::show()`**

Replace:

```php
        $product->load([
            'category',
            'supplier',
            'carModels',
            'inventoryTransactions' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Products/Show', [
            'product' => $product,
            'carMakes' => CarMake::with(['carModels' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'canManageCarMakes' => $user->can('car-makes.manage'),
        ]);
```

with:

```php
        $product->load([
            'category',
            'supplier',
            'localCarModels',
            'globalProduct.carModels',
            'inventoryTransactions' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Products/Show', [
            'product' => array_merge($product->toArray(), [
                'car_models' => $product->effectiveCarModels(),
            ]),
            'carMakes' => CarMake::with(['carModels' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'canManageCarMakes' => $user->can('car-makes.manage'),
        ]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProductShowCarModelsTest`
Expected: PASS

- [ ] **Step 5: Update `Products/Show.vue` to branch on `product.global_product_id`**

Replace the "O'ng ustun: avtomobil turlariga tavsiya" card body (everything between `<h3 ...>Avtomobil turlariga tavsiya</h3>` and the closing `</div></div>` of that card) with:

```html
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                            Avtomobil turlariga tavsiya
                        </h3>

                        <template v-if="product.global_product_id">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Bu mahsulot umumiy katalogga bog'langan — moshina mosligi umumiy katalog orqali belgilanadi.
                            </p>

                            <div v-if="(product.car_models ?? []).length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                                Hali hech qanday avtomobil turiga bog'lanmagan.
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <span
                                    v-for="model in product.car_models"
                                    :key="model.id"
                                    class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200"
                                >
                                    {{ model.name }}
                                </span>
                            </div>
                        </template>

                        <template v-else>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Qaysi avtomobil turlariga ushbu mahsulot tavsiya etilishini belgilang.
                            </p>

                            <div v-if="!canManageCarMakes" class="mb-4 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                Bu bo'limni faqat direktor/superadmin tahrirlashi mumkin.
                            </div>

                            <div v-if="carMakes.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                                Avtomobil markalari hali qo'shilmagan.
                            </div>

                            <div v-else class="space-y-4 max-h-[32rem] overflow-y-auto pr-1">
                                <div v-for="make in carMakes" :key="make.id">
                                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ make.name }}
                                    </h4>
                                    <div v-if="make.car_models.length === 0" class="pl-2 text-xs text-gray-400">
                                        Hali turlari yo'q
                                    </div>
                                    <div v-else class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                        <label
                                            v-for="model in make.car_models"
                                            :key="model.id"
                                            class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                            :class="canManageCarMakes ? 'cursor-pointer' : 'cursor-not-allowed opacity-75'"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="linkedModelIds.has(model.id)"
                                                :disabled="!canManageCarMakes"
                                                @change="toggleModel(model, $event.target.checked)"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <span class="text-gray-700 dark:text-gray-300">{{ model.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
```

(This keeps the exact existing markup for the `v-else` branch — only wraps it in `<template v-else>` and adds the new `v-if` branch above it. `linkedModelIds` and `toggleModel` in `<script setup>` are unchanged.)

- [ ] **Step 6: Manually verify in the browser**

Run: `npm run build` (catches any Vue/template syntax errors the automated tests can't).
Expected: build succeeds with no errors in `Products/Show.vue`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/ProductController.php resources/js/Pages/Products/Show.vue tests/Feature/ProductShowCarModelsTest.php
git commit -m "feat: show global-inherited car-model compatibility as read-only on product page"
```

---

## Task 6: Global admin manages car-model compatibility (`GlobalProductController` + routes)

**Files:**
- Modify: `app/Http/Controllers/GlobalProductController.php`
- Modify: `routes/web.php:79-83`
- Test: `tests/Feature/GlobalProductCarModelLinkTest.php`

**Interfaces:**
- Produces: `POST /global-products/{globalProduct}/car-models` (`global-products.car-models.attach`), `PUT /global-products/{globalProduct}/car-models/{carModel}` (`global-products.car-models.update`), `DELETE /global-products/{globalProduct}/car-models/{carModel}` (`global-products.car-models.destroy`) — all gated by `can:global-products.manage` (superadmin only, per `RolePermissionSeeder`).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class GlobalProductCarModelLinkTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_superadmin_can_attach_a_car_model_to_a_global_product(): void
    {
        $admin = $this->createSuperAdmin();
        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('global-products.car-models.attach', $globalProduct), [
            'car_model_id' => $model->id,
            'quantity' => 3.5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('car_model_global_products', [
            'global_product_id' => $globalProduct->id,
            'car_model_id' => $model->id,
            'quantity' => 3.5,
        ]);
    }

    public function test_superadmin_can_update_the_linked_quantity(): void
    {
        $admin = $this->createSuperAdmin();
        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $globalProduct->carModels()->attach($model->id, ['quantity' => 3.5]);

        $response = $this->actingAs($admin)->put(
            route('global-products.car-models.update', [$globalProduct, $model]),
            ['quantity' => 4.0]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('car_model_global_products', [
            'global_product_id' => $globalProduct->id,
            'car_model_id' => $model->id,
            'quantity' => 4.0,
        ]);
    }

    public function test_superadmin_can_detach_a_car_model(): void
    {
        $admin = $this->createSuperAdmin();
        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $globalProduct->carModels()->attach($model->id, ['quantity' => 3.5]);

        $response = $this->actingAs($admin)->delete(
            route('global-products.car-models.destroy', [$globalProduct, $model])
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('car_model_global_products', [
            'global_product_id' => $globalProduct->id,
            'car_model_id' => $model->id,
        ]);
    }

    public function test_a_director_cannot_manage_global_car_model_links(): void
    {
        [$director] = $this->createDirectorWithWorkshop();
        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($director)->post(route('global-products.car-models.attach', $globalProduct), [
            'car_model_id' => $model->id,
            'quantity' => 3.5,
        ]);

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=GlobalProductCarModelLinkTest`
Expected: FAIL — routes `global-products.car-models.*` don't exist yet.

- [ ] **Step 3: Add the routes**

In `routes/web.php`, inside the existing `Route::middleware('can:global-products.manage')->group(...)` block (around line 79-83), replace:

```php
    Route::middleware('can:global-products.manage')->group(function () {
        Route::get('/global-products/bulk-create', [GlobalProductController::class, 'bulkCreate'])->name('global-products.bulk-create');
        Route::post('/global-products/bulk-store', [GlobalProductController::class, 'bulkStore'])->name('global-products.bulk-store');
        Route::resource('global-products', GlobalProductController::class)->except(['show']);
    });
```

with:

```php
    Route::middleware('can:global-products.manage')->group(function () {
        Route::get('/global-products/bulk-create', [GlobalProductController::class, 'bulkCreate'])->name('global-products.bulk-create');
        Route::post('/global-products/bulk-store', [GlobalProductController::class, 'bulkStore'])->name('global-products.bulk-store');
        Route::post('/global-products/{globalProduct}/car-models', [GlobalProductController::class, 'attachCarModel'])->name('global-products.car-models.attach');
        Route::put('/global-products/{globalProduct}/car-models/{carModel}', [GlobalProductController::class, 'updateCarModelQuantity'])->name('global-products.car-models.update');
        Route::delete('/global-products/{globalProduct}/car-models/{carModel}', [GlobalProductController::class, 'detachCarModel'])->name('global-products.car-models.destroy');
        Route::resource('global-products', GlobalProductController::class)->except(['show']);
    });
```

- [ ] **Step 4: Add the controller methods**

In `app/Http/Controllers/GlobalProductController.php`, add imports:

```php
use App\Models\CarModel;
```

Add these three methods at the end of the class (before the final closing `}`):

```php
    /**
     * Ushbu global mahsulotni bir avtomobil turiga (kerakli miqdor bilan)
     * bog'laydi — mavjud bo'lsa miqdorni yangilaydi.
     */
    public function attachCarModel(Request $request, GlobalProduct $globalProduct): RedirectResponse
    {
        $validated = $request->validate([
            'car_model_id' => 'required|exists:car_models,id',
            'quantity' => 'required|numeric|min:0.01|max:9999.99',
        ]);

        $globalProduct->carModels()->syncWithoutDetaching([
            $validated['car_model_id'] => ['quantity' => $validated['quantity']],
        ]);

        return back()->with('success', 'Avtomobil turi bog\'landi!');
    }

    /**
     * Bog'langan avtomobil turi uchun kerakli miqdorni yangilaydi.
     */
    public function updateCarModelQuantity(Request $request, GlobalProduct $globalProduct, CarModel $carModel): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:9999.99',
        ]);

        $globalProduct->carModels()->updateExistingPivot($carModel->id, ['quantity' => $validated['quantity']]);

        return back()->with('success', 'Miqdor yangilandi!');
    }

    /**
     * Avtomobil turi bilan bog'lanishni uzadi.
     */
    public function detachCarModel(GlobalProduct $globalProduct, CarModel $carModel): RedirectResponse
    {
        $globalProduct->carModels()->detach($carModel->id);

        return back()->with('success', 'Bog\'lanish o\'chirildi!');
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=GlobalProductCarModelLinkTest`
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/GlobalProductController.php routes/web.php tests/Feature/GlobalProductCarModelLinkTest.php
git commit -m "feat: let superadmin manage car-model compatibility on the global catalog"
```

---

## Task 7: `GlobalProducts/Edit.vue` UI for managing car-model compatibility

**Files:**
- Modify: `app/Http/Controllers/GlobalProductController.php` (`edit()` method)
- Modify: `resources/js/Pages/GlobalProducts/Edit.vue`
- Test: `tests/Feature/GlobalProductEditPageTest.php`

**Interfaces:**
- Consumes: `global-products.car-models.attach|update|destroy` routes (Task 6).
- Produces: Inertia `GlobalProducts/Edit` props `product.car_models` (linked models with `pivot.quantity`) and `carMakes` (all makes/models, for the picker).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class GlobalProductEditPageTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_edit_page_exposes_linked_car_models_and_all_car_makes(): void
    {
        $admin = $this->createSuperAdmin();
        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $globalProduct->carModels()->attach($model->id, ['quantity' => 3.5]);

        $response = $this->actingAs($admin)->get(route('global-products.edit', $globalProduct));

        $response->assertInertia(fn ($page) => $page
            ->component('GlobalProducts/Edit')
            ->where('product.car_models.0.id', $model->id)
            ->where('carMakes.0.name', 'Chevrolet')
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GlobalProductEditPageTest`
Expected: FAIL — `carMakes` prop missing, `product.car_models` not loaded.

- [ ] **Step 3: Update `GlobalProductController::edit()`**

Add the import:

```php
use App\Models\CarMake;
```

Replace:

```php
    public function edit(GlobalProduct $globalProduct): Response
    {
        return Inertia::render('GlobalProducts/Edit', [
            'product' => $globalProduct->load('globalCategory'),
            'categoryNames' => GlobalCategory::orderBy('name')->pluck('name'),
        ]);
    }
```

with:

```php
    public function edit(GlobalProduct $globalProduct): Response
    {
        return Inertia::render('GlobalProducts/Edit', [
            'product' => $globalProduct->load(['globalCategory', 'carModels']),
            'categoryNames' => GlobalCategory::orderBy('name')->pluck('name'),
            'carMakes' => CarMake::with(['carModels' => fn ($query) => $query->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GlobalProductEditPageTest`
Expected: PASS

- [ ] **Step 5: Add the car-model linking section to `GlobalProducts/Edit.vue`**

Add to `<script setup>` (after the existing `const form = useForm(...)` block, before `const submit = ...`):

```js
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
```

(add these to the existing `import` lines at the top of the file, alongside the current `Head, Link, useForm` import from `@inertiajs/vue3` — merge `router` into that same import statement rather than duplicating it)

```js
const linkedCarModels = computed(() => props.product.car_models ?? []);

const carModelOptions = props.carMakes.flatMap((make) =>
    make.car_models.map((model) => ({
        value: model.id,
        label: `${make.name} ${model.name}`,
    }))
);

const attachForm = useForm({ car_model_id: null, quantity: 1 });
const submitAttach = () => {
    if (!attachForm.car_model_id) {
        return;
    }
    attachForm.post(route('global-products.car-models.attach', props.product.id), {
        preserveScroll: true,
        onSuccess: () => attachForm.reset(),
    });
};

const detachCarModel = (model) => {
    router.delete(route('global-products.car-models.destroy', [props.product.id, model.id]), {
        preserveScroll: true,
    });
};

const editingQtyId = ref(null);
const qtyForm = useForm({ quantity: 1 });
const startEditQty = (model) => {
    editingQtyId.value = model.id;
    qtyForm.quantity = model.pivot.quantity;
};
const submitQty = (model) => {
    qtyForm.put(route('global-products.car-models.update', [props.product.id, model.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingQtyId.value = null;
        },
    });
};
```

Also add `carMakes: Array` to `defineProps({...})`.

In the `<template>`, add a second card right after the closing `</div>` of the existing form card (i.e. after `<div class="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"> ... </div>` that wraps the `<form>`), still inside the `<div class="mx-auto max-w-3xl ...">` wrapper:

```html
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">
                            Mos avtomobil turlari
                        </h3>
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                            Ushbu mahsulot qaysi avtomobil turlariga mos kelishini va har biriga
                            kerakli miqdorni belgilang. Bu bog'lanish shu mahsulotni o'ziga
                            nusxa olgan barcha tadbirkorlarga avtomatik ko'rinadi.
                        </p>

                        <form @submit.prevent="submitAttach" class="mb-4 flex flex-wrap items-center gap-2">
                            <div class="w-full min-w-[14rem] flex-1 sm:w-auto">
                                <Multiselect
                                    v-model="attachForm.car_model_id"
                                    :options="carModelOptions"
                                    :searchable="true"
                                    placeholder="Avtomobil turini tanlang..."
                                    noOptionsText="Tur topilmadi"
                                    noResultsText="Natija topilmadi"
                                />
                            </div>
                            <input
                                v-model="attachForm.quantity"
                                type="number"
                                step="any"
                                min="0.01"
                                title="Miqdori"
                                class="w-24 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <button
                                type="submit"
                                :disabled="attachForm.processing || !attachForm.car_model_id"
                                class="shrink-0 rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                + Bog'lash
                            </button>
                        </form>

                        <div v-if="linkedCarModels.length === 0" class="text-sm text-gray-400">
                            Hali hech qanday avtomobil turi bog'lanmagan.
                        </div>
                        <div v-else class="space-y-1">
                            <div
                                v-for="model in linkedCarModels"
                                :key="model.id"
                                class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <span class="min-w-0 flex-1 truncate text-gray-700 dark:text-gray-300">
                                    {{ model.name }}
                                </span>

                                <template v-if="editingQtyId === model.id">
                                    <form @submit.prevent="submitQty(model)" class="flex shrink-0 items-center gap-1.5">
                                        <input
                                            v-model="qtyForm.quantity"
                                            type="number"
                                            step="any"
                                            min="0.01"
                                            autofocus
                                            class="w-24 rounded border-gray-300 py-1 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                        <button type="submit" class="text-lg text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">✓</button>
                                        <button type="button" @click="editingQtyId = null" class="text-lg text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
                                    </form>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    @click="startEditQty(model)"
                                    title="Miqdorni tahrirlash"
                                    class="shrink-0 text-sm font-semibold text-gray-500 underline decoration-dotted hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    ×{{ model.pivot.quantity }}
                                </button>
                                <button
                                    type="button"
                                    @click="detachCarModel(model)"
                                    title="Bog'lanishni uzish"
                                    class="shrink-0 text-red-600 hover:text-red-900 dark:text-red-400"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
```

- [ ] **Step 6: Verify the build**

Run: `npm run build`
Expected: build succeeds with no errors in `GlobalProducts/Edit.vue`.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/GlobalProductController.php resources/js/Pages/GlobalProducts/Edit.vue tests/Feature/GlobalProductEditPageTest.php
git commit -m "feat: add car-model compatibility editor to the global product admin page"
```

---

## Task 8: Backfill existing products/links (`global-catalog:backfill` command)

**Files:**
- Create: `app/Console/Commands/BackfillGlobalProducts.php`
- Test: `tests/Feature/BackfillGlobalProductsCommandTest.php`

**Interfaces:**
- Consumes: `GlobalProductMatcher::findOrCreateFor(array): GlobalProduct` (Task 3).
- Produces: Artisan command `global-catalog:backfill` — safe to run multiple times (idempotent: skips products that already have a `global_product_id`).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\CarMake;
use App\Models\CarModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class BackfillGlobalProductsCommandTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_it_backfills_global_product_links_and_moves_car_model_associations(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();

        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);

        $product = $workshop->products()->create([
            'name' => 'Motor moyi 5W-30',
            'unit' => 'litr',
            'currency' => 'UZS',
            'purchase_price_uzs' => 50000,
            'selling_price_uzs' => 70000,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        DB::table('car_model_products')->insert([
            'car_model_id' => $model->id,
            'product_id' => $product->id,
            'quantity' => 3.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('global-catalog:backfill')->assertExitCode(0);

        $product->refresh();
        $this->assertNotNull($product->global_product_id);
        $this->assertDatabaseHas('car_model_global_products', [
            'car_model_id' => $model->id,
            'global_product_id' => $product->global_product_id,
            'quantity' => 3.5,
        ]);
    }

    public function test_it_keeps_the_max_quantity_when_two_workshops_merge_into_the_same_global_product(): void
    {
        [, $workshopA] = $this->createDirectorWithWorkshop();
        [, $workshopB] = $this->createDirectorWithWorkshop();

        $make = CarMake::create(['name' => 'Chevrolet', 'sort_order' => 1]);
        $model = CarModel::create(['car_make_id' => $make->id, 'name' => 'Cobalt']);

        $productA = $workshopA->products()->create([
            'name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'currency' => 'UZS',
            'purchase_price_uzs' => 50000, 'selling_price_uzs' => 70000,
            'is_active' => true, 'track_inventory' => true,
        ]);
        $productB = $workshopB->products()->create([
            'name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'currency' => 'UZS',
            'purchase_price_uzs' => 48000, 'selling_price_uzs' => 68000,
            'is_active' => true, 'track_inventory' => true,
        ]);

        DB::table('car_model_products')->insert([
            ['car_model_id' => $model->id, 'product_id' => $productA->id, 'quantity' => 3.0, 'created_at' => now(), 'updated_at' => now()],
            ['car_model_id' => $model->id, 'product_id' => $productB->id, 'quantity' => 4.0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('global-catalog:backfill')->assertExitCode(0);

        $productA->refresh();
        $productB->refresh();
        $this->assertSame($productA->global_product_id, $productB->global_product_id);
        $this->assertDatabaseHas('car_model_global_products', [
            'car_model_id' => $model->id,
            'global_product_id' => $productA->global_product_id,
            'quantity' => 4.0,
        ]);
        $this->assertDatabaseCount('car_model_global_products', 1);
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->products()->create([
            'name' => 'Moy filtri', 'unit' => 'dona', 'currency' => 'UZS',
            'purchase_price_uzs' => 15000, 'selling_price_uzs' => 25000,
            'is_active' => true, 'track_inventory' => true,
        ]);

        $this->artisan('global-catalog:backfill')->assertExitCode(0);
        $this->artisan('global-catalog:backfill')->assertExitCode(0);

        $this->assertDatabaseCount('global_products', 1);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=BackfillGlobalProductsCommandTest`
Expected: FAIL — command `global-catalog:backfill` does not exist.

- [ ] **Step 3: Write the command**

```php
<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GlobalProductMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillGlobalProducts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'global-catalog:backfill';

    /**
     * @var string
     */
    protected $description = 'Global_product_id\'siz mahsulotlarni umumiy katalog bilan moslashtiradi/qo\'shadi va eski car_model_products bog\'lanishlarini global darajaga ko\'chiradi. Bir necha marta xavfsiz ishga tushirish mumkin.';

    public function handle(GlobalProductMatcher $matcher): int
    {
        $this->info('Global katalogga bog\'lanmagan mahsulotlarni aniqlash...');

        $linkedProducts = 0;
        $migratedLinks = 0;

        DB::transaction(function () use ($matcher, &$linkedProducts) {
            Product::whereNull('global_product_id')
                ->with('category')
                ->chunkById(50, function ($products) use ($matcher, &$linkedProducts) {
                    foreach ($products as $product) {
                        $globalProduct = $matcher->findOrCreateFor([
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'barcode' => $product->barcode,
                            'unit' => $product->unit,
                            'description' => $product->description,
                            'category_name' => $product->category?->name,
                        ]);

                        $alreadyLinkedInWorkshop = Product::where('workshop_id', $product->workshop_id)
                            ->where('id', '!=', $product->id)
                            ->where('global_product_id', $globalProduct->id)
                            ->exists();

                        if ($alreadyLinkedInWorkshop) {
                            continue;
                        }

                        $product->update(['global_product_id' => $globalProduct->id]);
                        $linkedProducts++;
                    }
                });

            DB::table('car_model_products')
                ->join('products', 'products.id', '=', 'car_model_products.product_id')
                ->whereNotNull('products.global_product_id')
                ->select('car_model_products.car_model_id', 'products.global_product_id', 'car_model_products.quantity')
                ->get()
                ->groupBy(fn ($row) => $row->car_model_id.'-'.$row->global_product_id)
                ->each(function ($rows) {
                    $first = $rows->first();

                    DB::table('car_model_global_products')->updateOrInsert(
                        ['car_model_id' => $first->car_model_id, 'global_product_id' => $first->global_product_id],
                        ['quantity' => $rows->max('quantity'), 'updated_at' => now(), 'created_at' => now()]
                    );
                });
        });

        $migratedLinks = DB::table('car_model_global_products')->count();

        $this->info("Yakunlandi: {$linkedProducts} ta mahsulot global katalogga bog'landi, jami {$migratedLinks} ta moshina-mahsulot bog'lanishi mavjud.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=BackfillGlobalProductsCommandTest`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/BackfillGlobalProducts.php tests/Feature/BackfillGlobalProductsCommandTest.php
git commit -m "feat: add global-catalog:backfill command for existing products and car-model links"
```

---

## Task 9: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Run the entire backend test suite**

Run: `php artisan test`
Expected: all tests PASS, including every test added in Tasks 1-8.

- [ ] **Step 2: Run the frontend build**

Run: `npm run build`
Expected: succeeds with no Vue/JS errors.

- [ ] **Step 3: Manual production backfill checklist (do NOT automate — human-gated)**

Before running `php artisan global-catalog:backfill` against the production `sql_oilcontrol` database:
1. Take a database backup.
2. Run `php artisan global-catalog:backfill` and read its summary line (products linked, links migrated).
3. Spot-check a few `global_products` rows created from ambiguous local names (e.g. run `GlobalProduct::orderByDesc('id')->limit(20)->get(['id','name'])` via `php artisan tinker`) to confirm no obviously-wrong merges happened.

- [ ] **Step 4: Report to the user**

Summarize: which tasks landed, test counts, and that the production backfill step (Step 3 above) is intentionally left for the user to run themselves against their real database.
