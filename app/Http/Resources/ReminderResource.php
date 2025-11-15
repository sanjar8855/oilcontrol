<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
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
            'service_log_id' => $this->service_log_id,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'status' => $this->status,
            'notification_type' => $this->notification_type,
            'message' => $this->message,
            'service_log' => new ServiceLogResource($this->whenLoaded('serviceLog')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
