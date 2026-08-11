<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel tilini foydalanuvchi profilidagi `locale` ustuniga qarab o'rnatadi.
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 4 "Rus tili".
 */
class SetLocale
{
    private const SUPPORTED = ['uz', 'ru'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale && in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
