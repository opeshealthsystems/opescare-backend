<?php

namespace App\Services\Fhir;

use App\Exceptions\FhirIngestRefusal;
use App\Models\ConsentGrant;
use App\Models\FhirIngestEvent;
use App\Models\Patient;
use App\Support\Fhir\FhirOutcome;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The three gates every partner write passes before anything is stored.
 *
 *   1. WHO IS WRITING     — the facility comes from the verified token and
 *                            from nowhere else.
 *   2. WHO IT IS ABOUT    — an EXISTING Health ID, resolved exactly.
 *                            A partner may never create one.
 *   3. MAY THEY           — an active ConsentGrant from that patient to that
 *                            facility. No grant, no write.
 *
 * Each gate throws FhirIngestRefusal, which unwinds any surrounding
 * transaction and is rendered as an OperationOutcome by the controller.
 */
class FhirIngestGuard
{
    /**
     * Identifier systems this server accepts for an OpesCare Health ID.
     *
     * A closed list on purpose. Accepting "any system whose value happens to
     * resolve" turns every identifier namespace in the world into a second
     * door onto the patient register.
     */
    public const HEALTH_ID_SYSTEMS = [
        'https://opescare.com/fhir/health-id',
        'https://opescare.cloud/fhir/health-id',
        'https://opescare.com/fhir/sid/health-id',   // FHIR export identifier (current)
        'https://opescare.cloud/fhir/sid/health-id', // FHIR export identifier (pre-2026-10-18)
        'urn:oid:2.16.120.1.1.1',                 // OpesCare Health ID OID
    ];

    /**
     * The facility this write is attributed to.
     *
     * $request->attributes is populated by VerifyBearerToken from the signed
     * JWT. Nothing else is consulted: not a header, not the request body, not
     * an Organization reference inside the posted resource, and never "the
     * one facility this client happens to have". A partner that can name its
     * own facility can write into any facility's record.
     *
     * @throws FhirIngestRefusal
     */
    public function facilityId(Request $request): string
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! is_string($facilityId) || ! Str::isUuid($facilityId)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::noFacilityInToken(),
                'no_facility_in_token',
                'token carried no usable facility_id'
            );
        }

        return $facilityId;
    }

    public function clientId(Request $request): string
    {
        return (string) ($request->attributes->get('integration_client_id') ?: 'unknown_client');
    }

    /** @return string[] */
    public function scopes(Request $request): array
    {
        return (array) $request->attributes->get('bearer_scopes', []);
    }

    /**
     * Enforce a SMART-on-FHIR scope inside the controller.
     *
     * Route middleware covers the single-resource endpoints. The transaction
     * Bundle cannot: one Bundle carries several resource types, so the scope
     * for each entry has to be checked as the entry is read.
     *
     * @throws FhirIngestRefusal
     */
    public function requireScope(Request $request, string $scope): void
    {
        $held = $this->scopes($request);

        if (in_array('*', $held, true) || in_array($scope, $held, true)) {
            return;
        }

        throw FhirIngestRefusal::make(
            FhirOutcome::insufficientScope($scope),
            'insufficient_scope',
            'missing scope ' . $scope
        );
    }

    /**
     * Resolve the patient this resource is about.
     *
     * ATTACH-ONLY. There is no create, no upsert, no "create if missing" and
     * no side effect anywhere on this path that mints an identity. A partner
     * supplies a Health ID that already exists; if it does not resolve, the
     * write ends here. Letting an external system mint identities is how a
     * national register fills with duplicates that nobody can later unpick.
     *
     * The lookup is Patient::resolveHealthId(): an exact, uppercased match on
     * `health_id`, falling back to an active alias, then following any merge.
     * No LIKE, no fuzzy match, no name search, nothing that could be walked.
     *
     * `subject.reference` is deliberately NOT accepted. It would be a second
     * door onto the register keyed by an internal UUID, and a partner has no
     * business holding internal UUIDs.
     *
     * @param  array<string,mixed>  $resource
     *
     * @throws FhirIngestRefusal
     */
    public function resolveSubject(array $resource, string $expressionRoot = 'subject'): Patient
    {
        $subject = $resource['subject'] ?? $resource['patient'] ?? null;

        if (! is_array($subject) || ! is_array($subject['identifier'] ?? null)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    'subject.identifier is required and must carry an OpesCare Health ID. This '
                    . 'server does not accept subject.reference: a partner identifies a patient by '
                    . 'Health ID, never by an internal record id.',
                    $expressionRoot . '.identifier'
                ),
                'subject_identifier_missing',
                'subject.identifier absent or not an identifier'
            );
        }

        $system = (string) ($subject['identifier']['system'] ?? '');
        $value  = trim((string) ($subject['identifier']['value'] ?? ''));

        if (! in_array($system, self::HEALTH_ID_SYSTEMS, true)) {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    'subject.identifier.system must be an OpesCare Health ID system. Accepted: '
                    . implode(', ', self::HEALTH_ID_SYSTEMS) . '.',
                    $expressionRoot . '.identifier.system'
                ),
                'subject_identifier_system_unknown',
                'unaccepted identifier system: ' . $system
            );
        }

        if ($value === '') {
            throw FhirIngestRefusal::make(
                FhirOutcome::structure(
                    'subject.identifier.value must carry a Health ID.',
                    $expressionRoot . '.identifier.value'
                ),
                'subject_identifier_missing',
                'empty Health ID value'
            );
        }

        $patient = Patient::resolveHealthId($value);

        if ($patient === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::unknownPatient(),
                'patient_unresolvable',
                'Health ID did not resolve'
            );
        }

        return $patient;
    }

    /**
     * The patient must have granted this facility an active consent.
     *
     * Read the same way the platform's own reads are gated
     * (App\Http\Middleware\RequireConsentGrant): active status, not expired,
     * issued to THIS facility. Global scopes are dropped because the
     * IsDemoRecord scope would otherwise hide a real grant from a demo
     * context and a demo grant from a real one.
     *
     * The grant is resolved from (patient, facility) rather than from a grant
     * id the partner names in a header. A partner naming its own grant is a
     * partner choosing its own permission.
     *
     * @throws FhirIngestRefusal
     */
    public function requireConsent(Patient $patient, string $facilityId): ConsentGrant
    {
        $grant = ConsentGrant::withoutGlobalScopes()
            ->where('patient_id', $patient->id)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->orderByDesc('expires_at')
            ->first();

        if ($grant === null) {
            throw FhirIngestRefusal::make(
                FhirOutcome::consentRequired(),
                'consent_required',
                'no active consent grant from patient to facility ' . $facilityId
            );
        }

        return $grant;
    }

    // -- Audit -------------------------------------------------------------

    public function recordAccepted(
        Request $request,
        string $resourceType,
        Patient $patient,
        string $createdResourceId,
        ?string $idempotencyKey,
    ): void {
        $this->record($request, $resourceType, self::acceptedRow($patient, $createdResourceId), [
            'outcome'         => FhirIngestEvent::OUTCOME_ACCEPTED,
            'http_status'     => 201,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function recordRefusal(
        Request $request,
        string $resourceType,
        FhirIngestRefusal $refusal,
        ?Patient $patient,
        ?string $submittedIdentifier,
        ?string $idempotencyKey,
    ): void {
        $this->record($request, $resourceType, [
            'patient_id'              => $patient?->id,
            'created_resource_id'     => null,
            'subject_identifier_hash' => FhirIngestEvent::hashIdentifier($submittedIdentifier),
        ], [
            'outcome'         => FhirIngestEvent::OUTCOME_REFUSED,
            'refusal_code'    => $refusal->refusalCode,
            'refusal_detail'  => $refusal->refusalDetail,
            'http_status'     => $refusal->status(),
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private static function acceptedRow(Patient $patient, string $createdResourceId): array
    {
        return [
            'patient_id'              => $patient->id,
            'created_resource_id'     => $createdResourceId,
            'subject_identifier_hash' => FhirIngestEvent::hashIdentifier($patient->health_id),
        ];
    }

    /**
     * Write the audit line.
     *
     * Wrapped: an audit-store failure must not turn an accepted clinical
     * write into a 500 the partner will retry, nor a refusal into a success.
     * It is logged loudly instead.
     */
    private function record(Request $request, string $resourceType, array $subject, array $outcome): void
    {
        try {
            FhirIngestEvent::create(array_merge($subject, $outcome, [
                'integration_client_id' => $this->clientId($request),
                'facility_id'           => $request->attributes->get('facility_id'),
                'resource_type'         => $resourceType,
                'created_at'            => now(),
            ]));
        } catch (\Throwable $e) {
            Log::error('fhir_ingest_audit_write_failed', [
                'resource_type' => $resourceType,
                'outcome'       => $outcome['outcome'] ?? null,
                'refusal_code'  => $outcome['refusal_code'] ?? null,
                'exception'     => $e->getMessage(),
            ]);
        }
    }
}
