<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WardAdminRecordResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'patient_id'  => $this->patient_id,
            'facility_id' => $this->facility_id,
            'actor_id'    => $this->actor_id,
            'record_type' => $this->record_type,
            'record_date' => $this->record_date?->toISOString(),
            'content'     => $this->content,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
