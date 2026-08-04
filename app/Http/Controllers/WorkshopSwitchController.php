<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopSwitchController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }
    }

    /**
     * Superadmin uchun workshoplar ro'yxati — qaysi biri nomidan ishlashni tanlash.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $workshops = Workshop::withCount(['clients', 'products'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/WorkshopSwitch', [
            'workshops' => $workshops,
            'activeWorkshopId' => session('active_workshop_id'),
        ]);
    }

    /**
     * Tanlangan workshop nomidan ishlashni boshlash.
     */
    public function switch(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->authorizeAccess($request);

        session(['active_workshop_id' => $workshop->id]);

        return redirect()->route('dashboard')
            ->with('success', "\"{$workshop->name}\" nomidan ishlamoqdasiz!");
    }

    /**
     * Workshop tanlovini bekor qilish (workshoplar ro'yxatiga qaytish).
     */
    public function exit(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        session()->forget('active_workshop_id');

        return redirect()->route('workshops.switch.index');
    }
}
