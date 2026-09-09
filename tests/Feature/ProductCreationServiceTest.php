<?php

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
