<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->workshop;

        $query = $workshop->clients();

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            // Manager/Employee can only see their branch's clients
            $query->where('branch_id', $user->branch_id);
        }

        $clients = $query->with('vehicles')
            ->latest()
            ->paginate(10);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * Store a newly created resource in storage (Client + Vehicle).
     */
    public function storeWithVehicle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'avg_daily_km' => 'nullable|integer|min:0',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'make' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $workshop = $user->workshop;

        // Set branch_id: Directors can choose, but managers/employees use their own branch
        $branchId = $user->canAccessAllBranches()
            ? ($request->input('branch_id') ?? $user->branch_id)
            : $user->branch_id;

        // Convert avg_daily_km to avg_monthly_km
        $avgMonthlyKm = null;
        if (isset($validated['avg_daily_km']) && $validated['avg_daily_km'] > 0) {
            $avgMonthlyKm = $validated['avg_daily_km'] * 30;
        }

        // Create client
        $client = $workshop->clients()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'default_avg_monthly_km' => $avgMonthlyKm,
            'branch_id' => $branchId,
        ]);

        // Create vehicle
        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate_number' => $validated['plate_number'],
            'make' => $validated['make'] ?? 'Noma\'lum',
        ]);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Mijoz va avtomobil muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $workshop = $user->workshop;

        // Set branch_id: Directors can choose, but managers/employees use their own branch
        $validated['branch_id'] = $user->canAccessAllBranches()
            ? ($request->input('branch_id') ?? $user->branch_id)
            : $user->branch_id;

        $workshop->clients()->create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Mijoz muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Client $client): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $client->load('vehicles.serviceLogs');

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Client $client): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Mijoz ma\'lumotlari yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($client->workshop_id !== $user->workshop->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $client->branch_id !== $user->branch_id) {
            abort(403);
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Mijoz o\'chirildi!');
    }
}
