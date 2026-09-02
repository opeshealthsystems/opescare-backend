<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CountryPolicyResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_code' => $this->country_code,
            'name' => $this->name,
            'version' => $this->version,
            'effective_from' => $this->effective_from?->toISOString(),
            'effective_to' => $this->effective_to?->toISOString(),
            'settings_json' => $this->settings_json,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
