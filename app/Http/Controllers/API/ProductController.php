<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workshop = $request->user()->workshop;

        $query = $workshop->products()
            ->with('category');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
        }

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'min_stock_level' => 'integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        $workshop = $request->user()->workshop;

        // Verify category belongs to workshop if provided
        if (isset($validated['category_id'])) {
            $category = $workshop->categories()->find($validated['category_id']);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }
        }

        $product = $workshop->products()->create($validated);

        // Create initial inventory transaction if stock_quantity > 0
        if ($product->stock_quantity > 0 && $product->track_inventory) {
            InventoryTransaction::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock_quantity,
                'quantity_before' => 0,
                'quantity_after' => $product->stock_quantity,
                'unit_price' => $product->purchase_price,
                'total_price' => $product->stock_quantity * $product->purchase_price,
                'reason' => 'Boshlang\'ich qoldiq',
                'transaction_date' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => new ProductResource($product->load('category')),
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        // Authorization check
        if ($product->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->load(['category', 'inventoryTransactions' => function($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        // Authorization check
        if ($product->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_level' => 'integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => new ProductResource($product->load('category')),
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        // Authorization check
        if ($product->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Adjust product stock
     */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        // Authorization check
        if ($product->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer',
            'unit_price' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $quantityBefore = $product->stock_quantity;
            $quantityChange = $validated['quantity'];

            // Calculate new stock quantity
            if ($validated['type'] === 'in') {
                $quantityAfter = $quantityBefore + $quantityChange;
            } elseif ($validated['type'] === 'out') {
                $quantityAfter = $quantityBefore - $quantityChange;
                if ($quantityAfter < 0) {
                    return response()->json(['message' => 'Insufficient stock'], 422);
                }
            } else { // adjustment
                $quantityAfter = $quantityChange;
                $quantityChange = $quantityAfter - $quantityBefore;
            }

            // Update product stock
            $product->update(['stock_quantity' => $quantityAfter]);

            // Create inventory transaction
            $unitPrice = $validated['unit_price'] ?? $product->purchase_price;
            InventoryTransaction::create([
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'unit_price' => $unitPrice,
                'total_price' => abs($quantityChange) * $unitPrice,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'transaction_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Stock adjusted successfully',
                'product' => new ProductResource($product->fresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to adjust stock'], 500);
        }
    }
}
