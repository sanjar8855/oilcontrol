<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveWorkshop
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin() && !session('active_workshop_id')) {
            if (!$request->routeIs('workshops.switch*') && !$request->routeIs('workshops.*') && !$request->routeIs('logout') && !$request->routeIs('profile.*') && !$request->routeIs('global-products.*') && !$request->routeIs('brands.*') && !$request->routeIs('subscription-payments.*')) {
                return redirect()->route('workshops.switch.index');
            }
        }

        return $next($request);
    }
}
