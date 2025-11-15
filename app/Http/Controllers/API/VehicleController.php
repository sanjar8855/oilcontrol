<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workshop = $request->user()->workshop;

        $vehicles = Vehicle::whereHas('client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
            ->with(['client', 'latestService'])
            ->latest()
            ->paginate(20);

        return VehicleResource::collection($vehicles);
    }

    /**
     * Store a newly created vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|max:50',
            'vin' => 'nullable|string|max:50',
        ]);

        // Check if client belongs to user's workshop
        $client = \App\Models\Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle = Vehicle::create($validated);
        $vehicle->load('client');

        return response()->json([
            'message' => 'Vehicle created successfully',
            'vehicle' => new VehicleResource($vehicle),
        ], 201);
    }

    /**
     * Display the specified vehicle
     */
    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Authorization check
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->load(['client', 'serviceLogs.reminders']);

        return response()->json([
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Authorization check
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|max:50',
            'vin' => 'nullable|string|max:50',
        ]);

        // Check if new client belongs to user's workshop
        $client = \App\Models\Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->update($validated);
        $vehicle->load('client');

        return response()->json([
            'message' => 'Vehicle updated successfully',
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    /**
     * Remove the specified vehicle
     */
    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Authorization check
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully',
        ]);
    }
}
