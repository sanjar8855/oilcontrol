<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        $query = $workshop->suppliers()->withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        $suppliers->getCollection()->transform(function (Supplier $supplier) {
            $supplier->balances = $supplier->getBalances();
            return $supplier;
        });

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $workshop = $request->user()->currentWorkshop();
        $workshop->suppliers()->create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Ta\'minotchi muvaffaqiyatli qo\'shildi!');
    }

    public function show(Request $request, Supplier $supplier): Response
    {
        $this->authorizeAccess($request, $supplier);

        $transactions = $supplier->transactions()->latest('transaction_date')->latest('id')->paginate(20);
        $products = $supplier->products()->with('category')->get(['id', 'name', 'sku', 'stock_quantity', 'unit', 'category_id']);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
            'balances' => $supplier->getBalances(),
            'transactions' => $transactions,
            'products' => $products,
        ]);
    }

    public function edit(Request $request, Supplier $supplier): Response
    {
        $this->authorizeAccess($request, $supplier);

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeAccess($request, $supplier);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Ta\'minotchi ma\'lumotlari yangilandi!');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeAccess($request, $supplier);

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Ta\'minotchi o\'chirildi!');
    }

    public function storePayment(Request $request, Supplier $supplier, SupplierLedgerService $ledger): RedirectResponse
    {
        $this->authorizeAccess($request, $supplier);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,UZS',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $ledger->recordPayment(
            workshopId: $supplier->workshop_id,
            supplierId: $supplier->id,
            amount: (float) $validated['amount'],
            currency: $validated['currency'],
            paymentMethod: $validated['payment_method'] ?? null,
            notes: $validated['notes'] ?? null,
            userId: $request->user()->id,
        );

        return back()->with('success', 'To\'lov muvaffaqiyatli qayd qilindi!');
    }

    private function authorizeAccess(Request $request, Supplier $supplier): void
    {
        if ($supplier->workshop_id !== $request->user()->currentWorkshop()->id) {
            abort(403);
        }
    }
}
