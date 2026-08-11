<?php

namespace App\Http\Controllers\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Telegram Mini App uchun mijozga qaratilgan (read-only) API.
 * Autentifikatsiya Laravel session/Sanctum orqali emas, VerifyTelegramInitData
 * middleware tekshirgan Telegram chat_id orqali amalga oshadi.
 *
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 3 "Mening garajim".
 */
class GarageController extends Controller
{
    /**
     * Shu Telegram akkauntga bog'langan barcha mijoz yozuvlari (turli moyxonalarda
     * bo'lishi mumkin) va ularning avtomobillari — bitta umumiy ro'yxatda,
     * moyxona tanlagichisiz (docs 3-bo'lim: "Ko'p-moyxona holati").
     */
    public function index(Request $request, ReminderService $reminderService): JsonResponse
    {
        $telegramId = $request->attributes->get('telegram_user')['id'];

        $clients = Client::where('telegram_id', $telegramId)
            ->with(['workshop:id,name,phone', 'vehicles.latestService'])
            ->get();

        $result = $clients->map(function (Client $client) use ($reminderService) {
            $vehicleIds = $client->vehicles->pluck('id');
            $debt = ServiceLog::whereIn('vehicle_id', $vehicleIds)->sum('remaining_amount');

            return [
                'id' => $client->id,
                'name' => $client->name,
                'workshop_name' => $client->workshop?->name,
                'workshop_phone' => $client->workshop?->phone,
                'debt' => (float) $debt,
                'vehicles' => $client->vehicles->map(function (Vehicle $vehicle) use ($reminderService) {
                    $latest = $vehicle->latestService;

                    $nextServiceKm = null;
                    $nextServiceDate = null;
                    $daysRemaining = null;

                    if ($latest) {
                        $nextServiceKm = $latest->odometer_reading + $latest->next_service_km;
                        $nextServiceDate = $reminderService->estimateDueDate($latest);
                        $daysRemaining = (int) now()->startOfDay()->diffInDays($nextServiceDate->copy()->startOfDay(), false);
                    }

                    return [
                        'id' => $vehicle->id,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'plate_number' => $vehicle->plate_number,
                        'year' => $vehicle->year,
                        'last_service_date' => $latest?->service_date?->toDateString(),
                        'last_service_km' => $latest?->odometer_reading,
                        'last_service_type' => $latest?->service_type,
                        'next_service_km' => $nextServiceKm,
                        'next_service_date' => $nextServiceDate?->toDateString(),
                        'days_remaining' => $daysRemaining,
                    ];
                }),
            ];
        });

        return response()->json([
            'clients' => $result,
        ]);
    }

    /**
     * Bitta avtomobilning to'liq servis tarixi (kvitansiyalar) va to'lov holati.
     */
    public function vehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        $telegramId = $request->attributes->get('telegram_user')['id'];

        $linkedClientIds = Client::where('telegram_id', $telegramId)->pluck('id');

        if (!$linkedClientIds->contains($vehicle->client_id)) {
            return response()->json(['message' => 'Ruxsat yo\'q'], 403);
        }

        $vehicle->load(['client.workshop', 'serviceLogs' => fn ($q) => $q->orderByDesc('service_date')]);

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'plate_number' => $vehicle->plate_number,
                'year' => $vehicle->year,
            ],
            'workshop_name' => $vehicle->client->workshop?->name,
            'history' => $vehicle->serviceLogs->map(fn ($log) => [
                'id' => $log->id,
                'service_date' => $log->service_date?->toDateString(),
                'service_type' => $log->service_type,
                'odometer_reading' => $log->odometer_reading,
                'next_service_km' => $log->odometer_reading + $log->next_service_km,
                'total_amount' => (float) $log->total_amount,
                'paid_amount' => (float) $log->paid_amount,
                'remaining_amount' => (float) $log->remaining_amount,
                'payment_status' => $log->payment_status,
                'currency' => $log->currency,
                'notes' => $log->notes,
            ]),
        ]);
    }
}
