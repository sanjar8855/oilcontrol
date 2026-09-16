<?php

namespace App\Console\Commands;

use App\Services\UserTelegramBotService;
use Illuminate\Console\Command;

class SetUsersTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-users-webhook';

    protected $description = "Users bot (@oilcontrol_customers_bot) uchun Telegram webhookni o'rnatish";

    public function handle(UserTelegramBotService $telegram): int
    {
        $url = route('telegram.users-webhook');
        $secret = (string) config('services.telegram.users_bot_webhook_secret');

        $result = $telegram->setWebhook($url, $secret);

        $this->info(json_encode($result));

        return (isset($result['ok']) && $result['ok']) ? self::SUCCESS : self::FAILURE;
    }
}
