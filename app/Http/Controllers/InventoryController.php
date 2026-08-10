<?php

namespace App\Http\Controllers;

use App\Exports\InventoriesExport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Services\StockMovementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryController extends Controller
{
    private function scopedInventoriesQuery(Request $request): Builder
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = $workshop->inventories()
            ->with(['branch', 'user'])
            ->withCount(['items', 'items as counted_items_count' => function ($q) {
                $q->whereNotNull('counted_quantity');
            }]);

        if (!$user->canAccessAllBranches()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->latest('started_at');
    }

    public function index(Request $request): Response
    {
        $inventories = $this->scopedInventoriesQuery($request)->paginate(15);

        return Inertia::render('Inventories/Index', [
            'inventories' => $inventories,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $inventories = $this->scopedInventoriesQuery($request)->get();

        return Excel::download(new InventoriesExport($inventories), 'inventarizatsiya.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $inventories = $this->scopedInventoriesQuery($request)->get();
        $export = new InventoriesExport($inventories);

        $pdf = Pdf::loadView('exports.table', [
            'title' => 'Inventarizatsiya',
            'headers' => $export->headings(),
            'rows' => $inventories->map(fn ($inventory) => $export->map($inventory))->all(),
            'workshopName' => $request->user()->currentWorkshop()->name,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inventarizatsiya.pdf');
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $branches = $user->canAccessAllBranches()
            ? $workshop->branches()->where('is_active', true)->get(['id', 'name', 'code'])
            : [];

        return Inertia::render('Inventories/Create', [
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $branchId = $user->canAccessAllBranches()
            ? ($validated['branch_id'] ?? null)
            : $user->branch_id;

        if ($branchId) {
            $branch = $workshop->branches()->find($branchId);
            if (!$branch) {
                abort(403, 'Bu filial sizga tegishli emas');
            }
        }

        $inventory = DB::transaction(function () use ($workshop, $user, $branchId, $validated) {
            $inventory = Inventory::create([
                'workshop_id' => $workshop->id,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'status' => 'draft',
                'started_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $products = $workshop->products()
                ->where('track_inventory', true)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->get(['id', 'stock_quantity']);

            foreach ($products as $product) {
                InventoryItem::create([
                    'inventory_id' => $inventory->id,
                    'product_id' => $product->id,
                    'system_quantity' => $product->stock_quantity,
                ]);
            }

            return $inventory;
        });

        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Inventarizatsiya boshlandi! Mahsulotlar sanab, miqdorini kiriting.');
    }

    public function show(Request $request, Inventory $inventory): Response
    {
        $this->authorizeAccess($request, $inventory);

        $inventory->load([
            'branch',
            'user',
            'items.product' => function ($q) {
                $q->select('id', 'name', 'unit', 'category_id')->with('category:id,name');
            },
        ]);

        return Inertia::render('Inventories/Show', [
            'inventory' => $inventory,
        ]);
    }

    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        $this->authorizeAccess($request, $inventory);

        if (!$inventory->isDraft()) {
            abort(403, 'Yakunlangan inventarizatsiyani tahrirlab bo\'lmaydi');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:inventory_items,id',
            'items.*.counted_quantity' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $inventory) {
            foreach ($validated['items'] as $item) {
                $inventory->items()->where('id', $item['id'])->update([
                    'counted_quantity' => $item['counted_quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Sanoq natijalari saqlandi!');
    }

    public function complete(Request $request, Inventory $inventory): RedirectResponse
    {
        $this->authorizeAccess($request, $inventory);

        if (!$inventory->isDraft()) {
            abort(403, 'Bu inventarizatsiya allaqachon yakunlangan');
        }

        $stockService = new StockMovementService();
        $adjustedCount = 0;

        DB::transaction(function () use ($inventory, $stockService, &$adjustedCount) {
            $items = $inventory->items()->whereNotNull('counted_quantity')->get();

            foreach ($items as $item) {
                $countedQuantity = (float) $item->counted_quantity;

                if ($countedQuantity !== (float) $item->system_quantity) {
                    $stockService->recordAdjustment(
                        productId: $item->product_id,
                        newQuantity: $countedQuantity,
                        notes: "Inventarizatsiya #{$inventory->id} natijasida tuzatildi"
                    );
                    $adjustedCount++;
                }
            }

            $inventory->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('inventories.show', $inventory)
            ->with('success', "Inventarizatsiya yakunlandi! {$adjustedCount} ta mahsulot qoldig'i tuzatildi.");
    }

    public function destroy(Request $request, Inventory $inventory): RedirectResponse
    {
        $this->authorizeAccess($request, $inventory);

        if (!$inventory->isDraft()) {
            abort(403, 'Yakunlangan inventarizatsiyani o\'chirib bo\'lmaydi');
        }

        $inventory->delete();

        return redirect()->route('inventories.index')
            ->with('success', 'Inventarizatsiya o\'chirildi!');
    }

    private function authorizeAccess(Request $request, Inventory $inventory): void
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        if (!$workshop || $inventory->workshop_id !== $workshop->id) {
            abort(403);
        }

        if (!$user->canAccessAllBranches() && $inventory->branch_id !== $user->branch_id) {
            abort(403);
        }
    }
}
