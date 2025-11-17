<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'workshop_id',
        'category_id',
        'name',
        'sku',
        'description',
        'unit',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'min_stock_level',
        'barcode',
        'image',
        'is_active',
        'track_inventory',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    // Helper methods
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function getProfit(): float
    {
        return (float) ($this->selling_price - $this->purchase_price);
    }

    public function getProfitMargin(): float
    {
        if ($this->selling_price == 0) {
            return 0;
        }
        return ($this->getProfit() / $this->selling_price) * 100;
    }

    // Relationships
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function serviceLogs(): BelongsToMany
    {
        return $this->belongsToMany(ServiceLog::class, 'service_log_product')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }
}
