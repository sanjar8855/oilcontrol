<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarModelGlobalProductsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_model_global_products_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('car_model_global_products'));
        $this->assertTrue(Schema::hasColumns('car_model_global_products', [
            'id', 'car_model_id', 'global_product_id', 'quantity', 'created_at', 'updated_at',
        ]));
    }
}
