<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workshop = $request->user()->workshop;

        $categories = $workshop->categories()
            ->withCount('products')
            ->latest()
            ->paginate(20);

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $workshop = $request->user()->workshop;

        // Auto-generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        $category = $workshop->categories()->create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        // Authorization check
        if ($category->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->load('products');

        return response()->json([
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        // Authorization check
        if ($category->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Update slug if name changed
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        // Authorization check
        if ($category->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
