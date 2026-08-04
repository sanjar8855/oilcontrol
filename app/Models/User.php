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

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login',
        'email',
        'phone',
        'phone_secondary',
        'password',
        'role',
        'branch_id',
        'salary',
        'hire_date',
        'position',
        'employment_status',
        'address',
    ];

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
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function canAccessAllBranches(): bool
    {
        return in_array($this->role, ['superadmin', 'director']);
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->canAccessAllBranches()) {
            return true;
        }

        return $this->branch_id === $branchId;
    }
}
