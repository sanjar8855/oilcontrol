<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GlobalProductController extends Controller
{
    /**
     * Kategoriya nomi matn sifatida kiritiladi — mavjud bo'lsa o'shanga
     * bog'lanadi, bo'lmasa avtomatik yaratiladi.
     */
    private function resolveCategoryId(?string $categoryName): ?int
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return null;
        }

        return GlobalCategory::firstOrCreate(['name' => $categoryName])->id;
    }

    public function index(Request $request): Response
    {
        $query = GlobalProduct::with('globalCategory');

        if ($request->filled('category_id')) {
            $query->where('global_category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(30)->withQueryString();
        $categories = GlobalCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('GlobalProducts/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('GlobalProducts/Create', [
            'categoryNames' => GlobalCategory::orderBy('name')->pluck('name'),
        ]);
    }

    private function validationRules(): array
    {
        return [
            'category_name' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['global_category_id'] = $this->resolveCategoryId($validated['category_name'] ?? null);
        unset($validated['category_name']);

        GlobalProduct::create($validated);

        return redirect()->route('global-products.index')
            ->with('success', 'Mahsulot katalogga qo\'shildi!');
    }

    public function edit(GlobalProduct $globalProduct): Response
    {
        return Inertia::render('GlobalProducts/Edit', [
            'product' => $globalProduct->load(['globalCategory', 'carModels']),
            'categoryNames' => GlobalCategory::orderBy('name')->pluck('name'),
            'carMakes' => CarMake::with(['carModels' => fn ($query) => $query->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, GlobalProduct $globalProduct): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['global_category_id'] = $this->resolveCategoryId($validated['category_name'] ?? null);
        unset($validated['category_name']);

        $globalProduct->update($validated);

        return redirect()->route('global-products.index')
            ->with('success', 'Mahsulot ma\'lumotlari yangilandi!');
    }

    public function destroy(GlobalProduct $globalProduct): RedirectResponse
    {
        $globalProduct->delete();

        return redirect()->route('global-products.index')
            ->with('success', 'Mahsulot katalogdan o\'chirildi!');
    }

    public function bulkCreate(): Response
    {
        return Inertia::render('GlobalProducts/BulkCreate', [
            'categoryNames' => GlobalCategory::orderBy('name')->pluck('name'),
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.category_name' => 'nullable|string|max:255',
            'items.*.name' => 'required|string|max:255',
            'items.*.sku' => 'nullable|string|max:255',
            'items.*.unit' => 'required|string|max:50',
            'items.*.barcode' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $created = 0;

            foreach ($validated['items'] as $item) {
                GlobalProduct::create([
                    'global_category_id' => $this->resolveCategoryId($item['category_name'] ?? null),
                    'name' => $item['name'],
                    'sku' => $item['sku'] ?? null,
                    'unit' => $item['unit'],
                    'barcode' => $item['barcode'] ?? null,
                    'is_active' => true,
                ]);
                $created++;
            }

            DB::commit();

            return redirect()->route('global-products.index')
                ->with('success', "{$created} ta mahsulot katalogga qo'shildi!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Ushbu global mahsulotni bir avtomobil turiga (kerakli miqdor bilan)
     * bog'laydi — mavjud bo'lsa miqdorni yangilaydi.
     */
    public function attachCarModel(Request $request, GlobalProduct $globalProduct): RedirectResponse
    {
        $validated = $request->validate([
            'car_model_id' => 'required|exists:car_models,id',
            'quantity' => 'required|numeric|min:0.01|max:9999.99',
        ]);

        $globalProduct->carModels()->syncWithoutDetaching([
            $validated['car_model_id'] => ['quantity' => $validated['quantity']],
        ]);

        return back()->with('success', 'Avtomobil turi bog\'landi!');
    }

    /**
     * Bog'langan avtomobil turi uchun kerakli miqdorni yangilaydi.
     */
    public function updateCarModelQuantity(Request $request, GlobalProduct $globalProduct, CarModel $carModel): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:9999.99',
        ]);

        $globalProduct->carModels()->updateExistingPivot($carModel->id, ['quantity' => $validated['quantity']]);

        return back()->with('success', 'Miqdor yangilandi!');
    }

    /**
     * Avtomobil turi bilan bog'lanishni uzadi.
     */
    public function detachCarModel(GlobalProduct $globalProduct, CarModel $carModel): RedirectResponse
    {
        $globalProduct->carModels()->detach($carModel->id);

        return back()->with('success', 'Bog\'lanish o\'chirildi!');
    }
}
