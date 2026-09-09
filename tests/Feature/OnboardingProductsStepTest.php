<?php
// tests/Feature/OnboardingProductsStepTest.php
namespace Tests\Feature;

use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingProductsStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_products_step_page_renders(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->get(route('onboarding.products'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Products'));
    }

    public function test_selecting_at_least_one_product_advances_to_the_vehicle_step(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [$globalProduct->id],
        ]);

        $response->assertRedirect(route('onboarding.vehicle'));
        $this->assertSame('vehicle', $workshop->fresh()->onboarding_step);
        $this->assertDatabaseHas('products', [
            'workshop_id' => $workshop->id,
            'global_product_id' => $globalProduct->id,
        ]);
    }

    public function test_submitting_zero_products_fails_validation(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [],
        ]);

        $response->assertSessionHasErrors('global_product_ids');
        $this->assertSame('products', $workshop->fresh()->onboarding_step);
    }

    public function test_skip_ends_onboarding_and_redirects_to_dashboard(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $response = $this->actingAs($user)->post(route('onboarding.skip'));

        $response->assertRedirect(route('dashboard'));
        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertNotNull($fresh->onboarding_completed_at);
    }

    public function test_storing_products_without_a_current_workshop_returns_403_not_500(): void
    {
        // An employee with no branch_id has currentWorkshop() === null (data-inconsistency edge case,
        // but reachable: this route group carries no role/permission middleware).
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user = \App\Models\User::factory()->create(['branch_id' => null]);
        $user->assignRole('employee');
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('onboarding.products.store'), [
            'global_product_ids' => [$globalProduct->id],
        ]);

        $response->assertForbidden();
    }

    public function test_skip_without_a_current_workshop_returns_403_not_500(): void
    {
        // Same null-currentWorkshop() edge case as storeProducts(), but for skip(): calling
        // advanceOnboarding() directly on a null return value would be an uncaught Error.
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user = \App\Models\User::factory()->create(['branch_id' => null]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->post(route('onboarding.skip'));

        $response->assertForbidden();
    }
}
