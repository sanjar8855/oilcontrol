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
