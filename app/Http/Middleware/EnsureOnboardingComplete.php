<?php

namespace App\Http\Middleware;

use App\Models\Vehicle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Onboarding hali tugamagan workshoplarni joriy qadamiga qayta yo'naltiradi.
 * Faqat joriy qadamning o'z marshruti (va universal logout/skip) ochiq
 * qoladi — na oldinga, na orqaga o'tib bo'lmaydi.
 */
class EnsureOnboardingComplete
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $workshop = $user->currentWorkshop();

        if (!$workshop || !$workshop->isOnboarding()) {
            return $next($request);
        }

        if ($request->routeIs('logout') || $request->routeIs('onboarding.skip') || $request->routeIs('workshops.switch*')) {
            return $next($request);
        }

        if ($workshop->onboarding_step === 'sale') {
            if ($request->routeIs('vehicles.show') || $request->routeIs('service-logs.store')) {
                return $next($request);
            }

            $vehicle = Vehicle::whereHas('client', fn ($q) => $q->where('workshop_id', $workshop->id))->orderBy('id')->first();

            if ($vehicle) {
                return redirect()->route('vehicles.show', $vehicle);
            }

            $workshop->advanceOnboarding('vehicle');

            return redirect()->route('onboarding.vehicle');
        }

        $currentStepPattern = $workshop->onboarding_step === 'vehicle' ? 'onboarding.vehicle*' : 'onboarding.products*';

        if ($request->routeIs($currentStepPattern)) {
            return $next($request);
        }

        return redirect()->route($workshop->onboarding_step === 'vehicle' ? 'onboarding.vehicle' : 'onboarding.products');
    }
}
