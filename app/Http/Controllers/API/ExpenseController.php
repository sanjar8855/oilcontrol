<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(Request $request): AnonymousResourceCollection
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

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $expenses = $query->latest('expense_date')->paginate(20);

        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request): JsonResponse
    {
        $workshop = $request->user()->workshop;

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
            'attachment' => 'nullable|string',
        ]);

        $expense = $workshop->expenses()->create($validated);

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => new ExpenseResource($expense),
        ], 201);
    }

    /**
     * Display the specified expense
     */
    public function show(Request $request, Expense $expense): JsonResponse
    {
        // Authorization check
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'expense' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        // Authorization check
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workshop = $request->user()->workshop;

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
            'attachment' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        // Authorization check
        if ($expense->workshop_id !== $request->user()->workshop->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    /**
     * Get expense statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $workshop = $request->user()->workshop;

        // Get date range (default to current month)
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->endOfMonth()->toDateString());

        // Total expenses
        $totalExpenses = $workshop->expenses()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->sum('amount');

        // Expenses by category
        $expensesByCategory = $workshop->expenses()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        // Expenses by payment method
        $expensesByPayment = $workshop->expenses()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'total_expenses' => (float) $totalExpenses,
            'by_category' => $expensesByCategory,
            'by_payment_method' => $expensesByPayment,
        ]);
    }
}
