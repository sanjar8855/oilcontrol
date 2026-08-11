<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceLogResource;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceLogController extends Controller
{
    /**
     * Display a listing of service logs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workshop = $request->user()->workshop;

        $serviceLogs = ServiceLog::whereHas('vehicle.client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
            ->with(['vehicle.client'])
            ->latest('service_date')
            ->paginate(20);

        return ServiceLogResource::collection($serviceLogs);
    }

    /**
     * Store a newly created service log
     */
    public function store(Request $request, ReminderService $reminderService): JsonResponse
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

        // Check if vehicle belongs to user's workshop
        $vehicle = Vehicle::with('client')->findOrFail($validated['vehicle_id']);
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceLog = ServiceLog::create($validated);

        // Create/recalculate automatic reminders based on latest odometer reading
        $reminderService->recalculateForVehicle($serviceLog->vehicle_id);

        $serviceLog->load(['vehicle.client', 'reminders']);

        return response()->json([
            'message' => 'Service log created successfully',
            'service_log' => new ServiceLogResource($serviceLog),
        ], 201);
    }

    /**
     * Display the specified service log
     */
    public function show(Request $request, ServiceLog $serviceLog): JsonResponse
    {
        // Authorization check
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceLog->load(['vehicle.client', 'reminders']);

        return response()->json([
            'service_log' => new ServiceLogResource($serviceLog),
        ]);
    }

    /**
     * Update the specified service log
     */
    public function update(Request $request, ServiceLog $serviceLog, ReminderService $reminderService): JsonResponse
    {
        // Authorization check
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        // Check if new vehicle belongs to user's workshop
        $vehicle = Vehicle::with('client')->findOrFail($validated['vehicle_id']);
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceLog->update($validated);

        // Probeg o'zgargan bo'lishi mumkin — eslatmalarni qayta hisoblaymiz
        $reminderService->recalculateForVehicle($serviceLog->vehicle_id);

        $serviceLog->load(['vehicle.client', 'reminders']);

        return response()->json([
            'message' => 'Service log updated successfully',
            'service_log' => new ServiceLogResource($serviceLog),
        ]);
    }

    /**
     * Remove the specified service log
     */
    public function destroy(Request $request, ServiceLog $serviceLog): JsonResponse
    {
        // Authorization check
        if ($serviceLog->vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceLog->delete();

        return response()->json([
            'message' => 'Service log deleted successfully',
        ]);
    }
}
