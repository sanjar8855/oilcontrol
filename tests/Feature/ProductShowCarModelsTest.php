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
