<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'workshop_id',
        'branch_id',
        'product_id',
        'service_log_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_price',
        'total_price',
        'reason',
        'notes',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serviceLog(): BelongsTo
    {
        return $this->belongsTo(ServiceLog::class);
    }
}
