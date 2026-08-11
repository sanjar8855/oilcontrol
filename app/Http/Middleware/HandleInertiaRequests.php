<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $workshop = $user?->currentWorkshop();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user?->getAllPermissions()->pluck('name') ?? [],
            ],
            'activeWorkshop' => $user && $user->isSuperAdmin()
                ? $workshop?->only(['id', 'name'])
                : null,
            'subscription' => $workshop ? [
                'active' => $workshop->isSubscriptionActive(),
                'onTrial' => $workshop->isOnTrial(),
                'daysRemaining' => $workshop->daysUntilExpiry(),
                'plan' => $workshop->subscription_plan,
            ] : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Closure sifatida — Inertia javobni tayyorlashda (route handler ichida
            // App::setLocale() chaqirilgandan KEYIN, masalan /ru landing marshrutida) hisoblanadi.
            'locale' => fn () => app()->getLocale(),
            'translations' => fn () => array_merge(trans('app'), ['landing' => trans('landing')]),
        ];
    }
}
