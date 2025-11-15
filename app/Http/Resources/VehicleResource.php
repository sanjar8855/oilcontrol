<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'client_id' => $this->client_id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'plate_number' => $this->plate_number,
            'vin' => $this->vin,
            'client' => new ClientResource($this->whenLoaded('client')),
            'service_logs' => ServiceLogResource::collection($this->whenLoaded('serviceLogs')),
            'latest_service' => new ServiceLogResource($this->whenLoaded('latestService')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
