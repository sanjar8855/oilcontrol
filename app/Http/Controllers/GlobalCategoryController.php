<?php

namespace App\Http\Controllers;

use App\Models\GlobalCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlobalCategoryController extends Controller
{
    /**
     * Display a listing of global categories.
     */
    public function index(): Response
    {
        $categories = GlobalCategory::withCount('globalProducts')
            ->orderBy('name')
            ->get();

        return Inertia::render('GlobalCategories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created global category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:global_categories,name',
        ]);

        GlobalCategory::create(['name' => $validated['name'], 'is_active' => true]);

        return redirect()->route('global-categories.index')
            ->with('success', 'Kategoriya qo\'shildi!');
    }

    /**
     * Update the specified global category.
     */
    public function update(Request $request, GlobalCategory $globalCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:global_categories,name,' . $globalCategory->id,
            'is_active' => 'boolean',
        ]);

        $globalCategory->update($validated);

        return redirect()->route('global-categories.index')
            ->with('success', 'Kategoriya yangilandi!');
    }

    /**
     * Remove the specified global category, unless products still use it.
     */
    public function destroy(GlobalCategory $globalCategory): RedirectResponse
    {
        if ($globalCategory->globalProducts()->exists()) {
            return redirect()->route('global-categories.index')
                ->with('error', 'Bu kategoriyaga mahsulotlar bog\'langan, avval ularni boshqa kategoriyaga o\'tkazing.');
        }

        $globalCategory->delete();

        return redirect()->route('global-categories.index')
            ->with('success', 'Kategoriya o\'chirildi!');
    }
}
