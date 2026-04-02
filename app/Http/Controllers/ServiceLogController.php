<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Services\ReminderService;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->workshop;

        $query = ServiceLog::whereHas('vehicle.client', function ($q) use ($workshop) {
            $q->where('workshop_id', $workshop->id);
        });

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            // Manager/Employee can only see their branch's service logs
            $query->where('branch_id', $user->branch_id);
        }

        $serviceLogs = $query->with(['vehicle.client'])
            ->latest('service_date')
            ->paginate(20);

        return Inertia::render('ServiceLogs/Index', [
            'serviceLogs' => $serviceLogs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->workshop;

        // Avtomobillar ro'yxati (mijoz nomi bilan) - branch filtered
        $vehiclesQuery = Vehicle::whereHas('client', function ($query) use ($workshop, $user) {
            $query->where('workshop_id', $workshop->id);

            // Branch filtering
            if (!$user->canAccessAllBranches()) {
                $query->where('branch_id', $user->branch_id);
            }
        });

        $vehicles = $vehiclesQuery
            ->with('client')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'label' => $vehicle->client->name . ' - ' . $vehicle->make . ' ' . $vehicle->model,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                ];
            });

        // Mahsulotlar ro'yxati (faqat aktiv va omborda bor) - branch filtered
        $productsQuery = $workshop->products()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);

        // Branch filtering for products
        if (!$user->canAccessAllBranches()) {
            $productsQuery->where('branch_id', $user->branch_id);
        }

        $products = $productsQuery
            ->select('id', 'name', 'selling_price', 'stock_quantity', 'unit')
            ->orderBy('name')
            ->get();

        // Agar query parametrda vehicle_id berilgan bo'lsa
        $selectedVehicleId = $request->query('vehicle_id');

        return Inertia::render('ServiceLogs/Create', [
            'vehicles' => $vehicles,
            'products' => $products,
            'selectedVehicleId' => $selectedVehicleId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ReminderService $reminderService): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_date' => 'required|date',
            'odometer_reading' => 'required|integer|min:0',
            'next_service_km' => 'required|integer|min:1000|max:50000',
            'avg_monthly_km' => 'nullable|integer|min:0',
            'service_type' => 'required|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'products' => 'nullable|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            // Yangi to'lov maydonlari
            'payment_type' => 'required|in:cash,credit,installment',
            'payment_status' => 'nullable|in:paid,partial,unpaid',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date|after_or_equal:service_date',
            'currency' => 'nullable|in:USD,UZS',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_consignment' => 'nullable|boolean',
            'consignment_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = $request->user();

        // Tekshirish: Vehicle shu ustaxonaga tegishli ekanligini
        $vehicle = Vehicle::with('client')->findOrFail($validated['vehicle_id']);
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        // Set branch_id from the vehicle's client branch
        $validated['branch_id'] = $vehicle->client->branch_id;

        // Default qiymatlar
        $validated['currency'] = $validated['currency'] ?? 'UZS';
        $validated['payment_status'] = $validated['payment_status'] ?? 'unpaid';
        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;
        $validated['discount_percentage'] = $validated['discount_percentage'] ?? 0;
        $validated['is_consignment'] = $validated['is_consignment'] ?? false;

        DB::beginTransaction();
        try {
            $serviceLog = ServiceLog::create($validated);

            $stockService = new StockMovementService();
            $totalProductsCost = 0;

            // Mahsulotlarni saqlash va FIFO orqali chiqarish
            if (!empty($validated['products'])) {
                foreach ($validated['products'] as $productData) {
                    $product = \App\Models\Product::findOrFail($productData['id']);

                    // Tekshirish: Mahsulot bu workshop'ga tegishli ekanligini
                    if ($product->workshop_id !== $user->workshop->id) {
                        abort(403);
                    }

                    // Check branch access for products
                    if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
                        abort(403);
                    }

                    // Mahsulotni service log'ga biriktirish
                    $totalPrice = $productData['quantity'] * $productData['unit_price'];
                    $serviceLog->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'unit_price' => $productData['unit_price'],
                        'total_price' => $totalPrice,
                    ]);

                    $totalProductsCost += $totalPrice;

                    // FIFO orqali omborda miqdorni kamaytirish
                    if ($product->track_inventory && $product->stock_quantity >= $productData['quantity']) {
                        try {
                            $stockService->recordOutgoing(
                                productId: $product->id,
                                quantity: $productData['quantity'],
                                referenceType: 'ServiceLog',
                                referenceId: $serviceLog->id,
                                notes: "Servis: {$serviceLog->service_type} - {$vehicle->make} {$vehicle->model}"
                            );
                        } catch (\Exception $e) {
                            // Agar FIFO xatolik bersa, oddiy decrement ishlatamiz
                            $product->decrement('stock_quantity', $productData['quantity']);
                        }
                    }
                }
            }

            // Total amount ni hisoblash
            $serviceLog->calculateTotal();

            // Agar to'langan summa berilgan bo'lsa, to'lovni qayd qilish
            if ($validated['payment_type'] === 'cash' && $validated['paid_amount'] > 0) {
                $serviceLog->addPayment(
                    amount: $validated['paid_amount'],
                    method: 'cash',
                    notes: 'Boshlang\'ich to\'lov'
                );
            }

            // Avtomatik eslatmalarni yaratish
            $reminderService->createRemindersForServiceLog($serviceLog);

            DB::commit();

            return redirect()->route('vehicles.show', $vehicle->id)
                ->with('success', 'Servis yozuvi muvaffaqiyatli qo\'shildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ServiceLog $serviceLog): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($serviceLog->vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $serviceLog->branch_id !== $user->branch_id) {
            abort(403);
        }

        $serviceLog->load(['vehicle.client', 'reminders', 'products']);

        return Inertia::render('ServiceLogs/Show', [
            'serviceLog' => $serviceLog,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ServiceLog $serviceLog): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($serviceLog->vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $serviceLog->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->workshop;

        $vehiclesQuery = Vehicle::whereHas('client', function ($query) use ($workshop, $user) {
            $query->where('workshop_id', $workshop->id);

            // Branch filtering
            if (!$user->canAccessAllBranches()) {
                $query->where('branch_id', $user->branch_id);
            }
        });

        $vehicles = $vehiclesQuery
            ->with('client')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'label' => $vehicle->client->name . ' - ' . $vehicle->make . ' ' . $vehicle->model,
                ];
            });

        return Inertia::render('ServiceLogs/Edit', [
            'serviceLog' => $serviceLog,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceLog $serviceLog): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($serviceLog->vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $serviceLog->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_date' => 'required|date',
            'odometer_reading' => 'required|integer|min:0',
            'next_service_km' => 'required|integer|min:1000|max:50000',
            'avg_monthly_km' => 'nullable|integer|min:0',
            'service_type' => 'required|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Tekshirish: Vehicle shu ustaxonaga tegishli ekanligini
        $vehicle = Vehicle::with('client')->findOrFail($validated['vehicle_id']);
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for the new vehicle
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $serviceLog->update($validated);

        return redirect()->route('service-logs.show', $serviceLog)
            ->with('success', 'Servis yozuvi yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ServiceLog $serviceLog): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($serviceLog->vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $serviceLog->branch_id !== $user->branch_id) {
            abort(403);
        }

        $vehicleId = $serviceLog->vehicle_id;
        $serviceLog->delete();

        return redirect()->route('vehicles.show', $vehicleId)
            ->with('success', 'Servis yozuvi o\'chirildi!');
    }
}
