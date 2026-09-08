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
