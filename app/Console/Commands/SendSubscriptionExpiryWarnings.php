<?php

namespace App\Console\Commands;

use App\Models\Workshop;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

/**
 * Obuna (yoki sinov muddati) 7/3/1 kun ichida tugaydigan kompaniyalar
 * direktoriga Telegram orqali ogohlantirish yuboradi.
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 1.
 */
class SendSubscriptionExpiryWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obuna muddati 7/3/1 kunda tugaydigan kompaniyalar direktoriga Telegram orqali ogohlantirish yuborish';

    public function handle(TelegramBotService $telegram)
    {
        $warningDays = config('plans.warning_days', [7, 3, 1]);

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('subscription_expires_at')->orWhereNotNull('trial_ends_at');
            })
            ->with('user:id,name,telegram_chat_id')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($workshops as $workshop) {
            $daysRemaining = $workshop->daysUntilExpiry();

            if ($daysRemaining === null || !in_array($daysRemaining, $warningDays, true)) {
                continue;
            }

            $chatId = $workshop->user?->telegram_chat_id;

            if (!$chatId) {
                $skipped++;
                $this->line("O'tkazib yuborildi (Telegram ulanmagan): {$workshop->name}");
                continue;
            }

            $label = $workshop->isOnTrial() ? 'Sinov muddati' : 'Obuna';
            $message = "⚠️ <b>{$workshop->name}</b>\n\n{$label} <b>{$daysRemaining} kundan</b> so'ng tugaydi.\n\nUzaytirish uchun tizim administratori bilan bog'laning.";

            if ($telegram->sendMessage($chatId, $message)) {
                $sent++;
                $this->info("Yuborildi: {$workshop->name} ({$daysRemaining} kun qoldi)");
            } else {
                $this->error("Xato: {$workshop->name}");
            }
        }

        $this->newLine();
        $this->info("Yakunlandi! Yuborildi: {$sent}, O'tkazib yuborildi: {$skipped}");

        return Command::SUCCESS;
    }
}
