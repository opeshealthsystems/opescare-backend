<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CarePlanResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'patient_id'  => $this->patient_id,
            'facility_id' => $this->facility_id,
            'created_by'  => $this->created_by,
            'title'       => $this->title,
            'description' => $this->description,
            'start_date'  => $this->start_date?->toISOString(),
            'end_date'    => $this->end_date?->toISOString(),
            'status'      => $this->status,
            'visit_id'    => $this->visit_id,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
