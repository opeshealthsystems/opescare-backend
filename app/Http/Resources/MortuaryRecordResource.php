<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MortuaryRecordResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'facility_id'        => $this->facility_id,
            'patient_id'         => $this->patient_id,
            'body_number'        => $this->body_number,
            'full_name'          => $this->full_name,
            'sex'                => $this->sex,
            'approximate_age'    => $this->approximate_age,
            'cause_of_death'     => $this->cause_of_death,
            'death_date'         => $this->death_date?->toISOString(),
            'admission_date'     => $this->admission_date?->toISOString(),
            'admitted_by'        => $this->admitted_by,
            'storage_location'   => $this->storage_location,
            'status'             => $this->status,
            'identified_by'      => $this->identified_by,
            'identified_at'      => $this->identified_at?->toISOString(),
            'released_at'        => $this->released_at?->toISOString(),
            'released_to'        => $this->released_to,
            'released_by'        => $this->released_by,
            'next_of_kin_name'   => $this->next_of_kin_name,
            'next_of_kin_contact' => $this->next_of_kin_contact,
            'notes'              => $this->notes,
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
