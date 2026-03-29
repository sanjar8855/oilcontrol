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

        // Faqat superadmin va director ko'ra oladi
        if (!$user->isSuperAdmin() && !$user->isDirector()) {
            abort(403, 'Sizda bu sahifani ko\'rish uchun ruxsat yo\'q');
        }

        $workshop = $user->workshop;

        // Superadmin barcha foydalanuvchilarni ko'ra oladi
        // Director faqat o'z workshop'idagi foydalanuvchilarni ko'radi
        $query = User::query();

        if ($user->isDirector()) {
            // Director faqat o'z workshop'iga tegishli foydalanuvchilarni ko'radi
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

        $users = $query->with(['workshop', 'branch'])
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

        // Faqat superadmin va director yarata oladi
        if (!$user->isSuperAdmin() && !$user->isDirector()) {
            abort(403, 'Sizda foydalanuvchi qo\'shish uchun ruxsat yo\'q');
        }

        $workshop = $user->workshop;

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

        // Faqat superadmin va director yarata oladi
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDirector()) {
            abort(403, 'Sizda foydalanuvchi qo\'shish uchun ruxsat yo\'q');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|string|in:superadmin,director,manager,employee',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Superadmin emas bo'lsa, superadmin yarata olmaydi
        if ($validated['role'] === 'superadmin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Faqat super admin boshqa super admin yarata oladi');
        }

        // Branch tekshiruvi
        if (isset($validated['branch_id'])) {
            $branch = Branch::findOrFail($validated['branch_id']);
            if ($branch->workshop_id !== $currentUser->workshop->id) {
                abort(403, 'Bu filial sizga tegishli emas');
            }
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user): Response
    {
        $currentUser = $request->user();

        // Access control
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDirector()) {
            abort(403);
        }

        // Director faqat o'z workshop'idagi foydalanuvchilarni ko'ra oladi
        if ($currentUser->isDirector()) {
            if ($user->branch && $user->branch->workshop_id !== $currentUser->workshop->id) {
                abort(403);
            }
        }

        $user->load(['workshop', 'branch']);

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

        // Access control
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDirector()) {
            abort(403, 'Sizda tahrirlash uchun ruxsat yo\'q');
        }

        // Director faqat o'z workshop'idagi foydalanuvchilarni tahrirlashi mumkin
        if ($currentUser->isDirector()) {
            if ($user->branch && $user->branch->workshop_id !== $currentUser->workshop->id) {
                abort(403);
            }
            // Director superadmin'ni tahrirlashi mumkin emas
            if ($user->isSuperAdmin()) {
                abort(403, 'Super admin\'ni tahrirlash mumkin emas');
            }
        }

        $workshop = $currentUser->workshop;

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

        // Access control
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDirector()) {
            abort(403, 'Sizda tahrirlash uchun ruxsat yo\'q');
        }

        // Director faqat o'z workshop'idagi foydalanuvchilarni tahrirlashi mumkin
        if ($currentUser->isDirector()) {
            if ($user->branch && $user->branch->workshop_id !== $currentUser->workshop->id) {
                abort(403);
            }
            // Director superadmin'ni tahrirlashi mumkin emas
            if ($user->isSuperAdmin()) {
                abort(403, 'Super admin\'ni tahrirlash mumkin emas');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'required|string|in:superadmin,director,manager,employee',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Superadmin emas bo'lsa, role'ni superadmin qila olmaydi
        if ($validated['role'] === 'superadmin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Faqat super admin role berishi mumkin');
        }

        // Branch tekshiruvi
        if (isset($validated['branch_id'])) {
            $branch = Branch::findOrFail($validated['branch_id']);
            if ($branch->workshop_id !== $currentUser->workshop->id) {
                abort(403, 'Bu filial sizga tegishli emas');
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ];

        // Agar parol kiritilgan bo'lsa, uni yangilash
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi ma\'lumotlari yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Access control
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDirector()) {
            abort(403, 'Sizda o\'chirish uchun ruxsat yo\'q');
        }

        // O'zini o'chirish mumkin emas
        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'O\'zingizni o\'chira olmaysiz!');
        }

        // Director superadmin'ni o'chira olmaydi
        if ($currentUser->isDirector() && $user->isSuperAdmin()) {
            abort(403, 'Super admin\'ni o\'chirish mumkin emas');
        }

        // Director faqat o'z workshop'idagi foydalanuvchilarni o'chirishi mumkin
        if ($currentUser->isDirector()) {
            if ($user->branch && $user->branch->workshop_id !== $currentUser->workshop->id) {
                abort(403);
            }
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Foydalanuvchi o\'chirildi!');
    }
}
