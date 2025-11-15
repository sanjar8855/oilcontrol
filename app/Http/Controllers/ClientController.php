<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
        $workshop = $request->user()->workshop;

        $clients = $workshop->clients()
            ->with('vehicles')
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $workshop = $request->user()->workshop;

        $workshop->clients()->create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Mijoz muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Client $client): Response
    {
        // Faqat o'z ustaxonasining mijozlarini ko'rish mumkin
        if ($client->workshop_id !== $request->user()->workshop->id) {
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
        // Faqat o'z ustaxonasining mijozlarini tahrirlash mumkin
        if ($client->workshop_id !== $request->user()->workshop->id) {
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
        // Faqat o'z ustaxonasining mijozlarini yangilash mumkin
        if ($client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
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
        // Faqat o'z ustaxonasining mijozlarini o'chirish mumkin
        if ($client->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Mijoz o\'chirildi!');
    }
}
