<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReportTypeResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'code'                    => $this->code,
            'name'                    => $this->name,
            'description'             => $this->description,
            'sensitivity_level'       => $this->sensitivity_level,
            'default_review_required' => (bool) $this->default_review_required,
            'is_active'               => (bool) $this->is_active,
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
