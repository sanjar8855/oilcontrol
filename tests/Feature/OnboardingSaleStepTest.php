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

    public function test_vehicle_show_page_is_highlighted_during_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->actingAs($user)->get(route('vehicles.show', $vehicle));

        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', true));
    }

    public function test_vehicle_show_page_is_not_highlighted_outside_onboarding(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A888AA', 'make' => 'Nexia']);

        $response = $this->actingAs($user)->get(route('vehicles.show', $vehicle));

        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', false));
    }

    /**
     * The 'products' and 'vehicle' onboarding steps normally never reach
     * VehicleController::show at all — EnsureOnboardingComplete redirects every
     * request back into the wizard until the workshop reaches the 'sale' step.
     * We bypass that middleware here so we can test the isOnboardingHighlight
     * computation in VehicleController::show directly: it must only ever be true
     * on the 'sale' step, never on an earlier step, even if this method were
     * ever reached with the workshop mid-onboarding on an earlier step.
     */
    public function test_vehicle_show_page_is_not_highlighted_when_onboarding_is_on_an_earlier_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);
        $vehicle = $this->createOnboardingVehicle($workshop);

        $response = $this->withoutMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class)
            ->actingAs($user)->get(route('vehicles.show', $vehicle));
        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', false));

        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->withoutMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class)
            ->actingAs($user)->get(route('vehicles.show', $vehicle));
        $response->assertInertia(fn ($page) => $page->where('isOnboardingHighlight', false));
    }

    public function test_completing_a_sale_during_onboarding_finishes_the_cycle_and_redirects_to_dashboard(): void
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

        $response->assertRedirect(route('dashboard'));
        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertNotNull($fresh->onboarding_completed_at);
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
