<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'workshop_id',
        'branch_id',
        'category_id',
        'supplier_id',
        'global_product_id',
        'name',
        'sku',
        'description',
        'unit',
        'currency',
        'purchase_price',
        'selling_price',
        'purchase_price_usd',
        'purchase_price_uzs',
        'selling_price_usd',
        'selling_price_uzs',
        'is_consignment',
        'consignment_percentage',
        'supplier',
        'exchange_rate',
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
        'purchase_price_usd' => 'decimal:2',
        'purchase_price_uzs' => 'decimal:2',
        'selling_price_usd' => 'decimal:2',
        'selling_price_uzs' => 'decimal:2',
        'consignment_percentage' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'is_consignment' => 'boolean',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function globalProduct(): BelongsTo
    {
        return $this->belongsTo(GlobalProduct::class);
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

    public function localCarModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Global katalogga bog'langan bo'lsa moshina mosligi global mahsulotdan
     * meros olinadi; aks holda eski lokal bog'lanish ishlatiladi (fallback,
     * hozircha faqat backfill qilinmagan mahsulotlar uchun qoladi).
     */
    public function effectiveCarModels(): Collection
    {
        if ($this->global_product_id) {
            return $this->globalProduct?->carModels ?? new Collection();
        }

        return $this->localCarModels;
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Helper methods for multi-currency
    public function getPurchasePrice(): float
    {
        if ($this->currency === 'USD') {
            return (float) $this->purchase_price_usd;
        }
        return (float) $this->purchase_price_uzs;
    }

    public function getSellingPrice(): float
    {
        if ($this->currency === 'USD') {
            return (float) $this->selling_price_usd;
        }
        return (float) $this->selling_price_uzs;
    }

    public function getAverageCost(): float
    {
        // FIFO average cost calculation
        $movements = $this->stockMovements()
            ->where('movement_type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($movements->isEmpty()) {
            return $this->getPurchasePrice();
        }

        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($movements as $movement) {
            $cost = $movement->currency === 'USD'
                ? $movement->unit_cost_usd
                : $movement->unit_cost_uzs;

            $totalCost += $cost * $movement->remaining_quantity;
            $totalQuantity += $movement->remaining_quantity;
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }
}
