<?php

namespace App\Http\Resources;

use App\Support\PatientVocabulary;
use Illuminate\Http\Request;

/**
 * A consent request as the PATIENT reads it — the decision screen for
 * "shall I open my medical record to this facility?".
 *
 * Served only by MobileGovernanceController::listConsentRequests(). The
 * facility's own outbox has a separate shape, built inside
 * MobileConsentRequestController, and that one already carried labels. This
 * side carried only codes, so the patient's Privacy screen printed the wire
 * values — `Patient.summary`, `Allergies.read`, `1440 min` — where the
 * clinician who raised the request had picked "Record summary", "Allergies"
 * and "24 hours" from a labelled list.
 *
 * The `*_label` fields below close that. They are resolved by
 * App\Support\PatientVocabulary out of the SAME lang files and the SAME
 * allow-lists the clinician's picker is built from, in the patient's language,
 * so there is one vocabulary rather than two that drift.
 *
 * The raw codes stay on the wire beside the labels: they are what approve and
 * deny act on, and a value the audit trail records is never replaced by its
 * rendering.
 */
class ConsentRequestResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $locale  = PatientVocabulary::locale($request);
        $scopes  = PatientVocabulary::scopes($this->requested_scope, $locale);
        $purpose = PatientVocabulary::purpose($this->purpose, $locale);
        $minutes = $this->duration_minutes;

        return [
            'id'                       => $this->id,
            'patient_id'               => $this->patient_id,
            'requesting_facility_id'   => $this->requesting_facility_id,
            'requesting_facility_name' => $this->whenLoaded('requestingFacility', fn () => $this->requestingFacility?->name),
            'requesting_facility_type' => $this->whenLoaded('requestingFacility', fn () => $this->requestingFacility?->type),
            'requesting_user_id'       => $this->requesting_user_id,
            'purpose'                  => $this->purpose,
            'requested_scope'          => $this->requested_scope,
            'duration_minutes'         => $this->duration_minutes,
            'status'                   => $this->status,

            // ── The words the clinician chose, in the patient's language ──
            //
            // `recognised: false` means OpesCare has no name for that code: the
            // label IS the code, unchanged, and the note beside it says so.
            // Nothing here is ever guessed from the shape of a string.
            'purpose_label'      => $purpose['label'] ?? null,
            'purpose_recognised' => $purpose['recognised'] ?? null,
            'purpose_note'       => $purpose['note'] ?? null,
            'scope_items'        => $scopes['items'],
            'scope_note'         => $scopes['note'],
            'duration_label'     => PatientVocabulary::duration(
                $minutes === null ? null : (int) $minutes,
                $locale
            )['label'] ?? null,

            // The active grant this request produced, if approved — the id a
            // patient-initiated revoke (POST /mobile/consents/{id}/revoke) targets.
            'grant_id'                 => $this->whenLoaded('grant', fn () => $this->grant?->id),
            'grant_status'             => $this->whenLoaded('grant', fn () => $this->grant?->status),
            'grant_expires_at'         => $this->whenLoaded('grant', fn () => $this->grant?->expires_at?->toISOString()),
            'created_at'               => $this->created_at?->toISOString(),
            'updated_at'               => $this->updated_at?->toISOString(),
        ];
    }
}
