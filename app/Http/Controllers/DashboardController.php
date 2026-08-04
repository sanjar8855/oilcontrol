<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        // Agar direktor hali workshopga ega bo'lmasa, yangi yaratamiz (backup)
        if (!$workshop && $user->isDirector()) {
            $workshop = $user->workshop()->create([
                'name' => $user->name . ' Ustaxonasi',
                'owner_name' => $user->name,
                'phone' => '',
                'subscription_plan' => 'free',
                'subscription_expires_at' => now()->addDays(30),
                'is_active' => true,
            ]);
        }

        // Statistika
        $totalClients = $workshop->clients()->count();
        $recentClients = $workshop->clients()
            ->with('vehicles.latestService')
            ->latest()
            ->take(5)
            ->get();

        // Moliyaviy statistika (joriy oy)
        $currentMonth = now()->startOfMonth();
        $totalProducts = $workshop->products()->count();
        $lowStockProducts = $workshop->products()
            ->whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->count();

        // Ombor qiymati
        $inventoryValue = $workshop->products()
            ->selectRaw('SUM(stock_quantity * purchase_price) as total')
            ->value('total') ?? 0;

        // Joriy oydagi xarajatlar
        $monthlyExpenses = $workshop->expenses()
            ->where('expense_date', '>=', $currentMonth)
            ->sum('amount');

        // Joriy oydagi daromad (service logs)
        $monthlyRevenue = $workshop->clients()
            ->join('vehicles', 'clients.id', '=', 'vehicles.client_id')
            ->join('service_logs', 'vehicles.id', '=', 'service_logs.vehicle_id')
            ->where('service_logs.service_date', '>=', $currentMonth)
            ->sum('service_logs.cost');

        // Foyda/Zarar
        $profitLoss = $monthlyRevenue - $monthlyExpenses;

        return Inertia::render('Dashboard', [
            'workshop' => $workshop,
            'stats' => [
                'total_clients' => $totalClients,
                'subscription_plan' => $workshop->subscription_plan,
                'subscription_expires_at' => $workshop->subscription_expires_at,
                'days_remaining' => $workshop->subscription_expires_at
                    ? now()->diffInDays($workshop->subscription_expires_at, false)
                    : 0,
                // Moliyaviy
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'inventory_value' => (float) $inventoryValue,
                'monthly_revenue' => (float) $monthlyRevenue,
                'monthly_expenses' => (float) $monthlyExpenses,
                'profit_loss' => (float) $profitLoss,
            ],
            'recent_clients' => $recentClients,
            'carMakeGroups' => CarMake::optionGroups(),
        ]);
    }
}
