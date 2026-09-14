<?php

namespace App\Services\Chart;

use App\Models\Patient;
use App\Modules\Governance\Services\AccessLogService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Support\Facades\Log;

/**
 * The web chart's door: who may open THIS patient's record, on what basis,
 * and the trail that proves it.
 *
 * The rule itself is App\Support\ClinicalRelationshipGate — the same class the
 * clinician API's chart, messaging, lab-order and pre-consult surfaces ask.
 * Nothing here re-implements it; this class only adapts the verdict to the
 * portal's conventions and writes the audit rows exactly the way the API chart
 * does, so a reviewer reading `access_logs` cannot tell which door was used.
 *
 * WHAT THE GATE ACTUALLY DECIDES (read before assuming facility ownership):
 *
 *   The chart is patient-centred. A Health ID is portable, so the gate never
 *   asks "does this facility own the record" — it asks on what BASIS this
 *   clinician stands in a care relationship with this patient: their own
 *   appointment, a current care-team membership, a clinical act they
 *   personally performed, a live episode at the ACTIVE facility, or an
 *   active consent grant to the ACTIVE facility. Two of the five bases use the
 *   facility; three follow the clinician across facilities. A patient seen only
 *   at another hospital, by other clinicians, with no grant here, has no basis
 *   and reads as not found.
 *
 *   The patient's privacy switch (`require_consent_for_full_record`, default
 *   ON) is applied INSIDE the gate: an implicit basis then opens the preview
 *   scope — identity, allergies, current medicines — and every other section
 *   waits for a consent grant. The second switch, `emergency_access_allowed`,
 *   gates break-glass, which this chart deliberately has no path to.
 *
 * REFUSAL SHAPES ON THE WEB:
 *
 *   no basis          → 404. The API answers 403 because its client needs the
 *                       `required_action`; a browser screen must not confirm
 *                       that a record exists. The refusal is still logged
 *                       (access_type `denied`), best-effort, as the gate's
 *                       other callers do.
 *   preview only      → the summary renders; every other tab renders a
 *                       "consent required" notice and logs a denial.
 *   audit unwritable  → 503. No trail, no disclosure.
 *
 * `$clinicianId` and `$facilityId` come from the controller, which reads them
 * from the session user and PortalContextService — never from the request.
 */
final class PatientChartAccess
{
    /** The sections a preview-scoped verdict may open. Mirrors the API chart. */
    public const PREVIEW_CATEGORIES = ['patient_identity', 'allergies', 'medications'];

    public function __construct(
        public readonly Patient $patient,
        public readonly ClinicalRelationshipGate $verdict,
        private readonly ?string $clinicianId,
        private readonly ?string $facilityId,
    ) {
    }

    public static function evaluate(Patient $patient, ?string $clinicianId, ?string $facilityId): self
    {
        return new self(
            $patient,
            ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patient->id),
            $clinicianId,
            $facilityId,
        );
    }

    public function holds(): bool
    {
        return $this->verdict->holds();
    }

    /** May this session read the named data category? */
    public function covers(string $dataCategory): bool
    {
        if (! $this->verdict->holds()) {
            return false;
        }

        return $this->verdict->coversFullRecord()
            || in_array($dataCategory, self::PREVIEW_CATEGORIES, true);
    }

    /**
     * Record a disclosure. False when it could not be recorded — the caller
     * must then refuse the read rather than serve it untracked.
     */
    public function auditRead(string $dataCategory, string $resourceType): bool
    {
        if (! $this->clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $this->patient->id,
                actorId: $this->clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $this->facilityId,
                purpose: $this->verdict->purpose(),
                dataCategory: $dataCategory,
                resourceType: $resourceType,
                resourceId: null,
                accessType: 'read',
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Web chart read refused: access log could not be written.', [
                'patient_id'    => $this->patient->id,
                'clinician_id'  => $this->clinicianId,
                'data_category' => $dataCategory,
                'error'         => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Record a refusal. Best-effort: a refusal disclosed nothing. */
    public function auditDenial(string $dataCategory, string $resourceType): void
    {
        if (! $this->clinicianId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $this->patient->id,
                actorId: $this->clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $this->facilityId,
                purpose: ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE,
                dataCategory: $dataCategory,
                resourceType: $resourceType,
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Web chart denial was not access-logged.', [
                'patient_id'   => $this->patient->id,
                'clinician_id' => $this->clinicianId,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
