<?php

namespace Tests\Feature;

use App\Models\GlobalProduct;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFullCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_registered_workshop_completes_the_full_onboarding_cycle(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->post('/register', [
            'name' => 'Full Cycle Test',
            'phone' => '+998907778899',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = \App\Models\User::where('phone', '+998907778899')->firstOrFail();
        $user->update(['telegram_verified_at' => now()]);
        // Auth::login() during /register cached the pre-update $user instance on the
        // session guard; refresh it so the guard's in-memory user reflects the update
        // instead of the guard silently serving the stale (unverified) copy.
        \Illuminate\Support\Facades\Auth::setUser($user->fresh());
        $workshop = \App\Models\Workshop::where('user_id', $user->id)->firstOrFail();

        $this->get('/dashboard')->assertRedirect(route('onboarding.products'));

        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi 5W-30', 'unit' => 'litr', 'is_active' => true]);
        $this->post(route('onboarding.products.store'), [
            'items' => [
                ['global_product_id' => $globalProduct->id, 'stock_quantity' => 10, 'purchase_price' => 15000, 'selling_price' => 20000],
            ],
        ])->assertRedirect(route('onboarding.vehicle'));

        $this->get('/dashboard')->assertRedirect(route('onboarding.vehicle'));

        $vehicleResponse = $this->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Nexia',
        ]);
        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $vehicleResponse->assertRedirect(route('onboarding.sale'));

        $this->get('/dashboard')->assertRedirect(route('onboarding.sale'));

        $this->get(route('onboarding.sale'))
            ->assertInertia(fn ($page) => $page->component('Onboarding/Sale'));

        $saleResponse = $this->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Servis',
            'manual_items' => [
                ['name' => 'Ish haqi', 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]);
        $saleResponse->assertRedirect(route('onboarding.result'));
        $this->assertSame('result', $workshop->fresh()->onboarding_step);

        $this->get('/dashboard')->assertRedirect(route('onboarding.result'));

        $this->get(route('onboarding.result'))
            ->assertInertia(fn ($page) => $page->component('Onboarding/Result'));

        $finishResponse = $this->post(route('onboarding.finish'));
        $finishResponse->assertRedirect(route('dashboard'));

        $this->assertNull($workshop->fresh()->onboarding_step);
        $this->assertNotNull($workshop->fresh()->onboarding_completed_at);

        // Onboarding'da yaratilgan sinov ma'lumotlari tozalangan bo'lishi kerak.
        $this->assertSame(0, $workshop->fresh()->clients()->count());
        $this->assertSame(0, $workshop->fresh()->products()->count());
        $this->assertSame(0, $workshop->fresh()->expenses()->count());

        $this->get('/dashboard')->assertOk();
    }

    public function test_a_pre_existing_workshop_never_sees_onboarding(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user = \App\Models\User::factory()->create();
        $user->assignRole('director');
        \App\Models\Workshop::create([
            'user_id' => $user->id,
            'name' => 'Old Workshop',
            'phone' => '+998901112233',
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
