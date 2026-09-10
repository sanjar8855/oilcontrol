<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class GlobalProductBrandTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_quick_add_endpoint_creates_a_new_brand(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->postJson(route('brands.store'), ['name' => 'Lukoil']);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name']);
        $this->assertDatabaseHas('brands', ['name' => 'Lukoil', 'is_active' => true]);
        $this->assertSame(1, Brand::count());
    }

    public function test_quick_add_endpoint_reuses_an_existing_brand_ignoring_case_and_spaces(): void
    {
        $admin = $this->createSuperAdmin();
        $existing = Brand::create(['name' => 'Valvoline', 'is_active' => true]);

        $response = $this->actingAs($admin)->postJson(route('brands.store'), ['name' => '  valvoline  ']);

        $response->assertOk();
        $response->assertJson(['id' => $existing->id, 'name' => $existing->name]);
        $this->assertSame(1, Brand::count());
    }

    public function test_a_director_cannot_quick_add_a_brand(): void
    {
        [$director] = $this->createDirectorWithWorkshop();

        $response = $this->actingAs($director)->postJson(route('brands.store'), ['name' => 'ZIC']);

        $response->assertForbidden();
        $this->assertSame(0, Brand::count());
    }

    public function test_storing_a_global_product_saves_the_brand(): void
    {
        $admin = $this->createSuperAdmin();
        $brand = Brand::create(['name' => 'Shell', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('global-products.store'), [
            'brand_id' => $brand->id,
            'name' => 'Helix 5W-30',
            'unit' => 'litr',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('global-products.index'));
        $this->assertDatabaseHas('global_products', [
            'name' => 'Helix 5W-30',
            'brand_id' => $brand->id,
        ]);
    }

    public function test_updating_a_global_product_saves_the_brand(): void
    {
        $admin = $this->createSuperAdmin();
        $brand = Brand::create(['name' => 'Mobil', 'is_active' => true]);
        $product = GlobalProduct::create(['name' => 'Motor moyi', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('global-products.update', $product), [
            'brand_id' => $brand->id,
            'name' => 'Motor moyi',
            'unit' => 'litr',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('global-products.index'));
        $this->assertDatabaseHas('global_products', [
            'id' => $product->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_global_catalog_index_filters_by_brand(): void
    {
        $admin = $this->createSuperAdmin();
        $lukoil = Brand::create(['name' => 'Lukoil', 'is_active' => true]);
        $shell = Brand::create(['name' => 'Shell', 'is_active' => true]);
        GlobalProduct::create(['name' => 'Lukoil moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $lukoil->id]);
        GlobalProduct::create(['name' => 'Shell moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $shell->id]);

        $response = $this->actingAs($admin)->get(route('global-products.index', ['brand_id' => $lukoil->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Lukoil moyi')
        );
    }

    public function test_workshop_catalog_page_filters_by_brand(): void
    {
        [$director, $workshop] = $this->createDirectorWithWorkshop();
        $lukoil = Brand::create(['name' => 'Lukoil', 'is_active' => true]);
        $shell = Brand::create(['name' => 'Shell', 'is_active' => true]);
        GlobalProduct::create(['name' => 'Lukoil moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $lukoil->id]);
        GlobalProduct::create(['name' => 'Shell moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $shell->id]);

        $response = $this->actingAs($director)->get(route('products.catalog', ['brand_id' => $shell->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Shell moyi')
        );
    }
}
