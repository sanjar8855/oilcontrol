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
