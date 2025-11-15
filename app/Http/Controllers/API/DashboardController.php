<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\WorkshopResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $workshop = $request->user()->workshop;

        // Statistics
        $totalClients = $workshop->clients()->count();
        $totalVehicles = \App\Models\Vehicle::whereHas('client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })->count();

        $totalServiceLogs = \App\Models\ServiceLog::whereHas('vehicle.client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })->count();

        $pendingReminders = \App\Models\Reminder::whereHas('serviceLog.vehicle.client', function ($query) use ($workshop) {
            $query->where('workshop_id', $workshop->id);
        })
            ->where('status', 'pending')
            ->where('scheduled_date', '<=', now()->toDateString())
            ->count();

        // Recent clients
        $recentClients = $workshop->clients()
            ->withCount('vehicles')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'workshop' => new WorkshopResource($workshop),
            'stats' => [
                'total_clients' => $totalClients,
                'total_vehicles' => $totalVehicles,
                'total_service_logs' => $totalServiceLogs,
                'pending_reminders' => $pendingReminders,
                'subscription_plan' => $workshop->subscription_plan,
                'subscription_expires_at' => $workshop->subscription_expires_at?->toDateString(),
                'days_remaining' => $workshop->subscription_expires_at ?
                    now()->diffInDays($workshop->subscription_expires_at, false) : 0,
            ],
            'recent_clients' => ClientResource::collection($recentClients),
        ]);
    }
}
