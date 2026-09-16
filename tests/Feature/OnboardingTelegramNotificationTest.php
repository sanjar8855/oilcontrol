<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTelegramIsVerified;
use App\Models\GlobalProduct;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingTelegramNotificationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    private function completeOnboardingUpToResult($user, $workshop): void
    {
        $workshop->update(['onboarding_step' => 'products']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi (Cobalt)', 'unit' => 'litr', 'is_active' => true]);

        $this->actingAs($user)->post(route('onboarding.products.store'), [
            'items' => [
                ['global_product_id' => $globalProduct->id, 'stock_quantity' => 10, 'purchase_price' => 15000, 'selling_price' => 20000],
            ],
        ]);

        $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Cobalt',
        ]);

        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $product = $workshop->fresh()->products()->firstOrFail();

        $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Birinchi servis',
            'products' => [
                ['id' => $product->id, 'quantity' => 2, 'unit_price' => 20000],
            ],
        ]);
    }

    public function test_finishing_onboarding_sends_a_sample_result_message_when_telegram_is_verified(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '999888', 'telegram_verified_at' => now()]);
        $this->completeOnboardingUpToResult($user, $workshop);

        $this->actingAs($user)->post(route('onboarding.finish'))->assertRedirect(route('dashboard'));

        $this->assertNotNull($capturedText);
        $this->assertStringContainsString('40 000', $capturedText);
    }

    public function test_finishing_onboarding_sends_nothing_when_telegram_is_not_verified(): void
    {
        Http::fake();

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $this->completeOnboardingUpToResult($user, $workshop);
        $user->update(['telegram_chat_id' => null, 'telegram_verified_at' => null]);

        // A user reaching this point unverified can't happen through normal
        // navigation (the global gate would already have redirected them to
        // /telegram/verify) — bypass it here to exercise finish()'s own
        // defensive check in isolation.
        $this->withoutMiddleware(EnsureTelegramIsVerified::class);

        $this->actingAs($user)->post(route('onboarding.finish'))->assertRedirect(route('dashboard'));

        Http::assertNothingSent();
    }
}
