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

    public function test_sale_step_with_no_vehicle_self_heals_instead_of_looping(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.vehicle'));

        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);

        $this->actingAs($user)->get(route('onboarding.vehicle'))->assertOk();
    }

    public function test_vehicles_show_and_service_logs_store_are_directly_reachable_on_the_sale_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);
        $client = $workshop->clients()->create(['name' => 'Test', 'phone' => '+998900000000']);
        $vehicle = Vehicle::create(['client_id' => $client->id, 'plate_number' => '01A123AA', 'make' => 'Chevrolet']);

        $this->actingAs($user)->get(route('vehicles.show', $vehicle))->assertOk();

        $this->actingAs($user)->post(route('service-logs.store'), [])->assertSessionHasErrors();
    }

    public function test_logout_is_reachable_and_does_not_redirect_back_into_onboarding(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    }
}
