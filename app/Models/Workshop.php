<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Workshop extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'owner_name',
        'phone',
        'email',
        'address',
        'subscription_plan',
        'subscription_expires_at',
        'trial_ends_at',
        'limits_override',
        'is_active',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'limits_override' => 'array',
        'is_active' => 'boolean',
        'onboarding_completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Workshop hali majburiy o'qitish siklini (mahsulot -> moshina -> savdo)
     * tugatmagan bo'lsa true.
     */
    public function isOnboarding(): bool
    {
        return $this->onboarding_step !== null;
    }

    /**
     * Onboarding siklini keyingi qadamga o'tkazadi. $nextStep null bo'lsa,
     * sikl yakunlangan deb belgilanadi (tabiiy yakun yoki "chiqish").
     */
    public function advanceOnboarding(?string $nextStep): void
    {
        $this->update([
            'onboarding_step' => $nextStep,
            'onboarding_completed_at' => $nextStep === null ? now() : null,
        ]);
    }

    /**
     * Hozir sinov muddatida (haqiqiy to'lov hali qilinmagan) ekanligini tekshiradi.
     */
    public function isOnTrial(): bool
    {
        return !$this->subscription_expires_at
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Obuna (yoki sinov muddati) hozir amalda faolmi.
     */
    public function isSubscriptionActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->subscription_expires_at) {
            return $this->subscription_expires_at->isFuture();
        }

        return $this->isOnTrial();
    }

    /**
     * Obuna (yoki sinov) qachon tugashini qaytaradi.
     */
    public function effectiveExpiresAt(): ?Carbon
    {
        return $this->subscription_expires_at ?? $this->trial_ends_at;
    }

    /**
     * Obuna tugashiga qancha kun qolganini qaytaradi (o'tib ketgan bo'lsa manfiy).
     */
    public function daysUntilExpiry(): ?int
    {
        $expiresAt = $this->effectiveExpiresAt();

        if (!$expiresAt) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);
    }

    /**
     * config/plans.php dagi tarif limitlari, workshops.limits_override bilan
     * ustidan yozilgan holda ("Maxsus" tarif uchun individual limitlar).
     *
     * @return array{name: string, price: ?int, branches: ?int, users: ?int}
     */
    public function planLimits(): array
    {
        $planKey = $this->subscription_plan === 'trial' ? 'start' : $this->subscription_plan;

        $defaults = config("plans.plans.{$planKey}") ?? config('plans.plans.start');

        return array_merge($defaults, array_filter(
            $this->limits_override ?? [],
            fn ($value) => $value !== null
        ));
    }

    public function branchLimit(): ?int
    {
        return $this->planLimits()['branches'] ?? null;
    }

    public function userLimit(): ?int
    {
        return $this->planLimits()['users'] ?? null;
    }

    /**
     * Direktor + shu workshop filiallariga biriktirilgan barcha xodimlar soni
     * ("faol foydalanuvchi" — login qila oladigan barcha user, director ham hisoblanadi).
     */
    public function activeUsersCount(): int
    {
        $branchIds = $this->branches()->pluck('id');

        return User::where('id', $this->user_id)
            ->orWhereIn('branch_id', $branchIds)
            ->count();
    }
}
