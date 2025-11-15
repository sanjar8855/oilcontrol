<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\Vehicle;
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

        // Agar query parametrda vehicle_id berilgan bo'lsa
        $selectedVehicleId = $request->query('vehicle_id');

        return Inertia::render('ServiceLogs/Create', [
            'vehicles' => $vehicles,
            'selectedVehicleId' => $selectedVehicleId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
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

        $serviceLog = ServiceLog::create($validated);

        // Avtomatik eslatma yaratish (keyinchalik)
        // $this->createReminder($serviceLog);

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

        $serviceLog->load(['vehicle.client', 'reminders']);

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
