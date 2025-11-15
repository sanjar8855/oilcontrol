<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\ServiceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderService
{
    protected TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }
    /**
     * ServiceLog yaratilganda avtomatik eslatmalarni yaratish
     */
    public function createRemindersForServiceLog(ServiceLog $serviceLog): void
    {
        // Agar avg_monthly_km bo'lmasa, standart 1000 km/oy deb hisoblaymiz
        $avgMonthlyKm = $serviceLog->avg_monthly_km ?? 1000;

        // Keyingi servis necha oy ichida bo'lishini hisoblash
        $monthsUntilNextService = $serviceLog->next_service_km / $avgMonthlyKm;

        // Keyingi servis sanasini taxminiy hisoblash
        $estimatedNextServiceDate = Carbon::parse($serviceLog->service_date)
            ->addMonths((int) ceil($monthsUntilNextService));

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

}
