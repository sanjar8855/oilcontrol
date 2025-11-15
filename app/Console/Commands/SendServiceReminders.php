<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendServiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bugungi servis eslatmalarini yuborish (SMS/Telegram)';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService)
    {
        $this->info('Bugungi eslatmalarni tekshirish...');

        $todayReminders = $reminderService->getTodayReminders();

        if ($todayReminders->isEmpty()) {
            $this->info('Bugun yuborilishi kerak bo\'lgan eslatmalar yo\'q.');
            return Command::SUCCESS;
        }

        $this->info("Jami {$todayReminders->count()} ta eslatma topildi.");

        $sent = 0;
        $failed = 0;

        foreach ($todayReminders as $reminder) {
            $client = $reminder->serviceLog->vehicle->client;
            $this->line("Eslatma yuborilmoqda: {$client->name} ({$client->phone})...");

            if ($reminderService->sendReminder($reminder)) {
                $sent++;
                $this->info("✓ Muvaffaqiyatli yuborildi!");
            } else {
                $failed++;
                $this->error("✗ Yuborishda xato!");
            }
        }

        $this->newLine();
        $this->info("Yakunlandi! Yuborildi: {$sent}, Xato: {$failed}");

        return Command::SUCCESS;
    }
}
