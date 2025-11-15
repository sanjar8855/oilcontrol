<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\ServiceLog;
use Carbon\Carbon;

class ReminderService
{
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
                    'notification_type' => 'sms', // Default SMS, keyinchalik Telegram ham qo'shiladi
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
     * Eslatmani yuborish (SMS yoki Telegram)
     */
    public function sendReminder(Reminder $reminder): bool
    {
        try {
            $client = $reminder->serviceLog->vehicle->client;

            // Telefon raqami bo'lsa SMS yuboramiz
            if ($client->phone && $reminder->notification_type === 'sms') {
                $this->sendSMS($client->phone, $reminder->message);
            }

            // Telegram ID bo'lsa Telegram orqali yuboramiz
            if ($client->telegram_id && $reminder->notification_type === 'telegram') {
                $this->sendTelegram($client->telegram_id, $reminder->message);
            }

            // Eslatma yuborilgan deb belgilaymiz
            $reminder->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Xato bo'lsa, failed deb belgilaymiz
            $reminder->update([
                'status' => 'failed',
            ]);

            \Log::error('Reminder yuborishda xato: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * SMS yuborish (Eskiz.uz yoki Playmobile API orqali)
     */
    protected function sendSMS(string $phoneNumber, string $message): void
    {
        // TODO: Eskiz.uz yoki Playmobile API integratsiyasi
        // Hozircha log ga yozamiz
        \Log::info("SMS yuborildi: {$phoneNumber} - {$message}");

        // Misol: Eskiz.uz API
        // $response = Http::post('https://notify.eskiz.uz/api/message/sms/send', [
        //     'mobile_phone' => $phoneNumber,
        //     'message' => $message,
        //     'from' => '4546',
        //     'callback_url' => route('sms.callback')
        // ]);
    }

    /**
     * Telegram orqali yuborish
     */
    protected function sendTelegram(string $telegramId, string $message): void
    {
        // TODO: Telegram Bot API integratsiyasi
        // Hozircha log ga yozamiz
        \Log::info("Telegram yuborildi: {$telegramId} - {$message}");

        // Misol: Telegram Bot API
        // $botToken = config('services.telegram.bot_token');
        // $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
        //     'chat_id' => $telegramId,
        //     'text' => $message,
        // ]);
    }
}
