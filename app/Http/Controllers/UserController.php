<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $workshop = $user->currentWorkshop();

        // Superadmin tanlagan workshop nomidan, director esa o'z workshop'i
        // doirasidagi foydalanuvchilarni ko'radi
        $query = User::query();

        if ($workshop) {
            $query->whereHas('workshop', function ($q) use ($workshop) {
                $q->where('id', $workshop->id);
            })->orWhere(function ($q) use ($workshop) {
                // Yoki workshop_id null bo'lsa va shu workshop'ga biriktiriladigan branch'ga tegishli
                $q->whereNull('id')->whereHas('branch', function ($b) use ($workshop) {
                    $b->where('workshop_id', $workshop->id);
                });
            })->orWhere(function ($q) use ($workshop) {
                // Yoki branch orqali shu workshop'ga tegishli
                $q->whereHas('branch', function ($b) use ($workshop) {
                    $b->where('workshop_id', $workshop->id);
                });
            });
        }

        $users = $query->with(['workshop', 'branch', 'roles'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        $workshop = $user->currentWorkshop();

        // Filiallar ro'yxati
        $branches = $workshop->branches()->where('is_active', true)->get(['id', 'name', 'code']);

        // Role'lar ro'yxati
        $roles = [
            ['value' => 'director', 'label' => 'Direktor'],
            ['value' => 'manager', 'label' => 'Menejer'],
            ['value' => 'employee', 'label' => 'Xodim'],
        ];

        // Superadmin boshqa superadmin yarata oladi
        if ($user->isSuperAdmin()) {
            array_unshift($roles, ['value' => 'superadmin', 'label' => 'Super Admin']);
        }

        return Inertia::render('Users/Create', [
            'branches' => $branches,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        $workshop = $currentUser->currentWorkshop();
        if ($workshop) {
            $userLimit = $workshop->userLimit();
            if ($userLimit !== null && $workshop->activeUsersCount() >= $userLimit) {
                return back()->with('error', "Tarifingizda foydalanuvchilar soni {$userLimit} tagacha cheklangan. Ko'proq xodim uchun tarifni yangilang.");
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|string|in:superadmin,director,manager,employee',
            'branch_id' => ['required_unless:role,superadmin,director', 'nullable', 'exists:branches,id'],
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'employment_status' => 'nullable|in:active,on_leave,terminated',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ], [
            'branch_id.required_unless' => 'Menejer va xodim uchun filial tanlash majburiy',
        ]);

        // Superadmin emas bo'lsa, superadmin yarata olmaydi
        if ($validated['role'] === 'superadmin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Faqat super admin boshqa super admin yarata oladi');
        }

        // Branch tekshiruvi
        if (isset($validated['branch_id'])) {
            $branch = Branch::findOrFail($validated['branch_id']);
            if ($branch->workshop_id !== $currentUser->currentWorkshop()?->id) {
                abort(403, 'Bu filial sizga tegishli emas');
            }
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'],
            'salary' => $validated['salary'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'position' => $validated['position'] ?? null,
            'employment_status' => $validated['employment_status'] ?? 'active',
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);
        $newUser->assignRole($validated['role']);

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user): Response
    {
        $currentUser = $request->user();

        // Faqat o'z (yoki tanlangan) workshop'idagi foydalanuvchilarni ko'rish mumkin
        $workshop = $currentUser->currentWorkshop();
        if ($user->branch && $workshop && $user->branch->workshop_id !== $workshop->id) {
            abort(403);
        }

        $user->load(['workshop', 'branch', 'salaries' => function ($query) {
            $query->orderBy('payment_date', 'desc')->limit(10);
        }]);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user): Response
    {
        $currentUser = $request->user();

        // Faqat o'z (yoki tanlangan) workshop'idagi foydalanuvchilarni tahrirlash mumkin
        $workshop = $currentUser->currentWorkshop();
        if ($user->branch && $workshop && $user->branch->workshop_id !== $workshop->id) {
            abort(403);
        }

        // Director superadmin'ni tahrirlashi mumkin emas
        if ($currentUser->isDirector() && $user->isSuperAdmin()) {
            abort(403, 'Super admin\'ni tahrirlash mumkin emas');
        }

        // Filiallar ro'yxati
        $branches = $workshop->branches()->where('is_active', true)->get(['id', 'name', 'code']);

        // Role'lar ro'yxati
        $roles = [
            ['value' => 'director', 'label' => 'Direktor'],
            ['value' => 'manager', 'label' => 'Menejer'],
            ['value' => 'employee', 'label' => 'Xodim'],
        ];

        // Superadmin boshqa superadmin tahrirlashi mumkin
        if ($currentUser->isSuperAdmin()) {
            array_unshift($roles, ['value' => 'superadmin', 'label' => 'Super Admin']);
        }

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'branches' => $branches,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Faqat o'z (yoki tanlangan) workshop'idagi foydalanuvchilarni tahrirlash mumkin
        $currentWorkshop = $currentUser->currentWorkshop();
        if ($user->branch && $currentWorkshop && $user->branch->workshop_id !== $currentWorkshop->id) {
            abort(403);
        }

        // Director superadmin'ni tahrirlashi mumkin emas
        if ($currentUser->isDirector() && $user->isSuperAdmin()) {
            abort(403, 'Super admin\'ni tahrirlash mumkin emas');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'required|string|in:superadmin,director,manager,employee',
            'branch_id' => ['required_unless:role,superadmin,director', 'nullable', 'exists:branches,id'],
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'employment_status' => 'nullable|in:active,on_leave,terminated',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ], [
            'branch_id.required_unless' => 'Menejer va xodim uchun filial tanlash majburiy',
        ]);

        // Superadmin emas bo'lsa, role'ni superadmin qila olmaydi
        if ($validated['role'] === 'superadmin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Faqat super admin role berishi mumkin');
        }

        // Branch tekshiruvi
        if (isset($validated['branch_id'])) {
            $branch = Branch::findOrFail($validated['branch_id']);
            if ($branch->workshop_id !== $currentWorkshop?->id) {
                abort(403, 'Bu filial sizga tegishli emas');
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'branch_id' => $validated['branch_id'],
            'salary' => $validated['salary'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'position' => $validated['position'] ?? null,
            'employment_status' => $validated['employment_status'] ?? 'active',
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        // Agar parol kiritilgan bo'lsa, uni yangilash
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi ma\'lumotlari yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // O'zini o'chirish mumkin emas
        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'O\'zingizni o\'chira olmaysiz!');
        }

        // Director superadmin'ni o'chira olmaydi
        if ($currentUser->isDirector() && $user->isSuperAdmin()) {
            abort(403, 'Super admin\'ni o\'chirish mumkin emas');
        }

        // Faqat o'z (yoki tanlangan) workshop'idagi foydalanuvchilarni o'chirish mumkin
        $workshop = $currentUser->currentWorkshop();
        if ($user->branch && $workshop && $user->branch->workshop_id !== $workshop->id) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi o\'chirildi!');
    }
}
