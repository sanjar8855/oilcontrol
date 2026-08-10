<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'workshop_id',
        'name',
        'phone',
        'contact_person',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    /**
     * Valyuta bo'yicha qoldiq qarz: sum(purchase) - sum(payment).
     *
     * @return array<string, float>
     */
    public function getBalances(): array
    {
        $totals = $this->transactions()
            ->selectRaw('currency, type, SUM(amount) as total')
            ->groupBy('currency', 'type')
            ->get();

        $balances = ['UZS' => 0.0, 'USD' => 0.0];

        foreach ($totals as $row) {
            $sign = $row->type === 'purchase' ? 1 : -1;
            $balances[$row->currency] = ($balances[$row->currency] ?? 0.0) + $sign * (float) $row->total;
        }

        return $balances;
    }
}
