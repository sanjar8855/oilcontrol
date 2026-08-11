<?php

namespace App\Http\Controllers;

use App\Exports\CategoriesExport;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CategoryController extends Controller
{
    /**
     * Jadval sarlavhasi orqali saralash mumkin bo'lgan ustunlar (SQL injection'dan himoya).
     */
    private const SORTABLE_COLUMNS = [
        'name', 'stock_quantity', 'purchase_price', 'selling_price', 'created_at',
    ];

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

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $categories = $request->user()->currentWorkshop()
            ->categories()->withCount('products')->latest()->get();

        return Excel::download(new CategoriesExport($categories), 'kategoriyalar.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $categories = $request->user()->currentWorkshop()
            ->categories()->withCount('products')->latest()->get();
        $export = new CategoriesExport($categories);

        $pdf = Pdf::loadView('exports.table', [
            'title' => trans('export.categories.title'),
            'headers' => $export->headings(),
            'rows' => $categories->map(fn ($category) => $export->map($category))->all(),
            'workshopName' => $request->user()->currentWorkshop()->name,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('kategoriyalar.pdf');
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

    /**
     * Kategoriyaning batafsil sahifasi — shu kategoriyadagi mahsulotlar
     * ro'yxati (filtr, saralash va sahifalash imkoniyatlari bilan).
     */
    public function show(Request $request, Category $category): Response
    {
        $user = $request->user();

        if ($category->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        $query = $category->products();

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            $query->where('branch_id', $user->branch_id);
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
            $query->where(function ($q) use ($search) {
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

        return Inertia::render('Categories/Show', [
            'category' => $category,
            'products' => $products,
            'filters' => $request->only(['stock_status', 'search', 'sort_by', 'sort_dir', 'per_page']),
        ]);
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
