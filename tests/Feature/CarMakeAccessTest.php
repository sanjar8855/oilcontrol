<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class CarMakeAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_a_superadmin_without_an_active_workshop_can_view_car_makes(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('car-makes.index'));

        $response->assertOk();
    }
}
