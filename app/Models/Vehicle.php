<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'client_id',
        'make',
        'model',
        'year',
        'plate_number',
        'vin',
        'avg_monthly_km',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }

    // Helper method - Oxirgi servis yozuvi
    public function latestService()
    {
        return $this->hasOne(ServiceLog::class)->latestOfMany('service_date');
    }
}
