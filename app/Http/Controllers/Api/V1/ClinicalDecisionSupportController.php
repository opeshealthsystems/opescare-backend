<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/**
 * ClinicalDecisionSupportController — PLACEHOLDER. Not an implementation.
 *
 * CDSS (drug-interaction, allergy and lab-rule alerting) is frozen out of V1
 * behind the `clinical_decision_support` feature flag. The real controller was
 * deleted when the module was frozen; its route group in the SEALED
 * routes/api.php (api/v1/cdss/*) could not be, so this class exists purely so
 * that route table can be built. See FrozenModulePlaceholderController for the
 * full rationale.
 *
 * The honesty rule matters more here than anywhere else in this trio. A CDSS
 * stub that answered 200 with an empty alert list would tell a prescribing
 * clinician "no interactions found" for a drug pair nothing ever checked. That
 * is a patient-safety failure wearing a success code. Every method 404s;
 * nothing here evaluates a rule or clears an alert.
 *
 * Frozen surface (10 routes, routes/api.php lines 938-951):
 *   POST  api/v1/cdss/run
 *   GET   api/v1/cdss/visits/{visitId}/alerts
 *   GET   api/v1/cdss/patients/{patientId}/alerts
 *   POST  api/v1/cdss/alerts/{alertId}/acknowledge
 *   POST  api/v1/cdss/alerts/{alertId}/override
 *   POST  api/v1/cdss/alerts/{alertId}/dismiss
 *   GET   api/v1/cdss/facilities/{facilityId}/summary
 *   POST  api/v1/cdss/overrides
 *   GET   api/v1/cdss/overrides/high-risk
 *   POST  api/v1/cdss/overrides/{overrideId}/qa-review
 *
 * @see \App\Http\Controllers\Api\V1\FrozenModulePlaceholderController
 */
class ClinicalDecisionSupportController extends FrozenModulePlaceholderController
{
    protected function featureKey(): string
    {
        return 'clinical_decision_support';
    }

    protected function moduleLabel(): string
    {
        return 'clinical decision support';
    }

    public function run(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function visitAlerts(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function patientAlerts(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function acknowledge(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function override(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function dismiss(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function facilitySummary(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function recordOverride(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function highRiskOverridesPendingReview(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function qaReviewOverride(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }
}
