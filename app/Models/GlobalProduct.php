<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalProduct extends Model
{
    protected $fillable = [
        'global_category_id',
        'brand_id',
        'name',
        'sku',
        'description',
        'unit',
        'barcode',
        'is_active',
        'product_type',
        'viscosity',
        'oil_base',
        'api_spec',
        'volume_liters',
        'pack_qty',
        'supplier_code',
        'source',
        'image_url',
        'recommended_price',
        'currency',
        'price_updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'volume_liters' => 'decimal:3',
        'recommended_price' => 'decimal:2',
        'price_updated_at' => 'datetime',
    ];

    public function globalCategory(): BelongsTo
    {
        return $this->belongsTo(GlobalCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_global_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
