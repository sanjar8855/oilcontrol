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
