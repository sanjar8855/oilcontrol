<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Salary::with(['user', 'workshop', 'branch', 'paidBy'])
            ->orderBy('payment_date', 'desc');

        // Filter by workshop if user is not superadmin/director
        if (!Auth::user()->canAccessAllBranches()) {
            $query->where('workshop_id', Auth::user()->workshop_id)
                  ->orWhere('branch_id', Auth::user()->branch_id);
        }

        // Filter by month if provided
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $salaries = $query->paginate(20);

        return Inertia::render('Salaries/Index', [
            'salaries' => $salaries,
            'filters' => $request->only(['month', 'user_id']),
        ]);
    }

    public function create()
    {
        $employees = User::whereIn('employment_status', ['active', 'on_leave'])
            ->whereNotNull('salary')
            ->select('id', 'name', 'position', 'salary')
            ->get();

        $workshops = Workshop::select('id', 'name')->get();

        return Inertia::render('Salaries/Create', [
            'employees' => $employees,
            'workshops' => $workshops,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'workshop_id' => 'required|exists:workshops,id',
            'branch_id' => 'nullable|exists:branches,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|date_format:Y-m',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,transfer,other',
            'bonus' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check for duplicate month payment
        $existingSalary = Salary::where('user_id', $validated['user_id'])
            ->where('month', $validated['month'])
            ->exists();

        if ($existingSalary) {
            return back()->withErrors([
                'month' => 'Bu xodimga ushbu oy uchun maosh allaqachon to\'langan.'
            ]);
        }

        $validated['paid_by'] = Auth::id();
        $validated['bonus'] = $validated['bonus'] ?? 0;
        $validated['deduction'] = $validated['deduction'] ?? 0;

        Salary::create($validated);

        return redirect()->route('salaries.index')
            ->with('success', 'Oylik maosh muvaffaqiyatli to\'landi.');
    }

    public function show(Salary $salary)
    {
        $salary->load(['user', 'workshop', 'branch', 'paidBy']);

        return Inertia::render('Salaries/Show', [
            'salary' => $salary,
        ]);
    }

    public function destroy(Salary $salary)
    {
        // Only director and superadmin can delete salary records
        if (!Auth::user()->canAccessAllBranches()) {
            return back()->withErrors([
                'error' => 'Sizda maosh yozuvini o\'chirish huquqi yo\'q.'
            ]);
        }

        $salary->delete();

        return redirect()->route('salaries.index')
            ->with('success', 'Maosh yozuvi muvaffaqiyatli o\'chirildi.');
    }

    // Get salary report by employee
    public function report(Request $request)
    {
        $query = User::with(['salaries' => function ($query) use ($request) {
            $query->orderBy('payment_date', 'desc');

            if ($request->has('start_date')) {
                $query->where('payment_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('payment_date', '<=', $request->end_date);
            }
        }])
        ->whereNotNull('salary')
        ->whereIn('employment_status', ['active', 'on_leave']);

        $employees = $query->get();

        return Inertia::render('Salaries/Report', [
            'employees' => $employees,
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }
}
