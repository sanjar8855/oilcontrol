<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = $workshop->expenses();

        // Branch filtering based on user role
        if (!$user->canAccessAllBranches()) {
            // Manager/Employee can only see their branch's expenses
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->latest('expense_date')->paginate(10);

        $totalExpensesQuery = $workshop->expenses()
            ->when($request->has('from_date'), fn($q) => $q->where('expense_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('expense_date', '<=', $request->to_date));

        if (!$user->canAccessAllBranches()) {
            $totalExpensesQuery->where('branch_id', $user->branch_id);
        }

        $totalExpenses = $totalExpensesQuery->sum('amount');

        $expensesByCategoryQuery = $workshop->expenses()
            ->when($request->has('from_date'), fn($q) => $q->where('expense_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('expense_date', '<=', $request->to_date));

        if (!$user->canAccessAllBranches()) {
            $expensesByCategoryQuery->where('branch_id', $user->branch_id);
        }

        $expensesByCategory = $expensesByCategoryQuery
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        // Get active categories for the workshop
        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['category', 'from_date', 'to_date']),
            'statistics' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();
        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Expenses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        // Get active category names for validation
        $categoryNames = $workshop->categories()
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', $categoryNames),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|in:Naqd,Bank,Click,Payme',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        // Set branch_id: Directors can choose, but managers/employees use their own branch
        $validated['branch_id'] = $user->canAccessAllBranches()
            ? ($request->input('branch_id') ?? $user->branch_id)
            : $user->branch_id;

        $workshop->expenses()->create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Xarajat muvaffaqiyatli qo\'shildi!');
    }

    public function edit(Request $request, Expense $expense): Response
    {
        $user = $request->user();

        // Check workshop access
        if ($expense->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $expense->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->currentWorkshop();
        $categories = $workshop->categories()->where('is_active', true)->get();

        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($expense->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $expense->branch_id !== $user->branch_id) {
            abort(403);
        }

        $workshop = $user->currentWorkshop();

        // Get active category names for validation
        $categoryNames = $workshop->categories()
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', $categoryNames),
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

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $user = $request->user();

        // Check workshop access
        if ($expense->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access for managers/employees
        if (!$user->canAccessAllBranches() && $expense->branch_id !== $user->branch_id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Xarajat o\'chirildi!');
    }
}
