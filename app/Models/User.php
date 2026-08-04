<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_secondary',
        'password',
        'branch_id',
        'salary',
        'hire_date',
        'position',
        'employment_status',
        'address',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['role'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'salary' => 'decimal:2',
            'hire_date' => 'date',
        ];
    }

    // Relationships
    public function workshop(): HasOne
    {
        return $this->hasOne(Workshop::class);
    }

    /**
     * Foydalanuvchi hozir amalda ishlayotgan workshop.
     *
     * - Direktor uchun — o'ziga tegishli (egalik qiladigan) workshop.
     * - Menejer/xodim uchun — o'z filiali orqali bog'langan workshop.
     * - Superadmin uchun — sessiyada tanlangan workshop (agar tanlanmagan bo'lsa, null).
     */
    public function currentWorkshop(): ?Workshop
    {
        if ($this->isSuperAdmin()) {
            $workshopId = session('active_workshop_id');

            return $workshopId ? Workshop::find($workshopId) : null;
        }

        if ($this->isDirector()) {
            return $this->workshop;
        }

        return $this->branch?->workshop;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    // Role helper methods
    public function getRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isDirector(): bool
    {
        return $this->hasRole('director');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isEmployee(): bool
    {
        return $this->hasRole('employee');
    }

    public function canAccessAllBranches(): bool
    {
        return $this->hasAnyRole(['superadmin', 'director']);
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->canAccessAllBranches()) {
            return true;
        }

        return $this->branch_id === $branchId;
    }
}
