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
        'branch_id',
        'service_date',
        'odometer_reading',
        'next_service_km',
        'avg_monthly_km',
        'service_type',
        'cost',
        'labor_cost',
        'payment_type',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'is_consignment',
        'consignment_percentage',
        'discount_amount',
        'discount_percentage',
        'total_amount',
        'currency',
        'notes',
        'manual_items',
    ];

    protected $casts = [
        'service_date' => 'date',
        'odometer_reading' => 'integer',
        'next_service_km' => 'integer',
        'avg_monthly_km' => 'integer',
        'cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'is_consignment' => 'boolean',
        'consignment_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'manual_items' => 'array',
    ];

    // Relationships
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Helper methods for payments
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function isCash(): bool
    {
        return $this->payment_type === 'cash';
    }

    public function isCredit(): bool
    {
        return $this->payment_type === 'credit';
    }

    public function isInstallment(): bool
    {
        return $this->payment_type === 'installment';
    }

    public function addPayment(float $amount, string $method = 'cash', string $notes = null): Payment
    {
        $payment = $this->payments()->create([
            'workshop_id' => $this->vehicle->client->workshop_id,
            'branch_id' => $this->branch_id,
            'amount' => $amount,
            'currency' => $this->currency,
            'payment_date' => now(),
            'payment_method' => $method,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        // Update payment status
        $this->paid_amount += $amount;
        $this->remaining_amount -= $amount;

        if ($this->remaining_amount <= 0) {
            $this->payment_status = 'paid';
            $this->remaining_amount = 0;
        } else {
            $this->payment_status = 'partial';
        }

        $this->save();

        return $payment;
    }

    public function calculateTotal(): void
    {
        $productsCost = $this->products->sum(function ($product) {
            return $product->pivot->total_price;
        });

        $subtotal = $productsCost + $this->labor_cost;
        $discountAmount = $this->discount_percentage > 0
            ? ($subtotal * $this->discount_percentage / 100)
            : $this->discount_amount;

        $this->total_amount = $subtotal - $discountAmount;
        $this->cost = $productsCost;
        $this->remaining_amount = $this->total_amount - $this->paid_amount;

        $this->save();
    }
}
