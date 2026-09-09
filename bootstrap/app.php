<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureActiveWorkshop::class,
            \App\Http\Middleware\CheckSubscription::class,
            \App\Http\Middleware\EnsureOnboardingComplete::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Har kuni ertalab soat 9:00 da eslatmalarni yuborish
        $schedule->command('reminders:send')->dailyAt('09:00');

        // Har kuni ertalab soat 8:00 da obuna tugash ogohlantirishlarini yuborish
        $schedule->command('subscriptions:notify-expiring')->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
