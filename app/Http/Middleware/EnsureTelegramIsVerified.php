<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yangi ro'yxatdan o'tgan (Telegram orqali hali tasdiqlamagan) userlarni
 * /telegram/verify sahifasiga qaytaradi. EnsureOnboardingComplete
 * patterniga o'xshash, lekin onboarding boshlanishidan OLDIN ishga tushishi
 * kerak — shuning uchun bootstrap/app.php'da EnsureOnboardingComplete'dan
 * oldin ro'yxatga olinadi.
 */
class EnsureTelegramIsVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->telegram_verified_at) {
            return $next($request);
        }

        if ($request->routeIs('telegram.verify*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect('/telegram/verify');
    }
}
