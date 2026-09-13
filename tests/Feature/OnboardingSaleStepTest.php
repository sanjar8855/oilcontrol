<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingSaleStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    private function createOnboardingVehicle($workshop): Vehicle
    {
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);

        return Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A777AA', 'make' => 'Nexia']);
    }

    public function test_sale_step_page_renders_with_the_onboarding_vehicle(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->actingAs($user)->get(route('onboarding.sale'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Sale')
            ->where('vehicle.id', $vehicle->id));
    }

    public function test_sale_step_page_self_heals_to_the_vehicle_step_when_no_vehicle_exists(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);

        $response = $this->withoutMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class)
            ->actingAs($user)->get(route('onboarding.sale'));

        $response->assertRedirect(route('onboarding.vehicle'));
        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);
    }

    public function test_completing_a_sale_during_onboarding_advances_to_the_result_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => "Ish haqi", 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('onboarding.result'));
        $this->assertSame('result', $workshop->fresh()->onboarding_step);
        $this->assertNull($workshop->fresh()->onboarding_completed_at);
    }

    public function test_a_normal_sale_outside_onboarding_still_redirects_to_the_vehicle_page(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A999AA', 'make' => 'Nexia']);

        $response = $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => "Ish haqi", 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('vehicles.show', $vehicle->id));
    }
}
