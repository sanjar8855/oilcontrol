<?php

namespace Tests\Feature;

use App\Models\GlobalProduct;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingResultStepTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    /**
     * To'liq oqim orqali (mahsulot -> mashina -> savdo) o'tib, 4-qadamda
     * (natija) ko'rsatiladigan foyda/qoldiq/eslatma to'g'ri hisoblanishini
     * va "tugatish" bosilganda BARCHA sinov ma'lumotlari (mahsulotlar bilan
     * birga ProductCreationService yaratgan Xarajat yozuvi ham) tozalanib,
     * dashboard 0 dan boshlanishini tekshiradi.
     */
    public function test_result_step_shows_the_sale_summary_and_finishing_purges_all_onboarding_data(): void
    {
        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi (Cobalt)', 'unit' => 'litr', 'is_active' => true]);

        $this->actingAs($user)->post(route('onboarding.products.store'), [
            'items' => [
                ['global_product_id' => $globalProduct->id, 'stock_quantity' => 10, 'purchase_price' => 15000, 'selling_price' => 20000],
            ],
        ])->assertRedirect(route('onboarding.vehicle'));

        // Onboarding paytida yaratilgan mahsulot uchun ProductCreationService
        // "supplier_id" bo'lmagani sababli Xarajat (initial stock cost) yozadi.
        $this->assertSame(1, $workshop->fresh()->expenses()->count());
        $product = $workshop->fresh()->products()->firstOrFail();
        $this->assertSame(10, $product->stock_quantity);

        $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Cobalt',
        ])->assertRedirect(route('onboarding.sale'));

        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();

        $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Birinchi servis',
            'products' => [
                ['id' => $product->id, 'quantity' => 2, 'unit_price' => 20000],
            ],
        ])->assertRedirect(route('onboarding.result'));

        $this->assertSame('result', $workshop->fresh()->onboarding_step);

        $response = $this->actingAs($user)->get(route('onboarding.result'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Result')
            ->where('saleTotal', 40000)
            // Foyda = (sotuv narxi - tan narxi) x miqdor = (20000 - 15000) x 2 = 10000
            ->where('profit', 10000)
            ->where('products.0.stock_quantity', 8));

        $finishResponse = $this->actingAs($user)->post(route('onboarding.finish'));
        $finishResponse->assertRedirect(route('dashboard'));

        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertNotNull($fresh->onboarding_completed_at);

        $this->assertSame(0, $fresh->clients()->count());
        $this->assertSame(0, $fresh->products()->count());
        $this->assertSame(0, $fresh->expenses()->count());
        $this->assertDatabaseMissing('vehicles', ['plate_number' => '01A777AA']);
        $this->assertDatabaseMissing('service_logs', ['vehicle_id' => $vehicle->id]);

        // Dashboard endi 0 dan boshlanishi kerak.
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertInertia(fn ($page) => $page->where('stats.total_clients', 0)
            ->where('stats.total_products', 0));
    }
}
