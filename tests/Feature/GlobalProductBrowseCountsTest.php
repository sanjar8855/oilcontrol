<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class GlobalProductBrowseCountsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    private function seedTwoLukoilOneShell(): array
    {
        $lukoil = Brand::create(['name' => 'Lukoil', 'is_active' => true]);
        $shell = Brand::create(['name' => 'Shell', 'is_active' => true]);
        $oils = GlobalCategory::create(['name' => 'Motor moylari', 'is_active' => true]);
        $filters = GlobalCategory::create(['name' => 'Filtrlar', 'is_active' => true]);

        GlobalProduct::create(['name' => 'Lukoil 5W-30', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $lukoil->id, 'global_category_id' => $oils->id]);
        GlobalProduct::create(['name' => 'Lukoil 10W-40', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $lukoil->id, 'global_category_id' => $oils->id]);
        GlobalProduct::create(['name' => 'Shell filtri', 'unit' => 'dona', 'is_active' => true, 'brand_id' => $shell->id, 'global_category_id' => $filters->id]);

        return [$lukoil, $shell, $oils, $filters];
    }

    public function test_global_catalog_index_includes_product_counts_per_brand_and_category(): void
    {
        $admin = $this->createSuperAdmin();
        [$lukoil, $shell, $oils, $filters] = $this->seedTwoLukoilOneShell();

        $response = $this->actingAs($admin)->get(route('global-products.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('brands', fn ($brands) => collect($brands)->firstWhere('id', $lukoil->id)['products_count'] === 2
                && collect($brands)->firstWhere('id', $shell->id)['products_count'] === 1)
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('id', $oils->id)['products_count'] === 2
                && collect($categories)->firstWhere('id', $filters->id)['products_count'] === 1)
        );
    }

    public function test_workshop_catalog_page_includes_product_counts_per_brand_and_category(): void
    {
        [$director] = $this->createDirectorWithWorkshop();
        [$lukoil, $shell, $oils, $filters] = $this->seedTwoLukoilOneShell();

        $response = $this->actingAs($director)->get(route('products.catalog'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('brands', fn ($brands) => collect($brands)->firstWhere('id', $lukoil->id)['products_count'] === 2
                && collect($brands)->firstWhere('id', $shell->id)['products_count'] === 1)
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('id', $oils->id)['products_count'] === 2
                && collect($categories)->firstWhere('id', $filters->id)['products_count'] === 1)
        );
    }
}
