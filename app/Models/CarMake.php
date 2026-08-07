<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarMake extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    /**
     * Multiselect uchun guruhlangan options ({label, options: [{value, label}]}).
     */
    public static function optionGroups(): array
    {
        return static::query()
            ->with(['carModels' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CarMake $make) => [
                'label' => $make->name,
                'options' => $make->carModels->map(fn (CarModel $model) => [
                    'value' => $model->name,
                    'label' => $model->name,
                ])->all(),
            ])
            ->all();
    }
}
