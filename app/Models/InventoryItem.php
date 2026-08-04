<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
    ];

    protected $appends = ['difference'];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDifferenceAttribute(): ?float
    {
        if ($this->counted_quantity === null) {
            return null;
        }

        return (float) $this->counted_quantity - (float) $this->system_quantity;
    }
}
