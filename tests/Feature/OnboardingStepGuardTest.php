<?php

namespace Tests\Feature;

use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingStepGuardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    /**
     * Regression test for the CRITICAL finding: prior to the fix, the onboarding
     * endpoints only null-checked currentWorkshop() and never verified the workshop
     * was actually AT that step (or onboarding at all). Any authenticated user of any
     * workshop — including an already-onboarded production tenant (onboarding_step is
     * null by default, per CreatesTestWorkshop) — could hit these routes directly to
     * bypass the normal products/clients/vehicles permission gates and flip the
     * workshop's onboarding_step, locking the whole tenant into the wizard.
     */
    public function test_onboarding_endpoints_are_forbidden_for_a_workshop_that_is_not_onboarding(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $this->assertNull($workshop->onboarding_step);

        $this->actingAs($user)->get(route('onboarding.products'))->assertForbidden();

        $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [$globalProduct->id],
        ])->assertForbidden();

        $this->actingAs($user)->get(route('onboarding.vehicle'))->assertForbidden();

        $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ])->assertForbidden();

        $this->actingAs($user)->post(route('onboarding.skip'))->assertForbidden();

        // None of these attempts should have mutated the workshop's onboarding state.
        $this->assertNull($workshop->fresh()->onboarding_step);
        $this->assertNull($workshop->fresh()->onboarding_completed_at);
    }

    /**
     * Defense-in-depth: EnsureOnboardingComplete already redirects (302) cross-step
     * access while a workshop is actively onboarding (see OnboardingGateTest's
     * "cannot skip ahead" / "cannot go back" cases). This test isolates the
     * controller-level step guard itself (independent of that middleware) to confirm
     * it also refuses a step's store endpoint when the workshop is on a different
     * onboarding step.
     */
    public function test_products_step_route_is_forbidden_when_workshop_is_on_the_vehicle_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'vehicle']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class)
            ->actingAs($user)->post(route('onboarding.products.store'), [
                'global_product_ids' => [$globalProduct->id],
            ])->assertForbidden();
    }

    public function test_vehicle_step_route_is_forbidden_when_workshop_is_on_the_products_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class)
            ->actingAs($user)->post(route('onboarding.vehicle.store'), [
                'name' => 'Aziz Karimov',
                'phone' => '+998901234567',
                'plate_number' => '01A777AA',
                'make' => 'Nexia',
            ])->assertForbidden();
    }
}
