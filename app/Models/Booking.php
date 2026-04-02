<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'client_id',
        'vehicle_id',
        'workshop_id',
        'branch_id',
        'booking_date',
        'booking_time',
        'service_type',
        'status',
        'telegram_user_id',
        'telegram_username',
        'telegram_message_id',
        'client_phone',
        'client_name',
        'notes',
        'service_log_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceLog(): BelongsTo
    {
        return $this->belongsTo(ServiceLog::class);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function confirm(): void
    {
        $this->status = 'confirmed';
        $this->save();
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }
}
