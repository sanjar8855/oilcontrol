<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Client;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Avto raqam yoki mijoz telefon raqami bo'yicha qisman (substring) qidiruv.
     * Masalan "aa" so'rovi "01 A 111 AA" va "01 A 222 AA" ikkisini ham topadi.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        // Bo'sh joy/chiziqchalarsiz, kichik harfda solishtirish uchun
        $term = mb_strtolower(preg_replace('/[\s\-]+/', '', $validated['query']));

        $vehicles = Vehicle::whereHas('client', function ($q) use ($workshop, $user) {
            $q->where('workshop_id', $workshop->id);

            // Branch filtering based on user role
            if (!$user->canAccessAllBranches()) {
                $q->where('branch_id', $user->branch_id);
            }
        })
        ->where(function ($q) use ($term) {
            $q->whereRaw("LOWER(REPLACE(REPLACE(plate_number, ' ', ''), '-', '')) LIKE ?", ["%{$term}%"])
                ->orWhereHas('client', function ($cq) use ($term) {
                    $cq->whereRaw("LOWER(REPLACE(REPLACE(phone, ' ', ''), '-', '')) LIKE ?", ["%{$term}%"]);
                });
        })
        ->with(['client', 'latestService'])
        ->limit(8)
        ->get();

        return response()->json([
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = Vehicle::whereHas('client', function ($q) use ($workshop, $user) {
            $q->where('workshop_id', $workshop->id);

            // Branch filtering based on user role
            if (!$user->canAccessAllBranches()) {
                $q->where('branch_id', $user->branch_id);
            }
        });

        $vehicles = $query->with(['client', 'latestService'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        // Mijozlar ro'yxati - branch filtered
        $clientsQuery = $workshop->clients();

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            $clientsQuery->where('branch_id', $user->branch_id);
        }

        $clients = $clientsQuery->orderBy('name')->get(['id', 'name']);

        // Agar query parametrda client_id berilgan bo'lsa
        $selectedClientId = $request->query('client_id');

        return Inertia::render('Vehicles/Create', [
            'clients' => $clients,
            'selectedClientId' => $selectedClientId,
            'carMakeGroups' => CarMake::optionGroups(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'nullable|string|max:20',
            'vin' => 'nullable|string|max:50',
            'avg_daily_km' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        if (isset($validated['avg_daily_km']) && $validated['avg_daily_km'] > 0) {
            $validated['avg_monthly_km'] = $validated['avg_daily_km'] * 30;
        }
        unset($validated['avg_daily_km']);

        $vehicle = Vehicle::create($validated);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Avtomobil muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Vehicle $vehicle): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $vehicle->load(['client', 'serviceLogs' => function ($query) {
            $query->with('products')->latest('service_date');
        }]);

        // Mahsulotlar ro'yxati (faqat aktiv va omborda bor) - branch filtered
        $productsQuery = $workshop->products()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);

        // Branch filtering for products
        if (!$user->canAccessAllBranches()) {
            $productsQuery->where('branch_id', $user->branch_id);
        }

        $products = $productsQuery
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'selling_price', 'selling_price_uzs', 'selling_price_usd', 'currency', 'stock_quantity', 'category_id']);
        $products->each(fn ($product) => $product->selling_price = $product->getSellingPrice());

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'products' => $products,
            'carModelInfo' => $this->buildCarModelInfo($vehicle, $workshop, $user),
            'isOnboardingHighlight' => $workshop->onboarding_step === 'sale'
                && $vehicle->id === Vehicle::whereHas('client', fn ($q) => $q->where('workshop_id', $workshop->id))
                    ->orderBy('id')
                    ->value('id'),
        ]);
    }

    /**
     * Avtomobil turiga bog'langan texnik ma'lumot (moy/antifriz hajmi) va
     * tavsiya etilgan mahsulotlarni JSON sifatida qaytaradi. Servis yozuvi
     * yaratishda avtomobil tanlanganda tavsiyalarni dinamik yuklash uchun.
     */
    public function carModelInfo(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        if ($vehicle->client->workshop_id !== $workshop->id) {
            abort(403);
        }

        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        return response()->json([
            'carModelInfo' => $this->buildCarModelInfo($vehicle, $workshop, $user),
        ]);
    }

    /**
     * Avtomobil turiga bog'langan texnik ma'lumot va tavsiya etilgan mahsulotlarni tuzish.
     */
    private function buildCarModelInfo(Vehicle $vehicle, Workshop $workshop, User $user): ?array
    {
        $carModel = CarModel::where('name', $vehicle->make)
            ->with(['products' => function ($query) use ($workshop, $user) {
                $query->where('products.workshop_id', $workshop->id)
                    ->where('products.is_active', true)
                    ->where('products.stock_quantity', '>', 0);

                if (!$user->canAccessAllBranches()) {
                    $query->where('products.branch_id', $user->branch_id);
                }
            }])
            ->first();

        if (!$carModel) {
            return null;
        }

        return [
            'oil_capacity_liters' => $carModel->oil_capacity_liters,
            'antifreeze_capacity_min_liters' => $carModel->antifreeze_capacity_min_liters,
            'antifreeze_capacity_max_liters' => $carModel->antifreeze_capacity_max_liters,
            'recommended_products' => $carModel->products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
                'selling_price' => $product->getSellingPrice(),
                'stock_quantity' => $product->stock_quantity,
                'quantity' => (float) $product->pivot->quantity,
            ])->all(),
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Vehicle $vehicle): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->currentWorkshop();

        // Mijozlar ro'yxati - branch filtered
        $clientsQuery = $workshop->clients();

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            $clientsQuery->where('branch_id', $user->branch_id);
        }

        $clients = $clientsQuery->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle,
            'clients' => $clients,
            'carMakeGroups' => CarMake::optionGroups(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'nullable|string|max:20',
            'vin' => 'nullable|string|max:50',
            'avg_daily_km' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['avg_daily_km']) && $validated['avg_daily_km'] > 0) {
            $validated['avg_monthly_km'] = $validated['avg_daily_km'] * 30;
        } else {
            $validated['avg_monthly_km'] = null;
        }
        unset($validated['avg_daily_km']);

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for the new client
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $vehicle->update($validated);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Avtomobil ma\'lumotlari yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $clientId = $vehicle->client_id;
        $vehicle->delete();

        return redirect()->route('clients.show', $clientId)
            ->with('success', 'Avtomobil o\'chirildi!');
    }
}
