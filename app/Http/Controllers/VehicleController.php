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
     * Search for a vehicle by plate number.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string',
        ]);

        $user = $request->user();
        $workshop = $user->workshop;

        $vehicle = Vehicle::whereHas('client', function ($q) use ($workshop, $user) {
            $q->where('workshop_id', $workshop->id);

            // Branch filtering based on user role
            if (!$user->canAccessAllBranches()) {
                $q->where('branch_id', $user->branch_id);
            }
        })
        ->where('plate_number', $validated['plate_number'])
        ->with(['client', 'latestService'])
        ->first();

        return response()->json([
            'found' => $vehicle ? true : false,
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->workshop;

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
        $workshop = $user->workshop;

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

        $user = $request->user();

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
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
        $user = $request->user();
        $workshop = $user->workshop;

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $vehicle->load(['client', 'serviceLogs' => function ($query) {
            $query->latest('service_date');
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
            ->get(['id', 'name', 'unit', 'selling_price', 'stock_quantity', 'category_id']);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'products' => $products,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Vehicle $vehicle): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $vehicle->client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->workshop;

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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
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
        ]);

        // Tekshirish: Client shu ustaxonaga tegishli ekanligini
        $client = Client::findOrFail($validated['client_id']);
        if ($client->workshop_id !== $user->workshop->id) {
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
        if ($vehicle->client->workshop_id !== $user->workshop->id) {
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
