<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $workshop = $request->user()->workshop;

        $vehicles = Vehicle::whereHas('client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
            ->with(['client', 'latestService'])
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
        $workshop = $request->user()->workshop;

        // Mijozlar ro'yxati
        $clients = $workshop->clients()->orderBy('name')->get(['id', 'name']);

        // Agar query parametrda client_id berilgan bo'lsa
        $selectedClientId = $request->query('client_id');

        return Inertia::render('Vehicles/Create', [
            'clients' => $clients,
            'selectedClientId' => $selectedClientId,
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
        ]);

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $vehicle = Vehicle::create($validated);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Avtomobil muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Vehicle $vehicle): Response
    {
        // Avtorizatsiya
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $vehicle->load(['client', 'serviceLogs' => function ($query) {
            $query->latest('service_date');
        }]);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Vehicle $vehicle): Response
    {
        // Avtorizatsiya
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $workshop = $request->user()->workshop;
        $clients = $workshop->clients()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        // Avtorizatsiya
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'nullable|string|max:20',
            'vin' => 'nullable|string|max:50',
        ]);

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $request->user()->workshop->id) {
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
        // Avtorizatsiya
        if ($vehicle->client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $clientId = $vehicle->client_id;
        $vehicle->delete();

        return redirect()->route('clients.show', $clientId)
            ->with('success', 'Avtomobil o\'chirildi!');
    }
}
