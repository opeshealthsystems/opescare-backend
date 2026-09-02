<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SubmissionProfileResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'report_type_id' => $this->report_type_id,
            'destination_type' => $this->destination_type,
            'endpoint_url' => $this->endpoint_url,
            'auth_method' => $this->auth_method,
            'payload_format' => $this->payload_format,
            'mapping_rules_json' => $this->mapping_rules_json,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
