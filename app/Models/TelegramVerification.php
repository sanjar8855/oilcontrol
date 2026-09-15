<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramVerification extends Model
{
    protected $fillable = [
        'user_id',
        'link_token',
        'code_hash',
        'code_expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'code_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
