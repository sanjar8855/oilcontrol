<?php

namespace App\Http\Controllers;

use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date('date_from'))->startOfDay()
            : now()->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        // Filial bo'yicha cheklov faqat filialga biriktirilgan xodim/menejer uchun;
        // direktor/superadmin uchun butun workshop (filiallari bo'lmasa ham) hisobga olinadi.
        $restrictToBranch = !$user->canAccessAllBranches() ? $user->branch_id : null;

        // ============================================
        // Savdolar (service_logs) — workshop orqali (vehicle -> client) bog'lanadi
        // ============================================
        $serviceLogsQuery = fn () => DB::table('service_logs')
            ->join('vehicles', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->where('clients.workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('service_logs.branch_id', $restrictToBranch))
            ->whereBetween('service_logs.service_date', [$dateFrom, $dateTo]);

        $salesCount = $serviceLogsQuery()->count();
        $revenue = (float) $serviceLogsQuery()->sum('service_logs.total_amount');
        $laborRevenue = (float) $serviceLogsQuery()->sum('service_logs.labor_cost');
        $creditExtended = (float) $serviceLogsQuery()->sum('service_logs.remaining_amount');

        // Sotilgan mahsulotlarning tan narxi (COGS)
        $cogs = (float) DB::table('service_log_product')
            ->join('service_logs', 'service_logs.id', '=', 'service_log_product.service_log_id')
            ->join('vehicles', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->join('products', 'products.id', '=', 'service_log_product.product_id')
            ->where('clients.workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('service_logs.branch_id', $restrictToBranch))
            ->whereBetween('service_logs.service_date', [$dateFrom, $dateTo])
            ->sum(DB::raw('service_log_product.quantity * products.purchase_price'));

        // ============================================
        // Xarajatlar
        // ============================================
        $expenses = (float) DB::table('expenses')
            ->where('workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('branch_id', $restrictToBranch))
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $netProfit = $revenue - $cogs - $expenses;

        // ============================================
        // To'lov turlari bo'yicha (naqd / click / nasiya)
        // ============================================
        $paymentsByMethod = DB::table('payments')
            ->where('workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('branch_id', $restrictToBranch))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->select('payment_method')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        // ============================================
        // Eng ko'p sotilgan mahsulotlar
        // ============================================
        $topProducts = DB::table('service_log_product')
            ->join('service_logs', 'service_logs.id', '=', 'service_log_product.service_log_id')
            ->join('vehicles', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->join('products', 'products.id', '=', 'service_log_product.product_id')
            ->where('clients.workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('service_logs.branch_id', $restrictToBranch))
            ->whereBetween('service_logs.service_date', [$dateFrom, $dateTo])
            ->select('products.id', 'products.name', 'products.unit')
            ->selectRaw('SUM(service_log_product.quantity) as total_quantity')
            ->selectRaw('SUM(service_log_product.total_price) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ============================================
        // Avtomobil markalari bo'yicha savdo
        // ============================================
        $salesByMake = DB::table('service_logs')
            ->join('vehicles', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->where('clients.workshop_id', $workshop->id)
            ->when($restrictToBranch, fn ($q) => $q->where('service_logs.branch_id', $restrictToBranch))
            ->whereBetween('service_logs.service_date', [$dateFrom, $dateTo])
            ->select('vehicles.make')
            ->selectRaw('COUNT(*) as services_count')
            ->selectRaw('SUM(service_logs.total_amount) as total_revenue')
            ->groupBy('vehicles.make')
            ->orderByDesc('total_revenue')
            ->get();

        // ============================================
        // Ombor holati (joriy holat, sana oralig'iga bog'liq emas)
        // ============================================
        $productsQuery = fn () => $workshop->products()
            ->when(!$user->canAccessAllBranches(), fn ($q) => $q->where('branch_id', $user->branch_id));

        $inventory = [
            'total_products' => $productsQuery()->count(),
            'total_value' => (float) ($productsQuery()->selectRaw('SUM(stock_quantity * purchase_price) as total')->value('total') ?? 0),
            'low_stock_count' => $productsQuery()->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
            'out_of_stock_count' => $productsQuery()->where('stock_quantity', '<=', 0)->count(),
        ];

        return Inertia::render('Reports/Index', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'summary' => [
                'sales_count' => $salesCount,
                'revenue' => $revenue,
                'labor_revenue' => $laborRevenue,
                'cogs' => $cogs,
                'expenses' => $expenses,
                'net_profit' => $netProfit,
                'credit_extended' => $creditExtended,
            ],
            'paymentsByMethod' => [
                'cash' => (float) ($paymentsByMethod['cash'] ?? 0),
                'click' => (float) ($paymentsByMethod['click'] ?? 0),
                'other' => (float) collect($paymentsByMethod)->except(['cash', 'click'])->sum(),
            ],
            'topProducts' => $topProducts,
            'salesByMake' => $salesByMake,
            'inventory' => $inventory,
        ]);
    }

    /**
     * Eslatma effektivligi hisoboti. Asosiy ko'rsatkichlar barcha tariflarda,
     * filial kesimi (byBranch) faqat Pro/Maxsus tarifda ko'rsatiladi.
     */
    public function reminders(Request $request, ReminderService $reminderService): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date('date_from'))->startOfDay()
            : now()->subDays(90)->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $restrictToBranch = !$user->canAccessAllBranches() ? $user->branch_id : null;

        $report = $reminderService->effectivenessReport($workshop->id, $dateFrom, $dateTo, $restrictToBranch);

        $canSeeBranchBreakdown = in_array($workshop->subscription_plan, ['pro', 'maxsus'], true);

        return Inertia::render('Reports/ReminderEffectiveness', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'report' => [
                'sent_total' => $report['sent_total'],
                'with_reminder' => $report['with_reminder'],
                'without_reminder' => $report['without_reminder'],
                'by_branch' => $canSeeBranchBreakdown ? $report['by_branch'] : null,
            ],
            'canSeeBranchBreakdown' => $canSeeBranchBreakdown,
        ]);
    }

    /**
     * Filiallarni solishtirish hisoboti — faqat Pro/Maxsus tarifda.
     * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 5.
     */
    public function branches(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        abort_unless(in_array($workshop->subscription_plan, ['pro', 'maxsus'], true), 403, 'Bu hisobot Pro va Maxsus tariflarda mavjud.');
        abort_unless($user->canAccessAllBranches(), 403);

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date('date_from'))->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $branches = $workshop->branches()->orderBy('name')->get(['id', 'name']);

        $salesByBranch = DB::table('service_logs')
            ->join('vehicles', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->where('clients.workshop_id', $workshop->id)
            ->whereBetween('service_logs.service_date', [$dateFrom, $dateTo])
            ->select('service_logs.branch_id')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('SUM(service_logs.total_amount) as revenue')
            ->selectRaw('AVG(service_logs.total_amount) as avg_ticket')
            ->selectRaw('SUM(service_logs.remaining_amount) as credit_extended')
            ->groupBy('service_logs.branch_id')
            ->get()
            ->keyBy('branch_id');

        $expensesByBranch = DB::table('expenses')
            ->where('workshop_id', $workshop->id)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->select('branch_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $newClientsByBranch = DB::table('clients')
            ->where('workshop_id', $workshop->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('branch_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $rows = $branches->map(function ($branch) use ($salesByBranch, $expensesByBranch, $newClientsByBranch) {
            $sales = $salesByBranch->get($branch->id);
            $revenue = (float) ($sales->revenue ?? 0);
            $expenses = (float) ($expensesByBranch[$branch->id] ?? 0);

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'sales_count' => (int) ($sales->sales_count ?? 0),
                'revenue' => $revenue,
                'avg_ticket' => round((float) ($sales->avg_ticket ?? 0), 2),
                'expenses' => $expenses,
                'net_profit' => $revenue - $expenses,
                'credit_extended' => (float) ($sales->credit_extended ?? 0),
                'new_clients' => (int) ($newClientsByBranch[$branch->id] ?? 0),
            ];
        });

        return Inertia::render('Reports/BranchComparison', [
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'branches' => $rows,
        ]);
    }
}
