<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CorrectionRequestResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'patient_id'             => $this->patient_id,
            'requested_by_user_id'   => $this->requested_by_user_id,
            'resource_type'          => $this->resource_type,
            'resource_id'            => $this->resource_id,
            'reason'                 => $this->reason,
            'supporting_document_id' => $this->supporting_document_id,
            'status'                 => $this->status,
            'reviewed_by'            => $this->reviewed_by,
            'reviewed_at'            => $this->reviewed_at?->toISOString(),
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
