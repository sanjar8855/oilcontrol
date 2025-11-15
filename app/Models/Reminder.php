<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'service_log_id',
        'scheduled_date',
        'sent_at',
        'status',
        'notification_type',
        'message',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function serviceLog(): BelongsTo
    {
        return $this->belongsTo(ServiceLog::class);
    }
}
