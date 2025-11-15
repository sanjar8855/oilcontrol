<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceLogResource extends JsonResource
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
            'vehicle_id' => $this->vehicle_id,
            'service_date' => $this->service_date?->toDateString(),
            'odometer_reading' => $this->odometer_reading,
            'next_service_km' => $this->next_service_km,
            'avg_monthly_km' => $this->avg_monthly_km,
            'service_type' => $this->service_type,
            'cost' => $this->cost,
            'notes' => $this->notes,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
