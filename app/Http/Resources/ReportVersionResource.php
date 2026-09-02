<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReportVersionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'version_number' => $this->version_number,
            'payload_json' => $this->payload_json,
            'change_reason' => $this->change_reason,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
