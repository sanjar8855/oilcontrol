<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Product;
use App\Models\InventoryTransaction;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Jadval sarlavhasi orqali saralash mumkin bo'lgan ustunlar (SQL injection'dan himoya).
     */
    private const SORTABLE_COLUMNS = [
        'name', 'stock_quantity', 'purchase_price', 'selling_price', 'created_at',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = $workshop->products()->with('category');

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            // Manager/Employee can only see their branch's products
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            match ($request->string('stock_status')->toString()) {
                'out' => $query->where('stock_quantity', '<=', 0),
                'low' => $query->whereColumn('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level'),
                'in_stock' => $query->whereColumn('stock_quantity', '>', 'min_stock_level'),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($request->string('sort_by')->toString(), self::SORTABLE_COLUMNS, true)
            ? $request->string('sort_by')->toString()
            : 'created_at';
        $sortDir = $request->string('sort_dir')->toString() === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = in_array((int) $request->input('per_page'), [10, 25, 30, 50, 100], true)
            ? (int) $request->input('per_page')
            : 30;

        $products = $query->paginate($perPage)->withQueryString();

        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'stock_status', 'search', 'sort_by', 'sort_dir', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();
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
            'currency' => 'required|in:USD,UZS',
            'purchase_price_usd' => 'nullable|numeric|min:0',
            'purchase_price_uzs' => 'nullable|numeric|min:0',
            'selling_price_usd' => 'nullable|numeric|min:0',
            'selling_price_uzs' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_consignment' => 'boolean',
            'consignment_percentage' => 'nullable|numeric|min:0|max:100',
            'supplier' => 'nullable|string|max:255',
            'stock_quantity' => 'integer|min:0',
            'min_stock_level' => 'integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            // Eski maydonlar (backward compatibility)
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            // Bog'langan avtomobil turlari
            'car_models' => 'nullable|array',
            'car_models.*' => 'integer|exists:car_models,id',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        if (isset($validated['category_id'])) {
            $category = $workshop->categories()->find($validated['category_id']);
            if (!$category) {
                return back()->withErrors(['category_id' => 'Kategoriya topilmadi']);
            }
        }

        // Set branch_id: Directors can choose, but managers/employees use their own branch
        $validated['branch_id'] = $user->canAccessAllBranches()
            ? ($request->input('branch_id') ?? $user->branch_id)
            : $user->branch_id;

        $carModelIds = $validated['car_models'] ?? [];
        unset($validated['car_models']);

        DB::beginTransaction();
        try {
            $product = $workshop->products()->create($validated);

            if (!empty($carModelIds)) {
                // Miqdor keyinchalik "Avto markalari" sahifasida aniqlashtiriladi, hozircha 1
                $product->carModels()->sync(collect($carModelIds)->mapWithKeys(
                    fn ($carModelId) => [$carModelId => ['quantity' => 1]]
                ));
            }

            // StockMovementService orqali boshlang'ich qoldiqni qo'shish
            if ($product->stock_quantity > 0 && $product->track_inventory) {
                $stockService = new StockMovementService();

                $stockService->recordIncoming(
                    productId: $product->id,
                    quantity: $product->stock_quantity,
                    unitCostUsd: $product->purchase_price_usd,
                    unitCostUzs: $product->purchase_price_uzs,
                    currency: $product->currency,
                    referenceType: 'InitialStock',
                    referenceId: null,
                    notes: "Boshlang'ich qoldiq"
                );

                // Eski InventoryTransaction (backward compatibility)
                InventoryTransaction::create([
                    'workshop_id' => $workshop->id,
                    'branch_id' => $product->branch_id,
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $product->stock_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $product->stock_quantity,
                    'unit_price' => $product->getPurchasePrice(),
                    'total_price' => $product->stock_quantity * $product->getPurchasePrice(),
                    'reason' => 'Boshlang\'ich qoldiq',
                    'transaction_date' => now(),
                ]);

                // Xarajat yaratish
                $workshop->expenses()->create([
                    'branch_id' => $product->branch_id,
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => "Boshlang'ich qoldiq: {$product->stock_quantity} {$product->unit}",
                    'amount' => $product->stock_quantity * $product->getPurchasePrice(),
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', 'Mahsulot muvaffaqiyatli qo\'shildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Request $request, Product $product): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        $product->load([
            'category',
            'carModels',
            'inventoryTransactions' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Products/Show', [
            'product' => $product,
            'carMakes' => CarMake::with(['carModels' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'canManageCarMakes' => $user->can('car-makes.manage'),
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->currentWorkshop();
        $categories = $workshop->categories()->where('is_active', true)->get();

        $product->load('carModels');

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'carModelGroups' => CarModel::optionGroupsWithId(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'currency' => 'required|in:USD,UZS',
            'purchase_price_usd' => 'nullable|numeric|min:0',
            'purchase_price_uzs' => 'nullable|numeric|min:0',
            'selling_price_usd' => 'nullable|numeric|min:0',
            'selling_price_uzs' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_consignment' => 'boolean',
            'consignment_percentage' => 'nullable|numeric|min:0|max:100',
            'supplier' => 'nullable|string|max:255',
            'min_stock_level' => 'integer|min:0',
            'barcode' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            // Eski maydonlar
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            // Bog'langan avtomobil turlari
            'car_models' => 'nullable|array',
            'car_models.*' => 'integer|exists:car_models,id',
        ]);

        $carModelIds = $validated['car_models'] ?? [];
        unset($validated['car_models']);

        $product->update($validated);

        // Avvaldan bog'langan turlar uchun miqdorni saqlab qolamiz (masalan, "Avto
        // markalari" sahifasida moy hajmiga moslab qo'yilgan bo'lishi mumkin),
        // yangi bog'langan turlarga esa boshlang'ich sifatida 1 qo'yiladi.
        $existingQuantities = $product->carModels()->pluck('car_model_products.quantity', 'car_models.id');

        $product->carModels()->sync(collect($carModelIds)->mapWithKeys(
            fn ($carModelId) => [$carModelId => ['quantity' => $existingQuantities[$carModelId] ?? 1]]
        ));

        return redirect()->route('products.show', $product)
            ->with('success', 'Mahsulot ma\'lumotlari yangilandi!');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Mahsulot o\'chirildi!');
    }

    public function adjustStock(Request $request, Product $product): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        return Inertia::render('Products/AdjustStock', [
            'product' => $product->load('category'),
        ]);
    }

    public function processStockAdjustment(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($product->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $product->branch_id !== $user->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'unit_price_usd' => 'nullable|numeric|min:0',
            'unit_price_uzs' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:USD,UZS',
            'unit_price' => 'nullable|numeric|min:0', // Backward compatibility
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stockService = new StockMovementService();
            $quantityBefore = $product->stock_quantity;

            $currency = $validated['currency'] ?? $product->currency;
            $unitPriceUsd = $validated['unit_price_usd'] ?? $product->purchase_price_usd;
            $unitPriceUzs = $validated['unit_price_uzs'] ?? $product->purchase_price_uzs;

            // Backward compatibility
            if (isset($validated['unit_price'])) {
                if ($currency === 'USD') {
                    $unitPriceUsd = $validated['unit_price'];
                } else {
                    $unitPriceUzs = $validated['unit_price'];
                }
            }

            if ($validated['type'] === 'in') {
                // Kirim
                $stockService->recordIncoming(
                    productId: $product->id,
                    quantity: $validated['quantity'],
                    unitCostUsd: $unitPriceUsd,
                    unitCostUzs: $unitPriceUzs,
                    currency: $currency,
                    referenceType: 'StockAdjustment',
                    referenceId: null,
                    notes: $validated['notes'] ?? $validated['reason']
                );

                $unitPrice = $currency === 'USD' ? $unitPriceUsd : $unitPriceUzs;

                // Xarajat yaratish
                $user->currentWorkshop()->expenses()->create([
                    'branch_id' => $product->branch_id,
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => $validated['notes'] ?? "Ombor kirim: {$validated['quantity']} {$product->unit}",
                    'amount' => $validated['quantity'] * $unitPrice,
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            } elseif ($validated['type'] === 'out') {
                // Chiqim
                $stockService->recordOutgoing(
                    productId: $product->id,
                    quantity: $validated['quantity'],
                    referenceType: 'StockAdjustment',
                    referenceId: null,
                    notes: $validated['notes'] ?? $validated['reason']
                );
            } else {
                // Adjustment
                $stockService->recordAdjustment(
                    productId: $product->id,
                    newQuantity: $validated['quantity'],
                    notes: $validated['notes'] ?? $validated['reason']
                );
            }

            // Eski InventoryTransaction (backward compatibility)
            $quantityAfter = $product->fresh()->stock_quantity;
            $unitPrice = $currency === 'USD' ? ($unitPriceUsd ?? 0) : ($unitPriceUzs ?? 0);

            InventoryTransaction::create([
                'workshop_id' => $user->currentWorkshop()->id,
                'branch_id' => $product->branch_id,
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $quantityAfter - $quantityBefore,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'unit_price' => $unitPrice,
                'total_price' => abs($quantityAfter - $quantityBefore) * $unitPrice,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'transaction_date' => now(),
            ]);

            DB::commit();

            return redirect()->route('products.show', $product)
                ->with('success', 'Qoldiq muvaffaqiyatli o\'zgartirildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }
}
