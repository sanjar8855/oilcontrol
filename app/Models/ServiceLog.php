<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceLog extends Model
{
    protected $fillable = [
        'vehicle_id',
        'service_date',
        'odometer_reading',
        'next_service_km',
        'avg_monthly_km',
        'service_type',
        'cost',
        'labor_cost',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'odometer_reading' => 'integer',
        'next_service_km' => 'integer',
        'avg_monthly_km' => 'integer',
        'cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
    ];

    // Relationships
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'service_log_product')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }
}
