<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\CarMake;
use App\Models\GlobalCategory;
use App\Models\Product;
use App\Models\InventoryTransaction;
use App\Services\GlobalProductMatcher;
use App\Services\ProductCreationService;
use App\Services\StockMovementService;
use App\Services\SupplierLedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    /**
     * Jadval sarlavhasi orqali saralash mumkin bo'lgan ustunlar (SQL injection'dan himoya).
     */
    private const SORTABLE_COLUMNS = [
        'name', 'stock_quantity', 'purchase_price', 'selling_price', 'created_at',
    ];

    /**
     * Index sahifasidagi filtrlarga mos ravishda mahsulotlar so'rovini quradi.
     * Excel/PDF eksport ham shu bilan bir xil filtr va scoping'dan foydalanadi.
     */
    private function filteredProductsQuery(Request $request): Builder|HasMany
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = $workshop->products()->with(['category', 'supplier']);

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

        return $query;
    }

    public function index(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $perPage = in_array((int) $request->input('per_page'), [10, 25, 30, 50, 100], true)
            ? (int) $request->input('per_page')
            : 30;

        $products = $this->filteredProductsQuery($request)->paginate($perPage)->withQueryString();

        $categories = $workshop->categories()->where('is_active', true)->get();
        $suppliers = $workshop->suppliers()->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'filters' => $request->only(['category_id', 'stock_status', 'search', 'sort_by', 'sort_dir', 'per_page']),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $products = $this->filteredProductsQuery($request)->get();

        return Excel::download(new ProductsExport($products), 'mahsulotlar.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $products = $this->filteredProductsQuery($request)->get();
        $export = new ProductsExport($products);

        $pdf = Pdf::loadView('exports.table', [
            'title' => trans('export.products.title'),
            'headers' => $export->headings(),
            'rows' => $products->map(fn ($product) => $export->map($product))->all(),
            'workshopName' => $request->user()->currentWorkshop()->name,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('mahsulotlar.pdf');
    }

    public function create(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();
        $categories = $workshop->categories()->where('is_active', true)->get();
        $suppliers = $workshop->suppliers()->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Yangi mahsulot yaratish uchun validatsiya qoidalari (store va bulkStore
     * o'rtasida umumiy).
     */
    private function productValidationRules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
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
        ];
    }

    public function store(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate($this->productValidationRules());

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        if (isset($validated['category_id'])) {
            $category = $workshop->categories()->find($validated['category_id']);
            if (!$category) {
                return back()->withErrors(['category_id' => 'Kategoriya topilmadi']);
            }
        }

        if (isset($validated['supplier_id']) && !$workshop->suppliers()->where('id', $validated['supplier_id'])->exists()) {
            return back()->withErrors(['supplier_id' => 'Ta\'minotchi topilmadi']);
        }

        // Set branch_id: Directors can choose, but managers/employees use their own branch
        $validated['branch_id'] = $user->canAccessAllBranches()
            ? ($request->input('branch_id') ?? $user->branch_id)
            : $user->branch_id;

        DB::beginTransaction();
        try {
            $productCreationService->createWithInitialStock($workshop, $user, $validated);

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', 'Mahsulot muvaffaqiyatli qo\'shildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }

    public function bulkCreate(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();
        $categories = $workshop->categories()->where('is_active', true)->get();
        $suppliers = $workshop->suppliers()->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Products/BulkCreate', [
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    public function bulkStore(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.supplier_id' => 'nullable|exists:suppliers,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.sku' => 'nullable|string|max:255',
            'items.*.unit' => 'required|string|max:50',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.stock_quantity' => 'nullable|integer|min:0',
            'items.*.min_stock_level' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();
        $branchId = $user->canAccessAllBranches() ? ($request->input('branch_id') ?? $user->branch_id) : $user->branch_id;

        DB::beginTransaction();
        try {
            $created = 0;

            foreach ($validated['items'] as $item) {
                if (isset($item['category_id'])) {
                    $category = $workshop->categories()->find($item['category_id']);
                    if (!$category) {
                        throw new \Exception("Kategoriya topilmadi: {$item['name']}");
                    }
                }

                if (isset($item['supplier_id']) && !$workshop->suppliers()->where('id', $item['supplier_id'])->exists()) {
                    throw new \Exception("Ta'minotchi topilmadi: {$item['name']}");
                }

                $productData = [
                    'category_id' => $item['category_id'] ?? null,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'name' => $item['name'],
                    'sku' => $item['sku'] ?? null,
                    'unit' => $item['unit'],
                    'currency' => 'UZS',
                    'purchase_price_uzs' => $item['purchase_price'],
                    'selling_price_uzs' => $item['selling_price'],
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['selling_price'],
                    'stock_quantity' => $item['stock_quantity'] ?? 0,
                    'min_stock_level' => $item['min_stock_level'] ?? 0,
                    'is_active' => true,
                    'track_inventory' => true,
                    'branch_id' => $branchId,
                ];

                $productCreationService->createWithInitialStock($workshop, $user, $productData);
                $created++;
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', "{$created} ta mahsulot muvaffaqiyatli qo'shildi!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Umumiy katalogdan tadbirkor o'ziga mahsulot tanlab olishi uchun
     * ro'yxat: qaysi katalog mahsulotlari allaqachon nusxa olinganini ham
     * belgilab beradi.
     */
    public function catalog(Request $request, GlobalProductMatcher $matcher): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $products = $matcher
            ->catalogQuery(
                $request->filled('search') ? $request->string('search')->toString() : null,
                $request->filled('category_id') ? $request->integer('category_id') : null
            )
            ->paginate(30)
            ->withQueryString();

        $copiedGlobalProductIds = $workshop->products()
            ->whereNotNull('global_product_id')
            ->pluck('global_product_id');

        return Inertia::render('Products/Catalog', [
            'products' => $products,
            'categories' => GlobalCategory::orderBy('name')->get(['id', 'name']),
            'copiedGlobalProductIds' => $copiedGlobalProductIds,
            'filters' => $request->only(['category_id', 'search']),
        ]);
    }

    /**
     * Katalogdan tanlangan mahsulotlarni tadbirkorning o'z workshop'iga
     * nusxa ko'chiradi — narx va boshlang'ich qoldiqni tadbirkor o'zi
     * kiritadi, kategoriya esa katalogdagi kategoriya nomi bo'yicha
     * avtomatik topiladi yoki yaratiladi.
     */
    public function copyFromCatalog(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.global_product_id' => 'required|integer|exists:global_products,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.stock_quantity' => 'nullable|integer|min:0',
            'items.*.min_stock_level' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();
        $branchId = $user->canAccessAllBranches() ? ($request->input('branch_id') ?? $user->branch_id) : $user->branch_id;

        DB::beginTransaction();
        try {
            $result = $productCreationService->copySelectionToWorkshop($workshop, $user, $validated['items'], $branchId);

            DB::commit();

            $message = "{$result['created']} ta mahsulot qo'shildi!";
            if ($result['skipped'] > 0) {
                $message .= " ({$result['skipped']} tasi allaqachon qo'shilgan edi)";
            }

            return redirect()->route('products.index')->with('success', $message);
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
            'supplier',
            'localCarModels',
            'globalProduct.carModels',
            'inventoryTransactions' => function($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Products/Show', [
            'product' => array_merge($product->toArray(), [
                'car_models' => $product->effectiveCarModels(),
            ]),
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
        $suppliers = $workshop->suppliers()->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
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
            'supplier_id' => 'nullable|exists:suppliers,id',
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
        ]);

        if (isset($validated['supplier_id']) && !$product->workshop->suppliers()->where('id', $validated['supplier_id'])->exists()) {
            return back()->withErrors(['supplier_id' => 'Ta\'minotchi topilmadi']);
        }

        $product->update($validated);

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
            'product' => $product->load(['category', 'supplier']),
            'suppliers' => $user->currentWorkshop()->suppliers()->where('is_active', true)->get(['id', 'name']),
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
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['supplier_id']) && !$user->currentWorkshop()->suppliers()->where('id', $validated['supplier_id'])->exists()) {
            return back()->withErrors(['supplier_id' => 'Ta\'minotchi topilmadi']);
        }

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
                $supplierId = $validated['supplier_id'] ?? $product->supplier_id;

                if ($supplierId) {
                    // Ta'minotchidan qarzga olingan — Xarajat emas, qarz sifatida yoziladi
                    (new SupplierLedgerService())->recordPurchase(
                        workshopId: $user->currentWorkshop()->id,
                        supplierId: $supplierId,
                        amount: $validated['quantity'] * $unitPrice,
                        currency: $currency,
                        description: $validated['notes'] ?? "Ombor kirim: {$product->name} ({$validated['quantity']} {$product->unit})",
                        referenceType: 'Product',
                        referenceId: $product->id,
                        userId: $user->id,
                    );
                } else {
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
                }
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
