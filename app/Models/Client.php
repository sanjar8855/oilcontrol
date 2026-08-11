<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $fillable = [
        'workshop_id',
        'branch_id',
        'name',
        'phone',
        'telegram_id',
        'telegram_link_token',
        'telegram_linked_at',
        'locale',
        'email',
        'notes',
    ];

    protected $casts = [
        'telegram_linked_at' => 'datetime',
    ];

    // Relationships
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Botga ulash uchun bir martalik tasodifiy token yaratadi (yoki mavjudini qaytaradi).
     * Docs: docs/strategiya_va_yol_xaritasi.md — 5.2 "Deep-link ulash oqimi".
     */
    public function getOrCreateTelegramLinkToken(): string
    {
        if ($this->telegram_link_token) {
            return $this->telegram_link_token;
        }

        do {
            $token = Str::random(12);
        } while (self::where('telegram_link_token', $token)->exists());

        $this->update(['telegram_link_token' => $token]);

        return $token;
    }
}
