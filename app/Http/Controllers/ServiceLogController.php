<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $workshop = $request->user()->workshop;

        $serviceLogs = ServiceLog::whereHas('vehicle.client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
            ->with(['vehicle.client'])
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
        $workshop = $request->user()->workshop;

        // Avtomobillar ro'yxati (mijoz nomi bilan)
        $vehicles = Vehicle::whereHas('client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
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

        // Mahsulotlar ro'yxati (faqat aktiv va omborda bor)
        $products = $workshop->products()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
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
        ]);

        // Tekshirish: Vehicle shu ustaxonaga tegishli ekanligini
        $vehicle = Vehicle::with('client')->findOrFail($validated['vehicle_id']);
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $serviceLog = ServiceLog::create($validated);

        // Mahsulotlarni saqlash va omborda miqdorni kamaytirish
        if (!empty($validated['products'])) {
            foreach ($validated['products'] as $productData) {
                $product = \App\Models\Product::findOrFail($productData['id']);

                // Tekshirish: Mahsulot bu workshop'ga tegishli ekanligini
                if ($product->workshop_id !== $request->user()->workshop->id) {
                    abort(403);
                }

                // Mahsulotni service log'ga biriktirish
                $totalPrice = $productData['quantity'] * $productData['unit_price'];
                $serviceLog->products()->attach($product->id, [
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total_price' => $totalPrice,
                ]);

                // Omborda miqdorni kamaytirish (faqat track_inventory=true bo'lsa)
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $productData['quantity']);
                }
            }
        }

        // Avtomatik eslatmalarni yaratish
        $reminderService->createRemindersForServiceLog($serviceLog);

        return redirect()->route('vehicles.show', $vehicle->id)
            ->with('success', 'Servis yozuvi muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ServiceLog $serviceLog): Response
    {
        // Avtorizatsiya
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
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
        // Avtorizatsiya
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $workshop = $request->user()->workshop;

        $vehicles = Vehicle::whereHas('client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
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
        // Avtorizatsiya
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
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
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
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
        // Avtorizatsiya
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $vehicleId = $serviceLog->vehicle_id;
        $serviceLog->delete();

        return redirect()->route('vehicles.show', $vehicleId)
            ->with('success', 'Servis yozuvi o\'chirildi!');
    }
}
