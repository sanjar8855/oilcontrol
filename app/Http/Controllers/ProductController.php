<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $workshop = $request->user()->workshop;

        $query = $workshop->products()->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(10);

        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'low_stock', 'search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $workshop = $request->user()->workshop;
        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        $workshop = $request->user()->workshop;

        if (isset($validated['category_id'])) {
            $category = $workshop->categories()->find($validated['category_id']);
            if (!$category) {
                return back()->withErrors(['category_id' => 'Kategoriya topilmadi']);
            }
        }

        DB::beginTransaction();
        try {
            $product = $workshop->products()->create($validated);

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

                $workshop->expenses()->create([
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => "Boshlang'ich qoldiq: {$product->stock_quantity} {$product->unit}",
                    'amount' => $product->stock_quantity * $product->purchase_price,
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', 'Mahsulot muvaffaqiyatli qo\'shildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi'])->withInput();
        }
    }

    public function show(Request $request, Product $product): Response
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $product->load([
            'category',
            'inventoryTransactions' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $workshop = $request->user()->workshop;
        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
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
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Mahsulot ma\'lumotlari yangilandi!');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Mahsulot o\'chirildi!');
    }

    public function adjustStock(Request $request, Product $product): Response
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        return Inertia::render('Products/AdjustStock', [
            'product' => $product->load('category'),
        ]);
    }

    public function processStockAdjustment(Request $request, Product $product): RedirectResponse
    {
        if ($product->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $quantityBefore = $product->stock_quantity;
            $quantityChange = $validated['quantity'];

            if ($validated['type'] === 'in') {
                $quantityAfter = $quantityBefore + $quantityChange;
            } elseif ($validated['type'] === 'out') {
                $quantityAfter = $quantityBefore - $quantityChange;
                if ($quantityAfter < 0) {
                    return back()->withErrors(['quantity' => 'Omborda yetarli mahsulot yo\'q']);
                }
            } else {
                $quantityAfter = $quantityChange;
                $quantityChange = $quantityAfter - $quantityBefore;
            }

            $product->update(['stock_quantity' => $quantityAfter]);

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

            if ($validated['type'] === 'in') {
                $request->user()->workshop->expenses()->create([
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => $validated['notes'] ?? "Ombor kirim: {$quantityChange} {$product->unit}",
                    'amount' => abs($quantityChange) * $unitPrice,
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            }

            DB::commit();

            return redirect()->route('products.show', $product)
                ->with('success', 'Qoldiq muvaffaqiyatli o\'zgartirildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi'])->withInput();
        }
    }
}
