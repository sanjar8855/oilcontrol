<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $workshop = $request->user()->workshop;

        $query = $workshop->expenses();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->latest('expense_date')->paginate(10);

        // Calculate statistics
        $totalExpenses = $workshop->expenses()
            ->when($request->has('from_date'), fn($q) => $q->where('expense_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('expense_date', '<=', $request->to_date))
            ->sum('amount');

        $expensesByCategory = $workshop->expenses()
            ->when($request->has('from_date'), fn($q) => $q->where('expense_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('expense_date', '<=', $request->to_date))
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['category', 'from_date', 'to_date']),
            'statistics' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Expenses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:Elektr,Ish haqi,Ijara,Transport,Boshqa',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|in:Naqd,Bank,Click,Payme',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        $workshop = $request->user()->workshop;

        $workshop->expenses()->create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Xarajat muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Expense $expense): Response
    {
        // Faqat o'z ustaxonasining xarajatlarini tahrirlash mumkin
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        // Faqat o'z ustaxonasining xarajatlarini yangilash mumkin
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'category' => 'required|string|in:Elektr,Ish haqi,Ijara,Transport,Boshqa',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|in:Naqd,Bank,Click,Payme',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Xarajat ma\'lumotlari yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        // Faqat o'z ustaxonasining xarajatlarini o'chirish mumkin
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Xarajat o\'chirildi!');
    }
}
