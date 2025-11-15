<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * Display a listing of clients
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workshop = $request->user()->workshop;

        $clients = $workshop->clients()
            ->withCount('vehicles')
            ->latest()
            ->paginate(20);

        return ClientResource::collection($clients);
    }

    /**
     * Store a newly created client
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $workshop = $request->user()->workshop;

        $client = $workshop->clients()->create($validated);

        return response()->json([
            'message' => 'Client created successfully',
            'client' => new ClientResource($client),
        ], 201);
    }

    /**
     * Display the specified client
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        // Authorization check
        if ($client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client->load('vehicles.serviceLogs');

        return response()->json([
            'client' => new ClientResource($client),
        ]);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        // Authorization check
        if ($client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client updated successfully',
            'client' => new ClientResource($client),
        ]);
    }

    /**
     * Remove the specified client
     */
    public function destroy(Request $request, Client $client): JsonResponse
    {
        // Authorization check
        if ($client->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully',
        ]);
    }
}
