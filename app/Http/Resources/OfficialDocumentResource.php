<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OfficialDocumentResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'document_type'    => $this->document_type,
            'document_number'  => $this->document_number,
            'patient_id'       => $this->patient_id,
            'health_id'        => $this->health_id,
            'facility_id'      => $this->facility_id,
            'organization_id'  => $this->organization_id,
            'issuer_user_id'   => $this->issuer_user_id,
            'template_id'      => $this->template_id,
            'template_version' => $this->template_version,
            'status'           => $this->status,
            'version'          => $this->version,
            'sensitivity_level' => $this->sensitivity_level,
            'title'            => $this->title,
            'payload_json'     => $this->payload_json,
            'standard_mapping_json' => $this->standard_mapping_json,
            'pdf_path'         => $this->pdf_path,
            'issued_at'        => $this->issued_at?->toISOString(),
            'released_at'      => $this->released_at?->toISOString(),
            'expires_at'       => $this->expires_at?->toISOString(),
            'revoked_at'       => $this->revoked_at?->toISOString(),
            'revocation_reason' => $this->revocation_reason,
            'is_demo'          => (bool) $this->is_demo,
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
