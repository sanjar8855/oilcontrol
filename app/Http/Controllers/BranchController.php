<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $branches = $workshop->branches()
            ->withCount(['users', 'clients', 'products'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Branches/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        $branchLimit = $workshop->branchLimit();
        if ($branchLimit !== null && $workshop->branches()->count() >= $branchLimit) {
            return back()->with('error', "Tarifingizda filiallar soni {$branchLimit} tagacha cheklangan. Ko'proq filial uchun tarifni yangilang.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where('workshop_id', $workshop->id),
            ],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $workshop->branches()->create($validated);

        return redirect()->route('branches.index')
            ->with('success', 'Filial muvaffaqiyatli qo\'shildi!');
    }

    public function edit(Request $request, Branch $branch): Response
    {
        if ($branch->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        if ($branch->workshop_id !== $workshop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where('workshop_id', $workshop->id)->ignore($branch->id),
            ],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', 'Filial ma\'lumotlari yangilandi!');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        if ($branch->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Filial o\'chirildi!');
    }
}
