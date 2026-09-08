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
        'name',
        'sku',
        'description',
        'unit',
        'barcode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function globalCategory(): BelongsTo
    {
        return $this->belongsTo(GlobalCategory::class);
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
