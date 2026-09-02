<?php

namespace App\Http\Controllers\Api\Fhir;

use App\Http\Controllers\Controller;
use App\Jobs\FhirBulkExportJob;
use App\Jobs\VerifySubscriptionEndpointJob;
use App\Models\AllergyRecord;
use App\Models\BulkExportJob;
use App\Models\ConsentGrant;
use App\Models\Diagnosis;
use App\Models\Facility;
use App\Models\FhirSubscription;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Modules\Fhir\Mappers\FhirDiagnosticReportMapper;
use App\Modules\Fhir\Mappers\FhirEncounterMapper;
use App\Modules\Fhir\Mappers\FhirMedicationRequestMapper;
use App\Modules\Fhir\Services\FhirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * FHIR R4 REST API Controller
 *
 * Read-only FHIR R4-compliant endpoints for healthcare data interoperability.
 * All responses use Content-Type: application/fhir+json.
 *
 * Security hardening (audit sprint — ISO 27799 §7.3, OWASP API1, HL7 SMART on FHIR):
 *
 *   [IDOR fix] patient(), patientEverything(), patientBulkExport():
 *     Now require the requesting facility to have an active ConsentGrant for
 *     the target patient. Without consent, a 403 OperationOutcome is returned.
 *     Previously, any authenticated bearer could read any patient record — a
 *     catastrophic cross-facility IDOR (OWASP API1 / ISO 27001 A.9.4).
 *
 *   [IDOR fix] searchPatient():
 *     LIKE-based surname/given name searches are now rejected with 400 — they
 *     allowed full patient enumeration via wildcard substring queries. Only
 *     exact `identifier` (health_id) lookups are permitted, and consent is
 *     verified before the patient is included in the result bundle.
 *
 *   [IDOR fix] bulkExport():
 *     $facilityId is now read from auth middleware attributes (not the
 *     unverified X-Facility-Id header) and applied to scope the export via
 *     ConsentGrant. Previously the header was read but silently ignored —
 *     ALL patients were exported to ANY authenticated client.
 *
 *   [Header spoofing fix] subscriptionCreate():
 *     facility_id now comes from VerifyBearerToken middleware attributes,
 *     not the unverified X-Facility-Id request header.
 *
 *   [Ownership fix] subscriptionDelete():
 *     Only the facility that created a subscription may delete it.
 *
 *   Bulk export limit: 5,000 patients max per request to prevent OOM/timeout.
 *     Production-scale export should use async queue-based $export.
 */
class FhirController extends Controller
{
    /** Maximum patients returned by system-level $export (synchronous). */
    private const BULK_EXPORT_LIMIT = 5000;

    public function __construct(private readonly FhirService $fhirService) {}

    // =========================================================================
    // CapabilityStatement
    // =========================================================================

    /**
     * GET /api/fhir/R4/metadata  — public per FHIR spec
     */
    public function metadata(): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->capabilityStatement());
    }

    // =========================================================================
    // Patient
    // =========================================================================

    /**
     * GET /api/fhir/R4/Patient/{id}
     *
     * Requires active ConsentGrant from the requesting facility (ISO 27799 §7.3).
     */
    public function patient(Request $request, string $id): JsonResponse
    {
        $patient    = Patient::where('id', $id)->orWhere('health_id', $id)->firstOrFail();
        $facilityId = $this->resolveFacilityId($request);

        if (! $this->hasConsent($patient->id, $facilityId)) {
            return $this->fhirDenied(
                "No active consent grant for facility {$facilityId} to access patient {$patient->health_id}."
            );
        }

        return $this->fhirResponse($this->fhirService->patient($patient));
    }

    /**
     * GET /api/fhir/R4/Patient
     *
     * Search by `identifier` (exact health_id match) only.
     * LIKE-based `family`/`given` searches are rejected — they allow
     * full-table patient enumeration (OWASP API1).
     */
    public function searchPatient(Request $request): JsonResponse
    {
        $identifier = $request->query('identifier');
        $family     = $request->query('family');
        $given      = $request->query('given');
        $facilityId = $this->resolveFacilityId($request);

        // Reject LIKE-based name searches — patient enumeration attack vector
        if ($family !== null || $given !== null) {
            return $this->fhirResponse([
                'resourceType' => 'OperationOutcome',
                'issue' => [[
                    'severity' => 'error',
                    'code'     => 'not-supported',
                    'details'  => [
                        'text' => 'Search by family/given name is not supported on this server. '
                            . 'Use the identifier parameter with a Health ID for exact lookup. '
                            . 'OWASP API1: wildcard name search would allow patient enumeration.',
                    ],
                ]],
            ], 400);
        }

        if (! $identifier) {
            return $this->fhirResponse([
                'resourceType' => 'OperationOutcome',
                'issue' => [[
                    'severity' => 'error',
                    'code'     => 'required',
                    'details'  => ['text' => 'The identifier search parameter is required.'],
                ]],
            ], 400);
        }

        $patient = Patient::where('health_id', $identifier)->first();

        if (! $patient || ! $this->hasConsent($patient->id, $facilityId)) {
            // Return empty bundle — do not leak whether the patient exists
            return $this->fhirResponse([
                'resourceType' => 'Bundle',
                'type'         => 'searchset',
                'total'        => 0,
                'entry'        => [],
            ]);
        }

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => 1,
            'entry'        => [[
                'fullUrl'  => url('/api/fhir/R4/Patient/' . $patient->id),
                'resource' => $this->fhirService->patient($patient),
            ]],
        ]);
    }

    /**
     * GET /api/fhir/R4/Patient/{id}/$everything
     *
     * Returns all clinical resources for a patient.
     * Requires active ConsentGrant (consent.grant middleware also applied at route level).
     */
    public function patientEverything(Request $request, string $id): JsonResponse
    {
        $patient    = Patient::where('id', $id)->orWhere('health_id', $id)->firstOrFail();
        $facilityId = $this->resolveFacilityId($request);

        if (! $this->hasConsent($patient->id, $facilityId)) {
            return $this->fhirDenied(
                "No active consent grant for facility {$facilityId} to access patient {$patient->health_id}."
            );
        }

        return $this->fhirResponse($this->fhirService->patientBundle($patient));
    }

    // =========================================================================
    // Encounter
    // =========================================================================

    /** GET /api/fhir/R4/Encounter/{id} */
    public function encounter(string $id): JsonResponse
    {
        $visit = Visit::with(['facility', 'provider'])->findOrFail($id);
        return $this->fhirResponse($this->fhirService->encounter($visit));
    }

    /** GET /api/fhir/R4/Encounter?patient={patientId} */
    public function searchEncounter(Request $request): JsonResponse
    {
        $query = Visit::query()->with(['facility', 'provider'])->latest()->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }

        $mapper  = app(FhirEncounterMapper::class);
        $entries = $query->get()->map(fn ($v) => [
            'fullUrl'  => url('/api/fhir/R4/Encounter/' . $v->id),
            'resource' => $mapper->toFhir($v),
        ])->values()->all();

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // DiagnosticReport
    // =========================================================================

    /** GET /api/fhir/R4/DiagnosticReport/{id} */
    public function diagnosticReport(string $id): JsonResponse
    {
        $order = LabOrder::with('results')->findOrFail($id);
        return $this->fhirResponse($this->fhirService->diagnosticReport($order));
    }

    /** GET /api/fhir/R4/DiagnosticReport?patient={}&status={} */
    public function searchDiagnosticReport(Request $request): JsonResponse
    {
        $query = LabOrder::with('results')->latest('ordered_at')->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $mapper  = app(FhirDiagnosticReportMapper::class);
        $entries = $query->get()->map(fn ($o) => [
            'fullUrl'  => url('/api/fhir/R4/DiagnosticReport/' . $o->id),
            'resource' => $mapper->toFhir($o),
        ])->values()->all();

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // MedicationRequest
    // =========================================================================

    /** GET /api/fhir/R4/MedicationRequest/{id} */
    public function medicationRequest(string $id): JsonResponse
    {
        $item   = \App\Models\PrescriptionItem::with('prescription')->findOrFail($id);
        $mapper = app(FhirMedicationRequestMapper::class);
        return $this->fhirResponse($mapper->itemToFhir($item, $item->prescription));
    }

    /** GET /api/fhir/R4/MedicationRequest?patient={} */
    public function searchMedicationRequest(Request $request): JsonResponse
    {
        $query = Prescription::with('items')->latest('prescribed_at')->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }

        $mapper  = app(FhirMedicationRequestMapper::class);
        $entries = [];

        foreach ($query->get() as $prescription) {
            foreach ($mapper->prescriptionToBundle($prescription) as $med) {
                $entries[] = [
                    'fullUrl'  => url('/api/fhir/R4/MedicationRequest/' . $med['id']),
                    'resource' => $med,
                ];
            }
        }

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // Practitioner
    // =========================================================================

    /** GET /api/fhir/R4/Practitioner/{id} */
    public function practitioner(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->practitioner(User::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Practitioner?facility={} */
    public function searchPractitioner(Request $request): JsonResponse
    {
        $query = User::query()->limit(50);

        if ($facilityId = $request->query('facility')) {
            $query->where('primary_facility_id', $facilityId);
        }

        return $this->fhirResponse($this->fhirService->practitionerBundle($query->get()));
    }

    // =========================================================================
    // Organization
    // =========================================================================

    /** GET /api/fhir/R4/Organization/{id} */
    public function organization(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->organization(Facility::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Organization?active={true|false} */
    public function searchOrganization(Request $request): JsonResponse
    {
        $query = Facility::query()->limit(50);

        if ($request->query('active') !== null) {
            $status = filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN) ? 'active' : 'inactive';
            $query->where('status', $status);
        }

        return $this->fhirResponse($this->fhirService->organizationBundle($query->get()));
    }

    // =========================================================================
    // DocumentReference
    // =========================================================================

    /** GET /api/fhir/R4/DocumentReference/{id} */
    public function documentReference(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->documentReference(OfficialDocument::findOrFail($id)));
    }

    /** GET /api/fhir/R4/DocumentReference?patient={}&status={} */
    public function searchDocumentReference(Request $request): JsonResponse
    {
        $query = OfficialDocument::query()->latest('issued_at')->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->fhirResponse($this->fhirService->documentReferenceBundle($query->get()));
    }

    // =========================================================================
    // Consent
    // =========================================================================

    /** GET /api/fhir/R4/Consent/{id} */
    public function consent(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->consent(ConsentGrant::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Consent?patient={}&status={} */
    public function searchConsent(Request $request): JsonResponse
    {
        $query = ConsentGrant::query()->latest()->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->fhirResponse($this->fhirService->consentBundle($query->get()));
    }

    // =========================================================================
    // Coverage
    // =========================================================================

    /** GET /api/fhir/R4/Coverage/{id} */
    public function coverage(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->coverage(PatientInsurancePolicy::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Coverage?patient={}&status={} */
    public function searchCoverage(Request $request): JsonResponse
    {
        $query = PatientInsurancePolicy::query()->latest()->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->fhirResponse($this->fhirService->coverageBundle($query->get()));
    }

    // =========================================================================
    // Immunization
    // =========================================================================

    /** GET /api/fhir/R4/Immunization/{id} */
    public function immunization(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->immunization(ImmunizationRecord::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Immunization?patient={} */
    public function searchImmunization(Request $request): JsonResponse
    {
        $query = ImmunizationRecord::query()->latest('administered_at')->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }

        $records = $query->get();
        $entries = $records->map(fn ($r) => [
            'fullUrl'  => url('/api/fhir/R4/Immunization/' . $r->id),
            'resource' => $this->fhirService->immunization($r),
        ])->values()->all();

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // AllergyIntolerance
    // =========================================================================

    /** GET /api/fhir/R4/AllergyIntolerance/{id} */
    public function allergyIntolerance(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->allergyIntolerance(AllergyRecord::findOrFail($id)));
    }

    /** GET /api/fhir/R4/AllergyIntolerance?patient={} */
    public function searchAllergyIntolerance(Request $request): JsonResponse
    {
        $query = AllergyRecord::query()->where('status', 'active')->latest()->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }

        $allergies = $query->get();
        $entries   = $allergies->map(fn ($a) => [
            'fullUrl'  => url('/api/fhir/R4/AllergyIntolerance/' . $a->id),
            'resource' => $this->fhirService->allergyIntolerance($a),
        ])->values()->all();

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // Condition
    // =========================================================================

    /** GET /api/fhir/R4/Condition/{id} */
    public function condition(string $id): JsonResponse
    {
        return $this->fhirResponse($this->fhirService->condition(Diagnosis::findOrFail($id)));
    }

    /** GET /api/fhir/R4/Condition?patient={}&clinical-status={} */
    public function searchCondition(Request $request): JsonResponse
    {
        $query = Diagnosis::query()->latest()->limit(50);

        if ($patientId = $request->query('patient')) {
            $query->where('patient_id', str_replace('Patient/', '', $patientId));
        }
        if ($status = $request->query('clinical-status')) {
            $query->where('status', $status);
        }

        $diagnoses = $query->get();
        $entries   = $diagnoses->map(fn ($d) => [
            'fullUrl'  => url('/api/fhir/R4/Condition/' . $d->id),
            'resource' => $this->fhirService->condition($d),
        ])->values()->all();

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => count($entries),
            'entry'        => $entries,
        ]);
    }

    // =========================================================================
    // FHIR Subscriptions (R4)
    // =========================================================================

    /**
     * GET /api/fhir/R4/Subscription
     * Lists subscriptions belonging to the authenticated facility only.
     */
    public function subscriptionIndex(Request $request): JsonResponse
    {
        $facilityId = $this->resolveFacilityId($request);

        $subscriptions = FhirSubscription::query()
            ->where('facility_id', $facilityId)  // [FIX] scope to facility
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (FhirSubscription $s) => $s->toFhirResource());

        return $this->fhirResponse([
            'resourceType' => 'Bundle',
            'type'         => 'searchset',
            'total'        => $subscriptions->count(),
            'entry'        => $subscriptions->map(fn ($r) => ['resource' => $r])->values()->all(),
        ]);
    }

    /**
     * POST /api/fhir/R4/Subscription
     *
     * facility_id now resolved from bearer token middleware attributes —
     * NOT from the X-Facility-Id header, which was unverified and spoofable.
     */
    public function subscriptionCreate(Request $request): JsonResponse
    {
        $facilityId = $this->resolveFacilityId($request);

        $data = $request->validate([
            'criteria'         => 'required|string|max:500',
            'channel.type'     => 'required|in:rest-hook,email,websocket',
            'channel.endpoint' => 'required_if:channel.type,rest-hook|url|max:500',
            'channel.payload'  => 'nullable|string',
            'channel.header'   => 'nullable|array',
            'reason'           => 'nullable|string|max:500',
            'end'              => 'nullable|date',
        ]);

        $subscription = FhirSubscription::create([
            'facility_id'    => $facilityId,  // [FIX] from auth middleware, not header
            'status'         => 'requested',  // [FIX] keep as 'requested' until endpoint verified
            'reason'         => $data['reason'] ?? null,
            'criteria'       => $data['criteria'],
            'channel_type'   => $data['channel']['type'],
            'endpoint'       => $data['channel']['endpoint'] ?? null,
            'headers'        => $data['channel']['header'] ?? null,
            'payload_type'   => $data['channel']['payload'] ?? 'application/fhir+json',
            'end'            => $data['end'] ?? null,
            'created_by'     => $request->attributes->get('integration_client_id'),
            'signing_secret' => Str::random(64),
        ]);

        // Dispatch the handshake verification job for rest-hook subscriptions.
        // The job will POST a FHIR subscription-notification bundle (type=handshake)
        // to the subscriber endpoint and set status='active' on a 2xx response.
        // Non-rest-hook channel types (email, websocket) are activated immediately.
        if ($subscription->channel_type === 'rest-hook') {
            VerifySubscriptionEndpointJob::dispatch($subscription);
        } else {
            $subscription->update(['status' => 'active']);
        }

        return $this->fhirResponse($subscription->fresh()->toFhirResource(), 201);
    }

    /** GET /api/fhir/R4/Subscription/{id} */
    public function subscriptionShow(Request $request, string $id): JsonResponse
    {
        $facilityId   = $this->resolveFacilityId($request);
        $subscription = FhirSubscription::where('id', $id)
            ->where('facility_id', $facilityId)
            ->firstOrFail();

        return $this->fhirResponse($subscription->toFhirResource());
    }

    /**
     * DELETE /api/fhir/R4/Subscription/{id}
     *
     * [FIX] Only the facility that created the subscription may delete it.
     * Previously any authenticated client could delete any subscription.
     */
    public function subscriptionDelete(Request $request, string $id): JsonResponse
    {
        $facilityId   = $this->resolveFacilityId($request);
        $subscription = FhirSubscription::where('id', $id)
            ->where('facility_id', $facilityId)  // ownership check
            ->first();

        if (! $subscription) {
            return $this->fhirDenied('Subscription not found or you do not have permission to delete it.');
        }

        $subscription->update(['status' => 'off']);
        return response()->json(null, 204);
    }

    // =========================================================================
    // FHIR Bulk Export ($export)
    // =========================================================================

    /**
     * GET /api/fhir/R4/$export
     *
     * System-level bulk export — async FHIR Bulk Data Access IG STU1 pattern.
     *
     * Migration Sprint — Item 4: replaced synchronous NDJSON dump with the
     * proper async polling pattern to support datasets larger than 5,000 patients.
     *
     * Flow:
     *   1. Client sends GET /api/fhir/R4/$export
     *   2. Server creates a BulkExportJob, dispatches FhirBulkExportJob to queue.
     *   3. Server responds 202 Accepted + Content-Location header.
     *   4. Client polls GET /api/fhir/R4/bulkdata/{jobId}/status
     *      until it receives 200 (complete) or 500 (failed).
     *   5. Client downloads NDJSON files from the URLs in the 200 response.
     *
     * Security:
     *   - facilityId resolved from auth middleware attributes (never from headers)
     *   - Job is scoped to the facility's consented patients via ConsentGrant
     *   - system:export scope enforced at route level
     */
    public function bulkExport(Request $request): \Illuminate\Http\Response
    {
        $facilityId = $this->resolveFacilityId($request);

        $job = BulkExportJob::create([
            'facility_id'  => $facilityId,
            'requested_by' => $request->attributes->get('integration_client_id'),
            'status'       => 'queued',
            'progress'     => 0,
            'parameters'   => array_filter([
                '_since' => $request->query('_since'),
                '_type'  => $request->query('_type'),
            ]),
        ]);

        FhirBulkExportJob::dispatch($job);

        $statusUrl = url("/api/fhir/R4/bulkdata/{$job->id}/status");

        return response('', 202, [
            'Content-Location' => $statusUrl,
            'X-Progress'       => '0%',
        ]);
    }

    /**
     * GET /api/fhir/R4/Patient/{id}/$export
     *
     * Patient-compartment bulk export. Consent required.
     */
    public function patientBulkExport(Request $request, string $id): \Illuminate\Http\Response
    {
        $patient    = Patient::where('id', $id)->orWhere('health_id', $id)->firstOrFail();
        $facilityId = $this->resolveFacilityId($request);

        if (! $this->hasConsent($patient->id, $facilityId)) {
            return response(json_encode([
                'resourceType' => 'OperationOutcome',
                'issue' => [[
                    'severity' => 'error',
                    'code'     => 'forbidden',
                    'details'  => ['text' => 'No active consent grant for this patient.'],
                ]],
            ]), 403, ['Content-Type' => 'application/fhir+json']);
        }

        $everything = $this->fhirService->patientBundle($patient);

        $lines = collect($everything['entry'] ?? [])
            ->map(fn ($e) => json_encode($e['resource']))
            ->all();

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'application/fhir+ndjson',
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Resolve the authenticated facility ID from bearer token middleware attributes.
     *
     * NEVER read facility_id from request headers/input — headers are unverified
     * and can be set to any value by the caller. The bearer token's facility_id
     * claim is cryptographically bound to the issuing auth server.
     */
    private function resolveFacilityId(Request $request): string
    {
        $facilityId = $request->attributes->get('facility_id');

        if (empty($facilityId)) {
            abort(403, 'Bearer token does not include a facility_id claim. Re-issue your API token.');
        }

        return $facilityId;
    }

    /**
     * Check whether the requesting facility has an active ConsentGrant for the patient.
     *
     * ISO 27799 §7.3: clinical data access requires explicit patient consent.
     * OWASP API1: object-level authorisation must be enforced on every request.
     */
    private function hasConsent(string $patientId, string $facilityId): bool
    {
        return ConsentGrant::where('patient_id', $patientId)
            ->where('requesting_facility_id', $facilityId)
            ->where('status', 'granted')
            ->exists();
    }

    /**
     * Return a FHIR OperationOutcome 403 response.
     */
    private function fhirDenied(string $message): JsonResponse
    {
        return $this->fhirResponse([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code'     => 'forbidden',
                'details'  => ['text' => $message],
            ]],
        ], 403);
    }

    /**
     * Standard FHIR JSON response wrapper.
     */
    private function fhirResponse(array $resource, int $status = 200): JsonResponse
    {
        return response()->json($resource, $status, [
            'Content-Type' => 'application/fhir+json; fhirVersion=4.0',
        ]);
    }
}
