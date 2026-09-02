<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\ClinicalNote. Mirrors the previous inline
 * EncounterController::serializeNote() exactly — no contract change — now
 * reusable and centralised.
 */
class ClinicalNoteResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'visit_id'                   => $this->visit_id,
            'provider_id'                => $this->provider_id,
            'status'                     => $this->status,
            'history_of_present_illness' => $this->history_of_present_illness,
            'examination_findings'       => $this->examination_findings,
            'treatment_plan'             => $this->treatment_plan,
            'signed_at'                  => $this->signed_at?->toISOString(),
            'amends_note_id'             => $this->amends_note_id ?? null,
            'created_at'                 => $this->created_at?->toISOString(),
        ];
    }
}
