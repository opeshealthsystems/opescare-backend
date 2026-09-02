<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DataExportRequestResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'requested_by_user_id' => $this->requested_by_user_id,
            'export_type' => $this->export_type,
            'scope_json' => $this->scope_json,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'file_path' => $this->file_path,
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
