<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obuna (yoki sinov muddati) tugagan kompaniyalarni "faqat o'qish" rejimiga
 * o'tkazadi: GET va eksport ochiq qoladi, yozish amallari (POST/PUT/PATCH/DELETE)
 * bloklanadi. Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 1.
 */
class CheckSubscription
{
    /**
     * Bu marshrutlar obuna tugagan bo'lsa ham har doim ochiq qoladi —
     * aks holda kompaniya to'lov qilish yoki tizimdan chiqib ketishning
     * iloji bo'lmay qoladi.
     */
    private const ALWAYS_ALLOWED = [
        'logout',
        'profile.*',
        'workshops.switch*',
        'workshops.*',
        'subscription-payments.*',
        'global-products.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $request->isMethodSafe()) {
            return $next($request);
        }

        foreach (self::ALWAYS_ALLOWED as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        $workshop = $user->currentWorkshop();

        if (!$workshop || $workshop->isSubscriptionActive()) {
            return $next($request);
        }

        return back()->with('error', 'Obuna muddati tugagan. Yozish amallari uchun to\'lovni yangilang — ma\'lumotlarni ko\'rish va eksport qilish davom etadi.');
    }
}
