<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->workshop;

        // Agar workshop bo'lmasa, yangi yaratamiz (backup)
        if (!$workshop) {
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

        return Inertia::render('Dashboard', [
            'workshop' => $workshop,
            'stats' => [
                'total_clients' => $totalClients,
                'subscription_plan' => $workshop->subscription_plan,
                'subscription_expires_at' => $workshop->subscription_expires_at,
                'days_remaining' => $workshop->subscription_expires_at
                    ? now()->diffInDays($workshop->subscription_expires_at, false)
                    : 0,
            ],
            'recent_clients' => $recentClients,
        ]);
    }
}
