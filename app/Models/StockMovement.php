<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'workshop_id',
        'branch_id',
        'movement_type',
        'quantity',
        'remaining_quantity',
        'unit_cost_usd',
        'unit_cost_uzs',
        'currency',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'unit_cost_usd' => 'decimal:2',
        'unit_cost_uzs' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isFullyConsumed(): bool
    {
        return $this->remaining_quantity <= 0;
    }

    public function canConsume(float $quantity): bool
    {
        return $this->remaining_quantity >= $quantity;
    }

    public function consume(float $quantity): void
    {
        $this->remaining_quantity -= $quantity;
        $this->save();
    }
}
