<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $categories = $workshop->categories()
            ->withCount('products')
            ->latest()
            ->paginate(10);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $workshop = $request->user()->currentWorkshop();
        $validated['slug'] = Str::slug($validated['name']);
        $workshop->categories()->create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategoriya muvaffaqiyatli qo\'shildi!');
    }

    public function edit(Request $request, Category $category): Response
    {
        if ($category->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }

        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        if ($category->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategoriya ma\'lumotlari yangilandi!');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategoriya o\'chirildi!');
    }
}
