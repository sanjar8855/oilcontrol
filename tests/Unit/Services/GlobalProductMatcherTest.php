<?php

namespace Tests\Unit\Services;

use App\Models\Brand;
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
            'name' => "Noma'lum mahsulot",
            'sku' => null,
            'barcode' => null,
            'unit' => 'dona',
            'description' => null,
            'category_name' => null,
        ]);

        $this->assertNull($result->global_category_id);
    }

    public function test_catalog_query_filters_by_brand(): void
    {
        $lukoil = Brand::create(['name' => 'Lukoil', 'is_active' => true]);
        $shell = Brand::create(['name' => 'Shell', 'is_active' => true]);
        GlobalProduct::create(['name' => 'Lukoil moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $lukoil->id]);
        GlobalProduct::create(['name' => 'Shell moyi', 'unit' => 'litr', 'is_active' => true, 'brand_id' => $shell->id]);

        $results = $this->matcher->catalogQuery(null, null, $lukoil->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Lukoil moyi', $results->first()->name);
    }
}
