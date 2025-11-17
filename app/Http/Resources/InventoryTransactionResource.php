<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
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
            'product_id' => $this->product_id,
            'service_log_id' => $this->service_log_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'service_log' => new ServiceLogResource($this->whenLoaded('serviceLog')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
