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
