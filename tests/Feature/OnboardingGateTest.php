<?php
// tests/Feature/OnboardingGateTest.php
namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingGateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_dashboard_redirects_to_the_products_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.products'));
    }

    public function test_dashboard_redirects_to_the_vehicle_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.vehicle'));
    }

    public function test_dashboard_redirects_to_the_onboarding_vehicles_show_page_on_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A123AA', 'make' => 'Chevrolet']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('vehicles.show', $vehicle));
    }

    public function test_the_current_step_route_stays_reachable(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get(route('onboarding.products'))->assertOk();
    }

    public function test_cannot_skip_ahead_to_a_later_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->get(route('onboarding.vehicle'))->assertRedirect(route('onboarding.products'));
    }

    public function test_cannot_go_back_to_an_earlier_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);

        $this->actingAs($user)->get(route('onboarding.products'))->assertRedirect(route('onboarding.vehicle'));
    }

    public function test_skip_route_is_always_reachable(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->post(route('onboarding.skip'))->assertRedirect(route('dashboard'));
    }

    public function test_a_workshop_that_finished_onboarding_is_never_redirected(): void
    {
        [$user] = $this->createDirectorWithWorkshop();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
