<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    protected $fillable = [
        'user_id',
        'workshop_id',
        'branch_id',
        'amount',
        'month',
        'payment_date',
        'payment_method',
        'bonus',
        'deduction',
        'notes',
        'paid_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deduction' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Helper methods
    public function getTotalAmount(): float
    {
        return (float) ($this->amount + $this->bonus - $this->deduction);
    }

    public function isCash(): bool
    {
        return $this->payment_method === 'cash';
    }
}
