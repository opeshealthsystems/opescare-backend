<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NotificationDeliveryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'notification_event_id' => $this->notification_event_id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'provider' => $this->provider,
            'status' => $this->status,
            'attempt_count' => $this->attempt_count,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
