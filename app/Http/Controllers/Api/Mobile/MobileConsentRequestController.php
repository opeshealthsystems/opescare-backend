<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ConsentRequest;
use App\Models\Facility;
use App\Models\Patient;
use App\Modules\Governance\Services\ConsentService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Mobile Provider API — ASKING a patient for access to their record.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS FILE EXISTS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * MobileDoctorChartController refuses a chart read with 403 and a message
 * that says "Ask the patient to grant your facility access" — and until this
 * controller there was no way to ask. `consent_requests` rows could only be
 * created by ConsentService::requestConsent(), which no HTTP route reached;
 * the table had never held a row. The patient's half of the loop
 * (GET /api/mobile/consent-requests, POST .../approve, .../deny) was already
 * built and wired to an inbox that was structurally always empty, and the
 * chart's fifth access basis, `consent_grant`, was unreachable in practice.
 *
 * This is the missing half. An approval here produces exactly the ConsentGrant
 * that MobileDoctorChartController::hasActiveConsentGrant() looks for —
 * patient + facility + status 'active' + expires_at in the future — so basis 5
 * starts working.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * IDENTITY COMES FROM THE TOKEN. FULL STOP.
 * ─────────────────────────────────────────────────────────────────────────
 *
 *     facility  ← $request->attributes 'provider_facility_id'
 *     clinician ← $request->attributes 'provider_user_id'
 *
 * Both are stamped by AuthenticateMobileProvider from the bearer token. There
 * is no `facility_id` in this file's validation rules, no header read, no
 * query parameter and no fallback — a `facility_id` posted in the body, put on
 * the query string or set as a header is simply not looked at, and the row
 * that gets written carries the token's facility. That matters more here than
 * almost anywhere: the facility named on a consent request is the facility
 * that will be able to open the record.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * FINDING THE PATIENT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * EXACT Health ID equality and nothing else — the same rule (and the same
 * three lines) as MobilePrescribingController::lookupPatient() and
 * MobileDoctorChartController::resolvePatient(). No name, no prefix, no LIKE,
 * no listing: patient enumeration is forbidden platform-wide, and "who may I
 * ask for access?" is exactly the query that would leak the register. A
 * clinician who cannot quote the whole Health ID cannot raise a request.
 *
 * Patient carries the IsDemoRecord global scope and it is deliberately not
 * bypassed: on a demo deployment a real patient reading as "not found" here is
 * the isolation working.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NOT SPAMMING THE PATIENT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * One OPEN request per (facility, patient), enforced by the partial unique
 * index `consent_requests_one_open_per_facility`
 * (WHERE status IN ('pending','pending_patient_approval')). The pre-check
 * below is for a good error message; the INDEX is the actual guarantee,
 * because two clinicians at one facility can tap at the same instant and a
 * SELECT-then-INSERT loses that race. A denied request falls outside the
 * predicate, so the facility may ask again later — a plain UNIQUE would have
 * turned one "no" into a permanent ban.
 */
class MobileConsentRequestController extends Controller
{
    /**
     * The coded reasons a facility may give.
     *
     * ConsentService::verifyAccess() JOINs consent_requests on `purpose`, so
     * this has to be a closed vocabulary — free text here would silently make
     * every later verifyAccess(..., 'treatment') miss. The clinician's own
     * words go in `justification`, which nothing joins on.
     *
     * @var list<string>
     */
    public const PURPOSES = [
        'treatment',
        'referral',
        'second_opinion',
        'continuity_of_care',
        'emergency_followup',
    ];

    /**
     * What may be asked for, one entry per section the chart can disclose
     * (MobileDoctorChartController's seven section routes, plus the header).
     *
     * The dot-form vocabulary is the one already in use on the consent side
     * ('patient.summary', 'allergies.read' — see DataGovernancePrivacyTest and
     * ConsentService::verifyAccess). The colon form ('patients:read') is the
     * B2B Connect token-scope vocabulary and is a different namespace; they
     * are not mixed here.
     *
     * @var list<string>
     */
    public const SCOPES = [
        'patient.summary',
        'allergies.read',
        'problems.read',
        'encounters.read',
        'vitals.read',
        'medications.read',
        'diagnoses.read',
        'orders.read',
    ];

    /** How long a GRANT may be asked for: an hour to thirty days. */
    private const GRANT_HOURS_MIN = 1;
    private const GRANT_HOURS_MAX = 720;

    /** How long the ASK itself may stand unanswered: an hour to seven days. */
    private const ASK_HOURS_MIN     = 1;
    private const ASK_HOURS_MAX     = 168;
    private const ASK_HOURS_DEFAULT = 72;

    public function __construct(private readonly ConsentService $consents) {}

    /**
     * Ask a named patient for access.
     *
     * POST /api/mobile/doctor/consent-requests
     *   health_id           required, EXACT
     *   purpose             required, one of PURPOSES
     *   scope[]             required, 1..8 of SCOPES
     *   duration_hours      required, how long the grant should last
     *   expires_in_hours    optional, how long the ask stands (default 72)
     *   justification       optional, what the patient reads
     */
    public function store(Request $request): JsonResponse
    {
        $facilityId  = $this->facilityId($request);
        $clinicianId = $this->clinicianId($request);

        if (! $facilityId || ! $clinicianId) {
            return response()->json([
                'error'   => 'IDENTITY_UNRESOLVABLE',
                'message' => __('consent_requests.identity_unresolvable'),
            ], 401);
        }

        $validated = $request->validate([
            'health_id'        => ['required', 'string', 'max:64'],
            'purpose'          => ['required', 'string', Rule::in(self::PURPOSES)],
            'scope'            => ['required', 'array', 'min:1', 'max:' . count(self::SCOPES)],
            'scope.*'          => ['required', 'string', Rule::in(self::SCOPES)],
            'duration_hours'   => ['required', 'integer', 'min:' . self::GRANT_HOURS_MIN, 'max:' . self::GRANT_HOURS_MAX],
            'expires_in_hours' => ['nullable', 'integer', 'min:' . self::ASK_HOURS_MIN, 'max:' . self::ASK_HOURS_MAX],
            'justification'    => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = $this->resolvePatientByHealthId($validated['health_id']);

        if (! $patient) {
            return response()->json([
                'error'   => 'PATIENT_NOT_FOUND',
                'message' => __('consent_requests.patient_not_found'),
            ], 404);
        }

        // Nothing to ask for: this facility can already open the chart. Asking
        // anyway would put a pointless decision in front of the patient, and
        // the honest answer is the grant they already gave.
        $existingGrant = $this->consents->activeGrantFor($patient->id, $facilityId);

        if ($existingGrant) {
            return response()->json([
                'error'      => 'ALREADY_GRANTED',
                'message'    => __('consent_requests.already_granted'),
                'expires_at' => $existingGrant->expires_at?->toISOString(),
                'scope'      => $existingGrant->scope,
            ], 409);
        }

        // A lapsed ask still sits in the open state and still holds the unique
        // index. Retire it first, so "we asked once and heard nothing" is not
        // a permanent block. (ConsentService::requestConsent does this too;
        // doing it here as well keeps the pre-check below honest.)
        $this->consents->expireStaleRequests($patient->id, $facilityId);

        $open = $this->consents->openRequestFor($patient->id, $facilityId);

        if ($open) {
            return response()->json([
                'error'      => 'REQUEST_ALREADY_PENDING',
                'message'    => __('consent_requests.already_pending'),
                'data'       => $this->format($open),
            ], 409);
        }

        $expiresAt = Carbon::now()->addHours(
            (int) ($validated['expires_in_hours'] ?? self::ASK_HOURS_DEFAULT)
        );

        try {
            $consentRequest = $this->consents->requestConsent(
                $patient->id,
                $facilityId,
                $clinicianId,
                $validated['purpose'],
                array_values(array_unique($validated['scope'])),
                (int) $validated['duration_hours'] * 60,
                $expiresAt,
                $validated['justification'] ?? null,
            );
        } catch (QueryException $e) {
            // 23505 = unique_violation. The partial unique index caught a race
            // the pre-check above could not: another clinician at this facility
            // raised a request between the SELECT and the INSERT. That is the
            // constraint working, not an error to surface as a 500.
            if ($this->isUniqueViolation($e)) {
                $open = $this->consents->openRequestFor($patient->id, $facilityId);

                return response()->json([
                    'error'   => 'REQUEST_ALREADY_PENDING',
                    'message' => __('consent_requests.already_pending'),
                    'data'    => $open ? $this->format($open) : null,
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => __('consent_requests.created'),
            'data'    => $this->format($consentRequest),
        ], 201);
    }

    /**
     * What THIS FACILITY has asked for, newest first.
     *
     * Scoped to the token's facility and to nothing else. There is no patient
     * parameter and no way to widen it: this is the facility's own outbox, not
     * a view of anybody's consent history.
     *
     * GET /api/mobile/doctor/consent-requests?status=&limit=
     */
    public function index(Request $request): JsonResponse
    {
        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return response()->json([
                'error'   => 'IDENTITY_UNRESOLVABLE',
                'message' => __('consent_requests.identity_unresolvable'),
            ], 401);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                'open',
                ConsentService::STATUS_APPROVED,
                ConsentService::STATUS_DENIED,
                ConsentService::STATUS_EXPIRED,
                ConsentService::STATUS_WITHDRAWN,
            ])],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Retire anything that lapsed before it is listed, so the outbox never
        // shows a dead request as still pending.
        $this->consents->expireStaleRequests(null, $facilityId);

        $query = ConsentRequest::where('requesting_facility_id', $facilityId)
            ->with('patient:id,health_id,first_name,last_name')
            ->latest();

        if (($validated['status'] ?? null) === 'open') {
            $query->whereIn('status', ConsentService::OPEN_STATUSES);
        } elseif (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $rows = $query->limit((int) ($validated['limit'] ?? 50))->get();

        return response()->json([
            'data' => $rows->map(fn (ConsentRequest $row) => $this->format($row))->values(),
        ], 200);
    }

    /**
     * One request this facility raised.
     *
     * GET /api/mobile/doctor/consent-requests/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return response()->json([
                'error'   => 'IDENTITY_UNRESOLVABLE',
                'message' => __('consent_requests.identity_unresolvable'),
            ], 401);
        }

        $consentRequest = $this->findForFacility($id, $facilityId);

        if (! $consentRequest) {
            return response()->json([
                'error'   => 'NOT_FOUND',
                'message' => __('consent_requests.not_found'),
            ], 404);
        }

        return response()->json(['data' => $this->format($consentRequest)], 200);
    }

    /**
     * Take the ask back.
     *
     * POST /api/mobile/doctor/consent-requests/{id}/withdraw
     *
     * The one open request per (facility, patient) rule is strict on purpose,
     * which means a mistyped Health ID would otherwise lock this facility out
     * of asking the right patient until the wrong request expired. Withdrawing
     * is terminal — it does not reopen and it is not a decision by the
     * patient, so it is audited as a facility action.
     */
    public function withdraw(Request $request, string $id): JsonResponse
    {
        $facilityId  = $this->facilityId($request);
        $clinicianId = $this->clinicianId($request);

        if (! $facilityId || ! $clinicianId) {
            return response()->json([
                'error'   => 'IDENTITY_UNRESOLVABLE',
                'message' => __('consent_requests.identity_unresolvable'),
            ], 401);
        }

        // Scoped by the TOKEN's facility before the service is called: without
        // this, an id belonging to another facility's request would be
        // withdrawable by anyone holding any provider token.
        $consentRequest = $this->findForFacility($id, $facilityId);

        if (! $consentRequest) {
            return response()->json([
                'error'   => 'NOT_FOUND',
                'message' => __('consent_requests.not_found'),
            ], 404);
        }

        // Throws ConflictHttpException (409) when the request is already
        // decided — approve/deny/withdraw are all one-way.
        $withdrawn = $this->consents->withdrawConsentRequest($consentRequest->id, $clinicianId);

        return response()->json([
            'message' => __('consent_requests.withdrawn'),
            'data'    => $this->format($withdrawn->fresh() ?? $withdrawn),
        ], 200);
    }

    /**
     * The vocabulary this endpoint accepts, so the app does not have to hard
     * code it and drift.
     *
     * GET /api/mobile/doctor/consent-requests/options
     */
    public function options(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'purposes' => array_map(fn (string $p) => [
                    'value' => $p,
                    'label' => __('consent_requests.purpose.' . $p),
                ], self::PURPOSES),
                'scopes' => array_map(fn (string $s) => [
                    'value' => $s,
                    'label' => __('consent_requests.scope.' . $s),
                ], self::SCOPES),
                'duration_hours'   => ['min' => self::GRANT_HOURS_MIN, 'max' => self::GRANT_HOURS_MAX],
                'expires_in_hours' => [
                    'min'     => self::ASK_HOURS_MIN,
                    'max'     => self::ASK_HOURS_MAX,
                    'default' => self::ASK_HOURS_DEFAULT,
                ],
            ],
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════════

    /** The token's facility. Never the body's, the query's or a header's. */
    private function facilityId(Request $request): ?string
    {
        return $request->attributes->get('provider_facility_id');
    }

    /** The token's clinician, likewise stamped by the middleware. */
    private function clinicianId(Request $request): ?string
    {
        return $request->attributes->get('provider_user_id');
    }

    /**
     * ONE patient by EXACT Health ID, or null.
     *
     * Case-folding normalises one whole identifier; the comparison stays an
     * equality on the full Health ID. There is no LIKE and no prefix here, so
     * this cannot be walked.
     */
    private function resolvePatientByHealthId(string $healthId): ?Patient
    {
        $key = strtoupper(trim($healthId));

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        // Follows a merge: a retired Health ID still opens the surviving record.
        return Patient::resolveHealthId($key);
    }

    /** A request by id, but only if it belongs to the caller's facility. */
    private function findForFacility(string $id, string $facilityId): ?ConsentRequest
    {
        // consent_requests.id is a Postgres uuid column: a malformed value
        // would raise "invalid input syntax for type uuid" rather than a 404.
        if (! \Illuminate\Support\Str::isUuid($id)) {
            return null;
        }

        return ConsentRequest::where('id', $id)
            ->where('requesting_facility_id', $facilityId)
            ->with('patient:id,health_id,first_name,last_name')
            ->first();
    }

    /** Postgres unique_violation, whatever driver wrapper it arrives in. */
    private function isUniqueViolation(QueryException $e): bool
    {
        if (($e->errorInfo[0] ?? null) === '23505') {
            return true;
        }

        return str_contains($e->getMessage(), 'consent_requests_one_open_per_facility');
    }

    /**
     * The wire shape.
     *
     * Deliberately not ConsentRequestResource: that resource is the PATIENT's
     * view (it carries the grant id a patient revokes and the requesting
     * facility's name) and knows nothing about the columns this half added.
     * The facility already knows which facility it is; what it needs back is
     * which patient, what was asked, and where the ask stands.
     *
     * @return array<string,mixed>
     */
    private function format(ConsentRequest $row): array
    {
        $patient = $row->relationLoaded('patient')
            ? $row->patient
            : Patient::where('id', $row->patient_id)->first();

        $expiresAt = $row->expires_at ? Carbon::parse($row->expires_at) : null;
        $decidedAt = $row->decided_at ? Carbon::parse($row->decided_at) : null;

        $grant = $row->status === ConsentService::STATUS_APPROVED
            ? $this->consents->activeGrantFor($row->patient_id, $row->requesting_facility_id)
            : null;

        return [
            'id'                => $row->id,
            'status'            => $row->status,
            'status_label'      => __('consent_requests.status.' . $row->status),
            'is_open'           => in_array($row->status, ConsentService::OPEN_STATUSES, true),
            'patient_id'        => $row->patient_id,
            'patient_health_id' => $patient?->health_id,
            'patient_name'      => $patient
                ? trim($patient->first_name . ' ' . $patient->last_name)
                : null,
            'purpose'           => $row->purpose,
            'purpose_label'     => __('consent_requests.purpose.' . $row->purpose),
            'justification'     => $row->justification,
            'requested_scope'   => $row->requested_scope,
            'scope_labels'      => array_map(
                fn (string $s) => __('consent_requests.scope.' . $s),
                (array) $row->requested_scope
            ),
            'duration_minutes'  => (int) $row->duration_minutes,
            'duration_hours'    => (int) round(((int) $row->duration_minutes) / 60),
            'expires_at'        => $expiresAt?->toISOString(),
            'decided_at'        => $decidedAt?->toISOString(),
            'requested_by'      => $row->requesting_user_id,
            'facility_id'       => $row->requesting_facility_id,
            'facility_name'     => Facility::where('id', $row->requesting_facility_id)->value('name'),
            // Present only once approved: the live grant this ask produced, so
            // the clinician can see how long their access actually runs.
            'grant_expires_at'  => $grant?->expires_at?->toISOString(),
            'created_at'        => $row->created_at?->toISOString(),
        ];
    }
}
