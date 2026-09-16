<?php

namespace App\Http\Controllers;

use App\Models\GlobalProduct;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Services\OnboardingCleanupService;
use App\Services\ProductCreationService;
use App\Services\UserTelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Onboarding'da tanlash uchun ko'rsatiladigan mahsulotlar — ataylab
     * qo'lda saqlanadigan qisqa ro'yxat (butun umumiy katalogdan emas),
     * chunki katalog boshqa ustaxonalar tomonidan avtomatik to'ldiriladi
     * va yangi ro'yxatdan o'tuvchi uchun mos emas. GlobalProductTestSeeder
     * shu nomlar bilan mos yozuvlarni yaratadi.
     */
    private const STARTER_PRODUCT_NAMES = [
        'Motor moyi (Cobalt)', 'Havo filtri (Cobalt)', 'Moy filtri (Cobalt)',
    ];

    public function products(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'products', 403);

        $search = $request->filled('search') ? $request->string('search')->toString() : null;

        $query = GlobalProduct::where('is_active', true)
            ->whereIn('name', self::STARTER_PRODUCT_NAMES);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return Inertia::render('Onboarding/Products', [
            'products' => $query->orderBy('name')->get(['id', 'name', 'unit']),
            'search' => $search,
        ]);
    }

    public function storeProducts(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.global_product_id' => 'required|integer|exists:global_products,id',
            'items.*.stock_quantity' => 'required|integer|min:0',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'products', 403);

        $items = collect($validated['items'])->map(fn ($item) => [
            'global_product_id' => $item['global_product_id'],
            'purchase_price' => $item['purchase_price'],
            'selling_price' => $item['selling_price'],
            'stock_quantity' => $item['stock_quantity'],
            'min_stock_level' => 0,
        ])->all();

        DB::beginTransaction();
        try {
            $productCreationService->copySelectionToWorkshop($workshop, $user, $items, $user->branch_id);
            $workshop->advanceOnboarding('vehicle');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
        }

        return redirect()->route('onboarding.vehicle');
    }

    public function vehicle(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'vehicle', 403);

        return Inertia::render('Onboarding/Vehicle');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'make' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'vehicle', 403);

        try {
            $vehicle = DB::transaction(function () use ($workshop, $user, $validated) {
                $client = $workshop->clients()->create([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'branch_id' => $user->branch_id,
                ]);

                $vehicle = Vehicle::create([
                    'client_id' => $client->id,
                    'plate_number' => $validated['plate_number'],
                    'make' => $validated['make'],
                ]);

                $workshop->advanceOnboarding('sale');

                return $vehicle;
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
        }

        return redirect()->route('onboarding.sale');
    }

    public function sale(Request $request): Response|RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'sale', 403);

        $vehicle = Vehicle::whereHas('client', fn ($q) => $q->where('workshop_id', $workshop->id))
            ->with('client')
            ->orderBy('id')
            ->first();

        if (!$vehicle) {
            $workshop->advanceOnboarding('vehicle');

            return redirect()->route('onboarding.vehicle');
        }

        $products = $workshop->products()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'selling_price', 'selling_price_uzs', 'selling_price_usd', 'currency', 'stock_quantity']);
        $products->each(fn ($product) => $product->selling_price = $product->getSellingPrice());

        return Inertia::render('Onboarding/Sale', [
            'vehicle' => $vehicle,
            'products' => $products,
        ]);
    }

    public function result(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'result', 403);

        return Inertia::render('Onboarding/Result', $this->resultSummary($workshop));
    }

    /**
     * @return array{products: \Illuminate\Support\Collection, saleTotal: float, profit: float, reminders: \Illuminate\Support\Collection}
     */
    private function resultSummary(Workshop $workshop): array
    {
        $products = $workshop->products()
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock_quantity']);

        $serviceLog = ServiceLog::whereHas('vehicle.client', fn ($q) => $q->where('workshop_id', $workshop->id))
            ->with(['products', 'reminders'])
            ->latest('id')
            ->first();

        $profit = 0;
        foreach ($serviceLog?->products ?? [] as $product) {
            $profit += ($product->pivot->unit_price - $product->purchase_price) * $product->pivot->quantity;
        }

        return [
            'products' => $products,
            'saleTotal' => (float) ($serviceLog->total_amount ?? 0),
            'profit' => $profit,
            'reminders' => $serviceLog?->reminders->pluck('scheduled_date')->map(fn ($date) => $date->toDateString())->sort()->values() ?? collect(),
        ];
    }

    public function finish(Request $request, OnboardingCleanupService $cleanup, UserTelegramBotService $telegramBot): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'result', 403);

        $user = $request->user();

        if ($user->telegram_verified_at) {
            try {
                $telegramBot->sendOnboardingResult($user, $this->resultSummary($workshop));
            } catch (\Exception $e) {
                report($e);
            }
        }

        $cleanup->purge($workshop);
        $workshop->advanceOnboarding(null);

        return redirect()->route('dashboard')
            ->with('success', "Tabriklaymiz! Tizimdan foydalanishni o'rganish yakunlandi.");
    }

    public function skip(Request $request, OnboardingCleanupService $cleanup): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->isOnboarding(), 403);

        $cleanup->purge($workshop);
        $workshop->advanceOnboarding(null);

        return redirect()->route('dashboard');
    }
}
