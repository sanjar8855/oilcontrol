<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CarModel extends Model
{
    protected $fillable = [
        'car_make_id',
        'name',
        'oil_capacity_liters',
        'antifreeze_capacity_min_liters',
        'antifreeze_capacity_max_liters',
    ];

    protected $casts = [
        'oil_capacity_liters' => 'decimal:2',
        'antifreeze_capacity_min_liters' => 'decimal:2',
        'antifreeze_capacity_max_liters' => 'decimal:2',
    ];

    public function carMake(): BelongsTo
    {
        return $this->belongsTo(CarMake::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'car_model_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function globalProducts(): BelongsToMany
    {
        return $this->belongsToMany(GlobalProduct::class, 'car_model_global_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Multiselect uchun guruhlangan options, ID qiymati bilan
     * ({label, options: [{value: id, label}]}) — mahsulotni bog'lash uchun.
     */
    public static function optionGroupsWithId(): array
    {
        return CarMake::query()
            ->with(['carModels' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CarMake $make) => [
                'label' => $make->name,
                'options' => $make->carModels->map(fn (CarModel $model) => [
                    'value' => $model->id,
                    'label' => $model->name,
                ])->all(),
            ])
            ->all();
    }
}
