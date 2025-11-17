<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'unit' => $this->unit,
            'purchase_price' => $this->purchase_price,
            'selling_price' => $this->selling_price,
            'stock_quantity' => $this->stock_quantity,
            'min_stock_level' => $this->min_stock_level,
            'barcode' => $this->barcode,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'track_inventory' => $this->track_inventory,
            'is_low_stock' => $this->isLowStock(),
            'profit' => $this->getProfit(),
            'profit_margin' => round($this->getProfitMargin(), 2),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'inventory_transactions' => InventoryTransactionResource::collection($this->whenLoaded('inventoryTransactions')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
