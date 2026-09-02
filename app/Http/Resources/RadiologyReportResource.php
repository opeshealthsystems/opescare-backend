<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RadiologyReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'patient_id'         => $this->patient_id,
            'facility_id'        => $this->facility_id,
            'imaging_order_id'   => $this->imaging_order_id,
            'dicom_study_id'     => $this->dicom_study_id,
            'ordered_by'         => $this->ordered_by,
            'reported_by'        => $this->reported_by,
            'modality'           => $this->modality,
            'body_part'          => $this->body_part,
            'study_date'         => $this->study_date?->toISOString(),
            'clinical_indication' => $this->clinical_indication,
            'findings'           => $this->findings,
            'impression'         => $this->impression,
            'recommendation'     => $this->recommendation,
            'status'             => $this->status,
            'finalized_at'       => $this->finalized_at?->toISOString(),
            'amended_at'         => $this->amended_at?->toISOString(),
            'amendment_reason'   => $this->amendment_reason,
            'distributed_to'     => $this->distributed_to,
            'distributed_at'     => $this->distributed_at?->toISOString(),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),

            // Relations are emitted only when the controller eager-loaded them,
            // preserving the pre-resource wire shape of show()/pending().
            'patient'            => $this->whenLoaded('patient'),
            'facility'           => $this->whenLoaded('facility'),
            'orderedBy'          => $this->whenLoaded('orderedBy'),
            'reportedBy'         => $this->whenLoaded('reportedBy'),
        ];
    }
}
