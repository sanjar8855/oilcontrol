<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Reminder;
use App\Models\ServiceLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 2 "Eslatma sifati".
 */

class ReminderService
{
    protected TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    /**
     * Avtomobilning probegi (ServiceLog yaratilgan/yangilangan) o'zgarganda chaqiriladi.
     * Eski (hali yuborilmagan) eslatmalar endi eskirgan hisoblanadi va o'chiriladi,
     * so'ng eng oxirgi ServiceLog asosida qaytadan quriladi. Allaqachon yuborilgan
     * eslatmalar (status=sent/failed) — eslatma effektivligi hisoboti uchun — teginilmaydi.
     */
    public function recalculateForVehicle(int $vehicleId): void
    {
        Reminder::whereHas('serviceLog', fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->where('status', 'pending')
            ->delete();

        $latestServiceLog = ServiceLog::where('vehicle_id', $vehicleId)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->first();

        if ($latestServiceLog) {
            $this->createRemindersForServiceLog($latestServiceLog);
        }
    }

    /**
     * ServiceLog yaratilganda avtomatik eslatmalarni yaratish
     */
    public function createRemindersForServiceLog(ServiceLog $serviceLog): void
    {
        $estimatedNextServiceDate = $this->estimateDueDate($serviceLog);

        // 3 xil eslatma yaratamiz:
        // 1. 30 kun oldin
        // 2. 14 kun oldin
        // 3. 7 kun oldin

        $reminderDays = [30, 14, 7];

        foreach ($reminderDays as $days) {
            $scheduledDate = $estimatedNextServiceDate->copy()->subDays($days);

            // Faqat kelajakdagi sanalar uchun eslatma yaratamiz
            if ($scheduledDate->isFuture()) {
                Reminder::create([
                    'service_log_id' => $serviceLog->id,
                    'scheduled_date' => $scheduledDate,
                    'status' => 'pending',
                    'notification_type' => 'telegram', // Faqat Telegram
                    'message' => $this->generateReminderMessage($serviceLog, $days),
                ]);
            }
        }
    }

    /**
     * ServiceLog ma'lumotlari asosida keyingi servis sanasini taxminiy hisoblash
     * (avg_monthly_km bo'lmasa, standart 1000 km/oy deb olinadi).
     * Public — Mini App garaj ko'rinishi ham xuddi shu formuladan foydalanadi.
     */
    public function estimateDueDate(ServiceLog $serviceLog): Carbon
    {
        $avgMonthlyKm = $serviceLog->avg_monthly_km ?: 1000;
        $monthsUntilNextService = $serviceLog->next_service_km / $avgMonthlyKm;

        return Carbon::parse($serviceLog->service_date)
            ->addMonths((int) ceil($monthsUntilNextService));
    }

    /**
     * Eslatma xabarini yaratish
     */
    protected function generateReminderMessage(ServiceLog $serviceLog, int $daysBeforeService): string
    {
        $vehicle = $serviceLog->vehicle;
        $client = $vehicle->client;
        $nextServiceKm = $serviceLog->odometer_reading + $serviceLog->next_service_km;

        return "Hurmatli {$client->name}! " .
               "Sizning {$vehicle->make} {$vehicle->model} mashinangiz uchun " .
               "{$daysBeforeService} kundan keyin servis vaqti keladi. " .
               "Oxirgi servis: {$serviceLog->odometer_reading} km. " .
               "Keyingi servis: {$nextServiceKm} km. " .
               "Aloqa: {$client->workshop->phone}";
    }

    /**
     * Bugungi eslatmalarni olish
     */
    public function getTodayReminders()
    {
        return Reminder::where('scheduled_date', '<=', now()->toDateString())
            ->where('status', 'pending')
            ->with(['serviceLog.vehicle.client.workshop'])
            ->get();
    }

    /**
     * Eslatmani yuborish (faqat Telegram)
     */
    public function sendReminder(Reminder $reminder): bool
    {
        try {
            $serviceLog = $reminder->serviceLog;
            $vehicle = $serviceLog->vehicle;
            $client = $vehicle->client;
            $workshop = $client->workshop;

            // Agar Telegram ID bo'lmasa, skip
            if (!$client->telegram_id) {
                Log::warning("Mijoz {$client->name} uchun Telegram ID yo'q. Eslatma yuborilmadi.");
                $reminder->update(['status' => 'failed']);
                return false;
            }

            // Eslatma ma'lumotlarini tayyorlash
            $data = [
                'client_name' => $client->name,
                'vehicle_make' => $vehicle->make,
                'vehicle_model' => $vehicle->model,
                'days_remaining' => now()->diffInDays($reminder->scheduled_date),
                'last_service_km' => number_format($serviceLog->odometer_reading),
                'next_service_km' => number_format($serviceLog->odometer_reading + $serviceLog->next_service_km),
                'service_type' => $serviceLog->service_type,
                'workshop_name' => $workshop->name,
                'workshop_phone' => $workshop->phone,
                'locale' => $client->locale ?? 'uz',
            ];

            // Telegram orqali yuborish
            $sent = $this->telegramBot->sendServiceReminder($client->telegram_id, $data);

            if ($sent) {
                $reminder->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                return true;
            }

            $reminder->update(['status' => 'failed']);
            return false;

        } catch (\Exception $e) {
            // Xato bo'lsa, failed deb belgilaymiz
            $reminder->update(['status' => 'failed']);
            Log::error('Reminder yuborishda xato: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eslatma effektivligi hisoboti: yuborilgan eslatmalardan keyin mijoz 30 kun ichida
     * qaytdimi, qaytganlar qancha tushum keltirdi, va bu — eslatma yuborilmagan
     * (masalan Telegram ulanmagan) holatlar bilan taqqoslaganda qanday farq beradi.
     *
     * Har bir ServiceLog uchun "muddati kelgan sana" avvalgidek (avg_monthly_km asosida)
     * hisoblanadi; shu sana o'tgan va tanlangan davr ichida bo'lgan yozuvlargina hisobga
     * olinadi (hali muddati kelmagan servislar bo'yicha "qaytdi/qaytmadi" hali noma'lum).
     *
     * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 2.
     */
    public function effectivenessReport(int $workshopId, Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $vehicleIds = Vehicle::whereHas('client', fn ($q) => $q->where('workshop_id', $workshopId))
            ->pluck('id');

        $logsQuery = ServiceLog::whereIn('vehicle_id', $vehicleIds)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('vehicle_id')
            ->orderBy('service_date');

        $logs = $logsQuery->get(['id', 'vehicle_id', 'branch_id', 'service_date', 'next_service_km', 'avg_monthly_km', 'total_amount']);

        $sentServiceLogIds = Reminder::where('status', 'sent')
            ->whereIn('service_log_id', $logs->pluck('id'))
            ->pluck('service_log_id')
            ->flip();

        $branchNames = Branch::whereIn('id', $logs->pluck('branch_id')->filter()->unique())
            ->pluck('name', 'id');

        $rows = collect();

        foreach ($logs->groupBy('vehicle_id') as $vehicleLogs) {
            $vehicleLogs = $vehicleLogs->values();

            foreach ($vehicleLogs as $i => $log) {
                $dueDate = $this->estimateDueDate($log);

                // Hali muddati kelmagan yoki tanlangan davrdan tashqarida bo'lsa — o'tkazib yuboriladi
                if ($dueDate->isFuture() || $dueDate->lt($from) || $dueDate->gt($to)) {
                    continue;
                }

                $nextLog = $vehicleLogs->get($i + 1);
                $returned = false;
                $revenue = 0.0;

                if ($nextLog) {
                    $nextDate = Carbon::parse($nextLog->service_date);
                    if ($nextDate->between($dueDate, $dueDate->copy()->addDays(30))) {
                        $returned = true;
                        $revenue = (float) $nextLog->total_amount;
                    }
                }

                $rows->push([
                    'had_reminder' => $sentServiceLogIds->has($log->id),
                    'returned' => $returned,
                    'revenue' => $revenue,
                    'branch_id' => $log->branch_id,
                    'branch_name' => $log->branch_id ? ($branchNames[$log->branch_id] ?? null) : null,
                ]);
            }
        }

        $summarize = function ($collection) {
            $total = $collection->count();
            $returned = $collection->where('returned', true)->count();

            return [
                'total' => $total,
                'returned' => $returned,
                'return_rate' => $total > 0 ? round($returned / $total * 100, 1) : 0,
                'revenue' => round($collection->sum('revenue'), 2),
            ];
        };

        $withReminder = $rows->where('had_reminder', true);
        $withoutReminder = $rows->where('had_reminder', false);

        $byBranch = $rows->whereNotNull('branch_id')->groupBy('branch_name')->map(function ($branchRows) use ($summarize) {
            $withR = $branchRows->where('had_reminder', true);
            $withoutR = $branchRows->where('had_reminder', false);

            return [
                'with_reminder' => $summarize($withR),
                'without_reminder' => $summarize($withoutR),
            ];
        });

        return [
            'sent_total' => Reminder::where('status', 'sent')
                ->whereIn('service_log_id', $logs->pluck('id'))
                ->whereBetween('sent_at', [$from, $to])
                ->count(),
            'with_reminder' => $summarize($withReminder),
            'without_reminder' => $summarize($withoutReminder),
            'by_branch' => $byBranch,
        ];
    }
}
