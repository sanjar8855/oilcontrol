<?php
// tests/Feature/OnboardingVehicleStepTest.php
namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingVehicleStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_vehicle_step_page_renders(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->get(route('onboarding.vehicle'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Vehicle'));
    }

    public function test_creating_a_client_and_vehicle_advances_to_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ]);

        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $response->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertSame('sale', $workshop->fresh()->onboarding_step);
        $this->assertSame('Aziz Karimov', $vehicle->client->name);
    }

    public function test_missing_plate_number_fails_validation(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $response = $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'make' => 'Nexia',
        ]);

        $response->assertSessionHasErrors('plate_number');
        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);
    }

    public function test_vehicle_step_without_a_current_workshop_returns_403_not_500(): void
    {
        // Same null-currentWorkshop() edge case as storeProducts()/skip(): calling
        // methods on a null workshop would be an uncaught Error, not a clean 403.
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user = \App\Models\User::factory()->create(['branch_id' => null]);
        $user->assignRole('employee');

        $getResponse = $this->actingAs($user)->get(route('onboarding.vehicle'));
        $getResponse->assertForbidden();

        $postResponse = $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ]);
        $postResponse->assertForbidden();
    }
}
