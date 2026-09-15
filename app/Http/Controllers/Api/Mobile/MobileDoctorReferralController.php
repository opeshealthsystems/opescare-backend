<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\PatientReferralRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PatientReferralRequest;
use App\Models\ReferralAccessGrant;
use App\Models\ReferralCase;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Referral\ReferralDeskService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile Provider API — clinician-to-clinician REFERRALS.
 *
 * Serves `/api/mobile/doctor/referrals` and the two chart-scoped writes that
 * raise one. Every route sits behind `auth.provider` + `capability:clinical_chart`,
 * so:
 *
 *     clinician  ← $request->attributes 'provider_user_id'
 *     facility   ← $request->attributes 'provider_facility_id'
 *
 * Both are stamped by AuthenticateMobileProvider from the bearer token. Neither
 * is ever read from the body, query string, header or session, and there is no
 * parameter in this file that lets a caller name either one. A
 * `referring_facility_id` posted in the body is simply not looked at — which is
 * the one thing the platform's own partner-API referral endpoint got wrong.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * A REFERRAL IS A DISCLOSURE DECISION, NOT A MESSAGE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Raising a referral hands a patient's care — and potentially their record —
 * to a DIFFERENT facility. Two separate authorisations are therefore required,
 * and they are not the same authorisation:
 *
 *   1. May this clinician act on this patient at all?
 *      → ClinicalRelationshipGate, exactly as the chart applies it. Holding a
 *        patient id is not authorisation. There is no search in this file and
 *        no listing of patients: a referral is raised from a chart the
 *        clinician can already open, addressed by exact patient id or exact
 *        Health ID.
 *
 *   2. May the RECEIVING facility read the record package?
 *      → an active ConsentGrant held by the RECEIVING facility. A grant to the
 *        referring facility authorises the referring facility and nobody else,
 *        so it can never stand in for one. See sendConsent() below: when no
 *        such grant exists the referral is still sent — a patient must be able
 *        to be referred — but it carries `included_record_types: []` and says
 *        so out loud. Records are never quietly attached.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * IMMUTABLE, LIKE EVERY OTHER CLINICAL EVENT
 * ─────────────────────────────────────────────────────────────────────────
 *
 *     draft ──→ sent ──→ accepted ──→ completed
 *       │        │  ↘ rejected
 *       ↓        ↓
 *     cancelled  expired
 *
 * Status only moves forward. TRANSITIONS below is the whole rule, enforced
 * server-side; an illegal move is 409, never a silent overwrite. Nothing here
 * deletes a referral — cancel and expire are how one ends. There is no update
 * endpoint: the clinical content of a sent referral is a record of what was
 * said at the time.
 *
 * Every read and every write is access-logged against the patient, and — as on
 * the chart — a disclosure that cannot be recorded does not happen (503).
 */
class MobileDoctorReferralController extends Controller
{
    public function __construct(private readonly ReferralDeskService $referrals)
    {
    }

    /**
     * The lifecycle rules live in App\Services\Referral\ReferralDeskService,
     * shared with the web referral desk. These aliases keep the validation
     * below reading exactly as it did.
     */
    private const TRANSITIONS         = ReferralDeskService::TRANSITIONS;
    private const URGENCIES           = ReferralDeskService::URGENCIES;
    private const RECORD_TYPES        = ReferralDeskService::RECORD_TYPES;
    private const DEFAULT_EXPIRY_DAYS = ReferralDeskService::DEFAULT_EXPIRY_DAYS;
    private const MAX_EXPIRY_DAYS     = ReferralDeskService::MAX_EXPIRY_DAYS;

    /** Hard ceiling on any list this controller returns. */
    private const LIST_LIMIT     = 50;
    private const LIST_MAX_LIMIT = 100;

    // ══════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/doctor/referrals?direction=&status=&limit=
     *
     * This FACILITY's referral book — the ones it sent and the ones addressed
     * to it, and nothing else. The facility is the token's; there is no
     * parameter that widens the query to another one, so this cannot be turned
     * into a cross-facility listing.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $validated = $request->validate([
            'direction' => ['nullable', Rule::in(['outbound', 'inbound'])],
            'status'    => ['nullable', Rule::in(array_keys(self::TRANSITIONS))],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:' . self::LIST_MAX_LIMIT],
        ]);

        $direction = $validated['direction'] ?? null;

        $rows = ReferralCase::query()
            ->when($direction === 'outbound', fn ($q) => $q->where('referring_facility_id', $facilityId))
            ->when($direction === 'inbound', fn ($q) => $q->where('receiving_facility_id', $facilityId))
            ->when($direction === null, fn ($q) => $q->where(function ($w) use ($facilityId) {
                $w->where('referring_facility_id', $facilityId)
                  ->orWhere('receiving_facility_id', $facilityId);
            }))
            ->when(isset($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->with(['referringFacility:id,name', 'receivingFacility:id,name'])
            ->orderByDesc('created_at')
            ->limit($validated['limit'] ?? self::LIST_LIMIT)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ReferralCase $r) => $this->serialize($r, $facilityId))->values(),
            'meta' => [
                'facility_id' => $facilityId,
                'direction'   => $direction ?? 'all',
                'count'       => $rows->count(),
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/doctor/referrals/{id}
     *
     * One referral, only if this facility is a party to it. A referral between
     * two other facilities answers 404 — not 403 — because confirming its
     * existence would already say that this patient was referred somewhere.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $referral = $this->findForFacility($id, $facilityId);

        if (! $referral) {
            return $this->notFound();
        }

        if (! $this->audit($referral->patient_id, $request, 'referral', 'read', $referral->id)) {
            return $this->auditUnavailable();
        }

        $referral->load(['referringFacility:id,name', 'receivingFacility:id,name']);

        return response()->json([
            'data' => $this->serialize($referral, $facilityId, withDetail: true),
        ], 200);
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/referral-requests
     *
     * The patient's own open asks ("please refer me to a cardiologist"), so a
     * clinician can answer one instead of guessing. Chart-scoped on purpose:
     * these requests carry no facility of their own, so a national queue of
     * them would be a list of patients and their complaints handed to any
     * professional with a token. Scoped to a patient this clinician may
     * already open, it discloses nothing new.
     *
     * A facility-side inbox — requests whose preferred_care_facility_id maps to
     * this facility — is a reasonable next step and is NOT built here.
     */
    public function patientRequests(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient] = $ctx;

        if (! $this->audit($patient->id, $request, 'referral_request', 'read', null)) {
            return $this->auditUnavailable();
        }

        $rows = PatientReferralRequest::forPatient($patient->id)
            ->orderByDesc('created_at')
            ->limit(self::LIST_LIMIT)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (PatientReferralRequest $r) => [
                'id'                  => $r->id,
                'reference'           => $r->reference,
                'requested_specialty' => $r->requested_specialty,
                'reason'              => $r->reason,
                'symptom_duration'    => $r->symptom_duration,
                // The PATIENT's own sense of urgency — never clinical triage.
                // Labelled so no screen can render it as `urgency`.
                'patient_reported_urgency' => $r->reported_urgency,
                'patient_note'        => $r->patient_note,
                'status'              => $r->status->value,
                'is_open'             => $r->status->isOpen(),
                'has_issued_referral' => $r->status->hasIssuedReferral(),
                'referral_case_id'    => $r->referral_case_id,
                'created_at'          => $this->iso($r->created_at),
                'reviewed_at'         => $this->iso($r->reviewed_at),
            ])->values(),
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // Writing
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/doctor/patients/{id}/referrals
     *
     * Raise a referral as a DRAFT. Drafts disclose nothing: no consent is
     * looked at and no record access exists until send().
     *
     * Body: receiving_facility_id? | receiving_specialty? | receiving_provider_name?
     *       urgency? reason* clinical_summary? included_record_types[]?
     *       patient_referral_request_id?
     *
     * `referring_facility_id` and `referring_provider_id` are NOT accepted —
     * they come from the token.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient, $clinicianId, $facilityId] = $ctx;

        $validated = $request->validate([
            'receiving_facility_id'       => ['nullable', 'uuid'],
            'receiving_specialty'         => ['nullable', 'string', 'max:120'],
            'receiving_provider_name'     => ['nullable', 'string', 'max:200'],
            'urgency'                     => ['nullable', Rule::in(self::URGENCIES)],
            'reason'                      => ['required', 'string', 'max:2000'],
            'clinical_summary'            => ['nullable', 'string', 'max:8000'],
            'included_record_types'       => ['nullable', 'array', 'max:' . count(self::RECORD_TYPES)],
            'included_record_types.*'     => ['string', Rule::in(self::RECORD_TYPES)],
            'patient_referral_request_id' => ['nullable', 'uuid'],
        ]);

        // A referral has to be addressed to somebody: a facility on this
        // platform, or — for a hospital that is not on it yet — a specialty or
        // a named clinician. All three empty is not a referral.
        if (empty($validated['receiving_facility_id'])
            && empty($validated['receiving_specialty'])
            && empty($validated['receiving_provider_name'])) {
            return response()->json([
                'message'    => __('doctor_referrals.destination_required'),
                'error_code' => 'DESTINATION_REQUIRED',
            ], 422);
        }

        $receivingFacilityId = $validated['receiving_facility_id'] ?? null;

        if ($receivingFacilityId !== null) {
            if ($receivingFacilityId === $facilityId) {
                return response()->json([
                    'message'    => __('doctor_referrals.self_referral'),
                    'error_code' => 'SELF_REFERRAL',
                ], 422);
            }

            if (! Facility::whereKey($receivingFacilityId)->exists()) {
                return response()->json([
                    'message'    => __('doctor_referrals.receiving_facility_not_found'),
                    'error_code' => 'RECEIVING_FACILITY_NOT_FOUND',
                ], 422);
            }
        }

        // The patient's own ask, if this referral answers one. It must belong
        // to THIS patient and still be open — a clinician cannot attach a
        // referral to somebody else's request, or re-answer a closed one.
        $patientRequest = null;

        if (! empty($validated['patient_referral_request_id'])) {
            $patientRequest = PatientReferralRequest::query()
                ->whereKey($validated['patient_referral_request_id'])
                ->where('patient_id', $patient->id)
                ->first();

            if (! $patientRequest || ! $patientRequest->status->isOpen()) {
                return response()->json([
                    'message'    => __('doctor_referrals.request_not_actionable'),
                    'error_code' => 'REQUEST_NOT_ACTIONABLE',
                ], 422);
            }
        }

        if (! $this->audit($patient->id, $request, 'referral', 'create', null)) {
            return $this->auditUnavailable();
        }

        $referral = $this->referrals->createDraft($patient, $facilityId, $clinicianId, $receivingFacilityId, $validated, $patientRequest);

        return response()->json([
            'data' => $this->serialize($referral, $facilityId, withDetail: true),
        ], 201);
    }

    /**
     * POST /api/mobile/doctor/referrals/{id}/send
     *
     * The referring facility sends the draft. This is the moment the consent
     * question is answered — see sendConsent().
     */
    public function send(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'sent', 'referring', function (ReferralCase $referral, Request $request, ?string $clinicianId) {
            $validated = $request->validate([
                'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_EXPIRY_DAYS],
            ]);

            $consent = $this->referrals->send($referral, isset($validated['expires_in_days']) ? (int) $validated['expires_in_days'] : null, $clinicianId);

            return $consent;
        });
    }

    /**
     * POST /api/mobile/doctor/referrals/{id}/accept — the receiving facility
     * takes the patient on.
     */
    public function accept(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'accepted', 'receiving', function (ReferralCase $referral, Request $request, ?string $clinicianId) {
            $this->referrals->accept($referral, $clinicianId);

            return null;
        });
    }

    /** POST /api/mobile/doctor/referrals/{id}/reject */
    public function reject(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'rejected', 'receiving', function (ReferralCase $referral, Request $request) {
            $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

            $this->referrals->reject($referral, $validated['reason']);

            return null;
        });
    }

    /**
     * POST /api/mobile/doctor/referrals/{id}/complete — the receiving facility
     * has seen the patient. Optional feedback goes back to the referrer.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'completed', 'receiving', function (ReferralCase $referral, Request $request) {
            $validated = $request->validate(['feedback' => ['nullable', 'string', 'max:8000']]);

            $this->referrals->complete($referral, $validated['feedback'] ?? null);

            return null;
        });
    }

    /**
     * POST /api/mobile/doctor/referrals/{id}/cancel — the REFERRING facility
     * withdraws it. The only way a referral leaves the book; there is no
     * delete.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'cancelled', 'referring', function (ReferralCase $referral, Request $request) {
            $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

            $this->referrals->cancel($referral, $validated['reason']);

            return null;
        });
    }

    // Consent (sendConsent) and grant revocation live in ReferralDeskService.

    // ══════════════════════════════════════════════════════════════════
    // The state machine
    // ══════════════════════════════════════════════════════════════════

    /**
     * One shape for all five status writes: resolve the referral, check this
     * facility is the right PARTY to make this move, check the move itself is
     * legal, audit, then apply.
     *
     * @param  string  $side  'referring' or 'receiving' — which party may move
     */
    private function transition(Request $request, string $id, string $target, string $side, callable $apply): JsonResponse
    {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $referral = $this->findForFacility($id, $facilityId);

        if (! $referral) {
            return $this->notFound();
        }

        // Wrong party (403) or an illegal move (409) — decided by the shared
        // service. Expiry is a state: a sent referral past its date is expired.
        $refusal = $this->referrals->refusal($referral, $facilityId, $target, $side);

        if ($refusal !== null && $refusal['error'] === 'WRONG_PARTY') {
            return response()->json([
                'message'    => __('doctor_referrals.wrong_party'),
                'error_code' => 'WRONG_PARTY',
            ], 403);
        }

        if ($refusal !== null) {
            return response()->json([
                'message'        => __('doctor_referrals.illegal_transition'),
                'error_code'     => 'ILLEGAL_TRANSITION',
                'current_status' => $refusal['current_status'],
                'attempted'      => $target,
                'allowed'        => $refusal['allowed'],
            ], 409);
        }

        if (! $this->audit($referral->patient_id, $request, 'referral', 'update', $referral->id)) {
            return $this->auditUnavailable();
        }

        $outcome = $apply($referral, $request, $clinicianId, $facilityId);

        $payload = ['data' => $this->serialize($referral->refresh(), $facilityId, withDetail: true)];

        if (is_array($outcome)) {
            $payload['record_access'] = $this->recordAccessNotice($outcome);
        }

        return response()->json($payload, 200);
    }

    /**
     * What the client must be TOLD about record access. A clinician who is not
     * told the package is empty will assume the other end can read the chart.
     *
     * @param  array<string,mixed>  $consent
     * @return array<string,mixed>
     */
    private function recordAccessNotice(array $consent): array
    {
        $granted = ($consent['record_types'] ?? []) !== [];

        return [
            'granted'         => $granted,
            'basis'           => $consent['basis'] ?? 'no_consent',
            'record_types'    => $consent['record_types'] ?? [],
            'message'         => $granted
                ? __('doctor_referrals.records_shared')
                : __('doctor_referrals.records_not_shared'),
            'required_action' => $granted ? null : 'request_consent',
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════

    /**
     * Resolve the patient AND the clinician's right to act on them, exactly as
     * the chart does. Returns a JsonResponse on refusal.
     *
     * A referral is a write on the chart, and PREVIEW-scope access (identity,
     * allergies, medicines — granted because the patient asked their record
     * stay behind consent) is not enough to hand a whole record to somebody
     * else, so this requires the full-record basis, not merely a relationship.
     *
     * @return array{0: Patient, 1: string, 2: string, 3: ClinicalRelationshipGate}|JsonResponse
     */
    private function patientContext(Request $request, string $id): array|JsonResponse
    {
        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $patient = $this->resolvePatient($id);

        if (! $patient) {
            return response()->json([
                'message'    => __('doctor_referrals.patient_not_found'),
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patient->id);

        if (! $verdict->holds()) {
            $this->auditDenial($patient->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('doctor_referrals.no_clinical_relationship'),
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'required_action' => 'request_consent',
            ], 403);
        }

        if (! $verdict->coversFullRecord()) {
            $this->auditDenial($patient->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('doctor_referrals.consent_required'),
                'error_code'      => 'CONSENT_REQUIRED',
                'required_action' => 'request_consent',
            ], 403);
        }

        return [$patient, $clinicianId, $facilityId, $verdict];
    }

    /**
     * ONE patient by EXACT id or EXACT Health ID — the same resolution the
     * chart uses. No LIKE, no prefix, no name: this cannot be walked.
     */
    private function resolvePatient(string $id): ?Patient
    {
        $key = trim($id);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        return Str::isUuid($key)
            ? Patient::resolveId($key)
            : Patient::resolveHealthId($key);
    }

    /** A referral this facility is a party to, or null. */
    private function findForFacility(string $id, string $facilityId): ?ReferralCase
    {
        return $this->referrals->findForFacility($id, $facilityId);
    }

    /**
     * @return array<string,mixed>
     */
    private function serialize(ReferralCase $r, string $facilityId, bool $withDetail = false): array
    {
        $isExpired = ($r->status === 'sent' && $r->isExpired());
        $status    = $isExpired ? 'expired' : (string) $r->status;

        $row = [
            'id'                      => $r->id,
            'patient_id'              => $r->patient_id,
            'direction'               => $r->referring_facility_id === $facilityId ? 'outbound' : 'inbound',
            'referring_facility_id'   => $r->referring_facility_id,
            'referring_facility_name' => $r->relationLoaded('referringFacility') ? $r->referringFacility?->name : null,
            'receiving_facility_id'   => $r->receiving_facility_id,
            'receiving_facility_name' => $r->relationLoaded('receivingFacility') ? $r->receivingFacility?->name : null,
            'receiving_specialty'     => $r->receiving_specialty,
            'receiving_provider_name' => $r->receiving_provider_name,
            'urgency'                 => $r->urgency,
            'status'                  => $status,
            // True only when the DATE has passed on a still-sent referral. The
            // stored status is reported alongside it rather than quietly
            // rewritten, so nothing here claims a write that did not happen.
            'is_expired'              => $isExpired,
            'stored_status'           => (string) $r->status,
            'reason'                  => $r->reason,
            'allowed_transitions'     => array_values(self::TRANSITIONS[$status] ?? []),
            'expires_at'              => $this->iso($r->expires_at),
            'created_at'              => $this->iso($r->created_at),
        ];

        if (! $withDetail) {
            return $row;
        }

        return $row + [
            'referring_provider_id' => $r->referring_provider_id,
            'clinical_summary'      => $r->clinical_summary,
            // What the receiving end may actually read. Empty means empty —
            // it is never a placeholder for "everything".
            'included_record_types' => array_values((array) ($r->included_record_types ?? [])),
            'has_record_access'     => ! empty($r->included_record_types) && $r->consent_grant_id !== null,
            'consent_grant_id'      => $r->consent_grant_id,
            'accepted_at'           => $this->iso($r->accepted_at),
            'accepted_by_id'        => $r->accepted_by_id,
            'rejected_at'           => $this->iso($r->rejected_at),
            'rejection_reason'      => $r->rejection_reason,
            'completed_at'          => $this->iso($r->completed_at),
            'feedback'              => $r->feedback,
            'cancelled_at'          => $this->iso($r->cancelled_at),
            'cancellation_reason'   => $r->cancellation_reason,
            'updated_at'            => $this->iso($r->updated_at),
        ];
    }

    /**
     * Record the act against the patient. Returns false when it could not be
     * written, which every caller turns into a 503 rather than proceeding: a
     * referral moved with no trail is exactly the event the audit exists for.
     */
    private function audit(string $patientId, Request $request, string $dataCategory, string $accessType, ?string $resourceId): bool
    {
        $clinicianId = $this->clinicianId($request);

        if (! $clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $this->facilityId($request),
                purpose: 'treatment:referral',
                dataCategory: $dataCategory,
                resourceType: 'ReferralCase',
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Clinician referral action refused: access log could not be written.', [
                'patient_id'   => $patientId,
                'clinician_id' => $clinicianId,
                'access_type'  => $accessType,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Best-effort: a refusal disclosed nothing, so it must not become a 500. */
    private function auditDenial(string $patientId, ?string $clinicianId, ?string $facilityId): void
    {
        if (! $clinicianId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE,
                dataCategory: 'referral',
                resourceType: 'ReferralCase',
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Clinician referral denial was not access-logged.', [
                'patient_id'   => $patientId,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /** The clinician's user id — stamped by AuthenticateMobileProvider. */
    private function clinicianId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * The facility this session speaks for. Only ever from the request
     * attributes the auth middleware set — never a header, body, query string
     * or session, and never a fallback default.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Honour Accept-Language for this response only. */
    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }

    private function facilityRequired(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_referrals.facility_required'),
            'error_code' => 'IDENTITY_UNRESOLVABLE',
        ], 401);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_referrals.referral_not_found'),
            'error_code' => 'NOT_FOUND',
        ], 404);
    }

    private function auditUnavailable(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_referrals.audit_unavailable'),
            'error_code' => 'AUDIT_UNAVAILABLE',
        ], 503);
    }

    private function iso(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface ? Carbon::instance($value)->toIso8601String() : null;
    }
}
