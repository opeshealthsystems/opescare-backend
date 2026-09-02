<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Wire contract for App\Models\CountryLaunchApproval. */
class CountryLaunchApprovalResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                                => $this->id,
            'country_id'                        => $this->country_id,
            'status'                            => $this->status,
            'checklist_summary'                 => $this->checklist_summary,
            'legal_review_complete'             => (bool) $this->legal_review_complete,
            'health_regulation_review_complete' => (bool) $this->health_regulation_review_complete,
            'language_pack_ready'               => (bool) $this->language_pack_ready,
            'payment_configured'                => (bool) $this->payment_configured,
            'pilot_facility_selected'           => (bool) $this->pilot_facility_selected,
            'data_residency_reviewed'           => (bool) $this->data_residency_reviewed,
            'approved_by'                       => $this->approved_by,
            'approved_at'                       => $this->approved_at?->toISOString(),
            'approval_notes'                    => $this->approval_notes,
            'created_at'                        => $this->created_at?->toISOString(),
        ];
    }
}
