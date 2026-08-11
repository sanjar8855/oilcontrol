<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Superadmin uchun kompaniyalar (workshoplar) bo'yicha to'liq CRUD.
 * "workshops.switch" (nomidan ishlash) dan farqli o'laroq, bu yerda
 * kompaniyalarning o'zi yaratiladi/tahrirlanadi/o'chiriladi.
 */
class WorkshopController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Workshop::withCount(['clients', 'products', 'categories', 'branches'])
            ->with('user:id,name,phone');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('subscription_plan')) {
            $query->where('subscription_plan', $request->string('subscription_plan')->toString());
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->string('is_active')->toString() === '1');
        }

        $workshops = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Workshops/Index', [
            'workshops' => $workshops,
            'filters' => $request->only(['search', 'subscription_plan', 'is_active']),
            'activeWorkshopId' => session('active_workshop_id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workshops/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'subscription_plan' => 'required|in:trial,start,pro,maxsus',
            'subscription_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'director_name' => 'required|string|max:255',
            'director_phone' => 'required|string|max:20|unique:users,phone',
            'director_password' => ['required', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated) {
            $director = User::create([
                'name' => $validated['director_name'],
                'phone' => $validated['director_phone'],
                'password' => Hash::make($validated['director_password']),
            ]);
            $director->assignRole('director');

            Workshop::create([
                'user_id' => $director->id,
                'name' => $validated['name'],
                'owner_name' => $validated['owner_name'] ?: $validated['director_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'subscription_plan' => $validated['subscription_plan'],
                'subscription_expires_at' => $validated['subscription_expires_at'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
        });

        return redirect()->route('workshops.index')
            ->with('success', 'Yangi kompaniya muvaffaqiyatli qo\'shildi!');
    }

    public function show(Workshop $workshop): Response
    {
        $workshop->loadCount(['clients', 'products', 'categories', 'expenses', 'branches', 'inventories']);
        $workshop->load([
            'user:id,name,phone,email',
            'branches:id,workshop_id,name,code,is_active',
            'subscriptionPayments' => fn ($query) => $query->latest('paid_at')->with('confirmedBy:id,name'),
        ]);

        return Inertia::render('Workshops/Show', [
            'workshop' => $workshop,
            'subscriptionStatus' => [
                'active' => $workshop->isSubscriptionActive(),
                'onTrial' => $workshop->isOnTrial(),
                'daysRemaining' => $workshop->daysUntilExpiry(),
                'limits' => $workshop->planLimits(),
                'usersCount' => $workshop->activeUsersCount(),
            ],
            'plans' => config('plans.plans'),
        ]);
    }

    public function edit(Workshop $workshop): Response
    {
        $workshop->load('user:id,name,phone');

        return Inertia::render('Workshops/Edit', [
            'workshop' => $workshop,
        ]);
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'subscription_plan' => 'required|in:trial,start,pro,maxsus',
            'subscription_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'director_name' => 'required|string|max:255',
            'director_phone' => 'required|string|max:20|unique:users,phone,' . $workshop->user_id,
            'director_password' => ['nullable', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated, $workshop) {
            $workshop->update([
                'name' => $validated['name'],
                'owner_name' => $validated['owner_name'] ?: $validated['director_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'subscription_plan' => $validated['subscription_plan'],
                'subscription_expires_at' => $validated['subscription_expires_at'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $directorUpdate = [
                'name' => $validated['director_name'],
                'phone' => $validated['director_phone'],
            ];

            if (!empty($validated['director_password'])) {
                $directorUpdate['password'] = Hash::make($validated['director_password']);
            }

            $workshop->user()->update($directorUpdate);
        });

        return redirect()->route('workshops.index')
            ->with('success', 'Kompaniya ma\'lumotlari yangilandi!');
    }

    /**
     * Kompaniyani butunlay o'chirish — direktor foydalanuvchisini o'chirish orqali
     * amalga oshiriladi, chunki workshops.user_id FK cascade bo'lgani uchun
     * shu kompaniyaga tegishli barcha ma'lumotlar (mijozlar, mahsulotlar,
     * savdolar va h.k.) ham avtomatik o'chib ketadi.
     */
    public function destroy(Workshop $workshop): RedirectResponse
    {
        DB::transaction(function () use ($workshop) {
            $branchIds = $workshop->branches()->pluck('id');
            User::whereIn('branch_id', $branchIds)->delete();

            $workshop->user()->delete();
        });

        if ((int) session('active_workshop_id') === $workshop->id) {
            session()->forget('active_workshop_id');
        }

        return redirect()->route('workshops.index')
            ->with('success', 'Kompaniya va unga tegishli barcha ma\'lumotlar o\'chirildi!');
    }
}
