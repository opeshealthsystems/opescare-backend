<?php

namespace App\Modules\Governance\Services;

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\ConsentRequest;
use App\Models\ConsentGrant;
use App\Models\ConsentRevocation;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Notifications\Services\NotificationService;
use App\Notifications\ConsentAccessRequestedNotification;
use App\Support\PatientPush;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The consent loop: a facility ASKS, the patient DECIDES, a grant is what
 * authorises the read.
 *
 * ── THE STATE MACHINE ─────────────────────────────────────────────────────
 *
 *                      ┌──────────► approved ──► (ConsentGrant, expires)
 *                      │
 *   pending_patient_   ├──────────► denied      terminal
 *   approval  ─────────┤
 *   (the ONLY open     ├──────────► withdrawn   terminal (the facility gave up)
 *    state)            │
 *                      └──────────► expired     terminal (nobody answered)
 *
 * Every arrow out of the open state is one-way. Nothing in this class writes
 * a request BACK to the open state, which is what stops a denial from
 * silently reopening; and every one of them is guarded by assertOpen(), so a
 * second approve/deny/withdraw on a decided request is a 409, never a second
 * grant.
 *
 * ── TWO CLOCKS, TWO COLUMNS ───────────────────────────────────────────────
 *
 *   consent_requests.expires_at        when the ASK lapses unanswered
 *   consent_requests.duration_minutes  how long the GRANT lasts once approved
 *
 * They are unrelated: a request that stands for 3 days can be asking for 30
 * days of access. Conflating them is how a patient ends up granting access for
 * as long as they happened to take to answer.
 *
 * ── PURPOSE IS A KEY, JUSTIFICATION IS PROSE ──────────────────────────────
 *
 * verifyAccess() JOINs consent_requests on `purpose`, so `purpose` has to stay
 * a stable coded token ('treatment', 'referral', …). The clinician's own words
 * live in `justification`, which nothing joins on and which is what the patient
 * actually reads.
 *
 * ── EVERY STATE CHANGE IS AUDITED ─────────────────────────────────────────
 *
 * to `audit_events` — the table with a nullable actor_id and a consent_grant_id
 * column, which is what an expiry (no actor) and an approval (a grant) each
 * need. Auditing is best-effort by design here: it must never be the reason a
 * patient's own decision fails to record. A failure is logged loudly instead.
 */
class ConsentService
{
    /** What ConsentService writes, and what consent_requests.status defaults to. */
    public const STATUS_PENDING = 'pending_patient_approval';

    /**
     * Both spellings of "open". `pending_patient_approval` is this table's own;
     * `pending` is the shorter value the rest of the codebase uses for review
     * queues, and rows could arrive with it. The partial unique index
     * `consent_requests_one_open_per_facility` covers exactly this same pair —
     * the two lists MUST stay in step or the constraint and the code disagree.
     *
     * @var list<string>
     */
    public const OPEN_STATUSES = ['pending', 'pending_patient_approval'];

    public const STATUS_APPROVED  = 'approved';
    public const STATUS_DENIED    = 'denied';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public function __construct(private NotificationService $notificationService) {}

    /**
     * A facility asks a patient for access.
     *
     * The trailing three parameters are additive — every existing caller
     * (MobileGovernanceController and the two governance tests) passes six
     * arguments and keeps working unchanged.
     *
     * THROWS on a duplicate. The "one open request per (facility, patient)"
     * rule is enforced by a partial unique index, not by a SELECT here: a
     * check-then-insert loses to two clinicians at the same facility tapping
     * at the same moment. The QueryException that surfaces (SQLSTATE 23505) is
     * the constraint doing its job and the caller turns it into a 409.
     *
     * @param  list<string>  $scopes
     */
    public function requestConsent(
        string $patientId,
        string $facilityId,
        ?string $userId,
        string $purpose,
        array $scopes,
        int $durationMinutes,
        ?\DateTimeInterface $requestExpiresAt = null,
        ?string $justification = null
    ): ConsentRequest {
        // A request that lapsed unanswered still occupies the open state, and
        // therefore the unique index. Retire it before asking again, so "we
        // asked in March and heard nothing" never blocks asking in September.
        $this->expireStaleRequests($patientId, $facilityId);

        $request = new ConsentRequest();
        $request->patient_id = $patientId;
        $request->requesting_facility_id = $facilityId;
        $request->requesting_user_id = $userId;
        $request->purpose = $purpose;
        $request->requested_scope = array_values($scopes);
        $request->duration_minutes = $durationMinutes;
        $request->justification = $justification;
        $request->expires_at = $requestExpiresAt;
        $request->status = self::STATUS_PENDING;
        $request->save();

        $this->audit(
            AuditEventType::RequestConsent->value,
            $request,
            actorId: $userId,
            actorRole: 'provider',
            reason: $purpose,
            after: [
                'status'           => $request->status,
                'requested_scope'  => $request->requested_scope,
                'duration_minutes' => $durationMinutes,
                'expires_at'       => $requestExpiresAt?->format(DATE_ATOM),
            ],
        );

        $this->notifyPatientOfRequest($request);

        return $request;
    }

    /**
     * The patient says yes. Produces the ConsentGrant that actually authorises
     * access.
     *
     * The grant is written to be exactly what a reader checks for. The chart's
     * fifth access basis (MobileDoctorChartController::hasActiveConsentGrant)
     * asks for patient_id + facility_id + status 'active' + expires_at in the
     * future, and RequireConsentGrant and verifyAccess() add scope on top of
     * that — so facility_id comes from the REQUEST (never from anything the
     * approving patient sends) and the scope is the scope that was asked for,
     * copied verbatim rather than widened.
     */
    public function approveConsent(string $requestId, string $userId): ConsentGrant
    {
        $request = ConsentRequest::findOrFail($requestId);
        $this->assertOpen($request);

        $request->status = self::STATUS_APPROVED;
        $request->decided_at = Carbon::now();
        $request->decided_by = $userId;
        $request->save();

        $grant = new ConsentGrant();
        $grant->consent_request_id = $request->id;
        $grant->patient_id = $request->patient_id;
        $grant->facility_id = $request->requesting_facility_id;
        $grant->authorizing_actor = 'patient';
        $grant->scope = $request->requested_scope;
        $grant->status = 'active';
        $grant->expires_at = Carbon::now()->addMinutes($request->duration_minutes);
        $grant->save();

        $this->audit(
            AuditEventType::ConsentGranted->value,
            $request,
            actorId: $userId,
            actorRole: 'patient',
            reason: $request->purpose,
            after: [
                'status'     => $request->status,
                'grant_id'   => $grant->id,
                'scope'      => $grant->scope,
                'expires_at' => $grant->expires_at->format(DATE_ATOM),
            ],
            grantId: $grant->id,
        );

        // Partner webhooks (consent.granted) — queued, signed, and never able to
        // fail the grant: the publisher wraps dispatch in rescue().
        \App\Modules\Connect\Services\WebhookEventPublisher::consentGranted($grant);

        return $grant;
    }

    /**
     * The patient says no. Terminal: nothing here or anywhere else writes the
     * row back to the open state. The facility may ask again later — the
     * partial unique index is restricted to the open statuses precisely so a
     * denial is not a permanent ban.
     */
    public function denyConsent(string $requestId, ?string $userId = null): ConsentRequest
    {
        $request = ConsentRequest::findOrFail($requestId);
        $this->assertOpen($request);

        // MobileGovernanceController::denyConsent() calls this with one
        // argument, so without the fallback below a refusal would be the only
        // state change in the loop with no actor on it — recorded, but
        // anonymous. The fallback reads the MIDDLEWARE-SET request attribute
        // and nothing else; see currentPatientUserIdFor().
        $userId ??= $this->currentPatientUserIdFor($request->patient_id);

        $request->status = self::STATUS_DENIED;
        $request->decided_at = Carbon::now();
        $request->decided_by = $userId;
        $request->save();

        $this->audit(
            AuditEventType::ConsentDenied->value,
            $request,
            actorId: $userId,
            actorRole: 'patient',
            reason: $request->purpose,
            after: ['status' => $request->status],
        );

        return $request;
    }

    /**
     * The FACILITY takes its own ask back — mistyped Health ID, patient walked
     * in and was seen anyway, wrong purpose.
     *
     * This exists because the unique index is deliberately strict: without a
     * way to close an open request, one mistake would lock the facility out of
     * asking this patient again until the request expired on its own.
     *
     * There is no facility check in here. The caller owns that: the controller
     * matches the request against the facility from $request->attributes before
     * it gets this far, the same way ownership is checked on the patient side.
     */
    public function withdrawConsentRequest(string $requestId, ?string $userId = null): ConsentRequest
    {
        $request = ConsentRequest::findOrFail($requestId);
        $this->assertOpen($request);

        $request->status = self::STATUS_WITHDRAWN;
        $request->decided_at = Carbon::now();
        $request->decided_by = $userId;
        $request->save();

        $this->audit(
            'consent_request_withdrawn',
            $request,
            actorId: $userId,
            actorRole: 'provider',
            reason: $request->purpose,
            after: ['status' => $request->status],
        );

        return $request;
    }

    /**
     * Retire open requests whose own clock ran out. Returns how many.
     *
     * Lazily driven rather than scheduled: it runs before a new ask and before
     * a list is served, so an expired request is never shown as pending and
     * never holds the unique index. A cron would also work; this needs no
     * worker to be correct, which matters on a host that has none.
     *
     * Rows with a NULL expires_at never lapse — that is a request with no
     * deadline, not a request that expired at the epoch.
     */
    public function expireStaleRequests(?string $patientId = null, ?string $facilityId = null): int
    {
        $query = ConsentRequest::query()
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now());

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }
        if ($facilityId !== null) {
            $query->where('requesting_facility_id', $facilityId);
        }

        $expired = 0;

        foreach ($query->get() as $request) {
            $request->status = self::STATUS_EXPIRED;
            $request->decided_at = Carbon::now();
            // decided_by stays null: nobody decided this, the clock did. That
            // is exactly why the audit trail is audit_events (nullable actor)
            // and not access_logs (actor_id NOT NULL).
            $request->save();

            $this->audit(
                'consent_request_expired',
                $request,
                actorId: null,
                actorRole: 'system',
                reason: $request->purpose,
                after: ['status' => $request->status],
            );

            $expired++;
        }

        return $expired;
    }

    /**
     * The live grant this facility holds over this patient, or null.
     *
     * Deliberately the SAME four predicates as
     * MobileDoctorChartController::hasActiveConsentGrant — patient, facility,
     * status 'active', expiry in the future. A caller using this to decide
     * "is there any point asking?" must be answering the same question the
     * chart will answer, or it will invite an ask that was already unnecessary
     * (or suppress one that was needed).
     */
    public function activeGrantFor(string $patientId, string $facilityId): ?ConsentGrant
    {
        return ConsentGrant::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->latest('expires_at')
            ->first();
    }

    /** The facility's currently open ask for this patient, if it has one. */
    public function openRequestFor(string $patientId, string $facilityId): ?ConsentRequest
    {
        return ConsentRequest::where('patient_id', $patientId)
            ->where('requesting_facility_id', $facilityId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->latest()
            ->first();
    }

    public function revokeConsent(string $grantId, string $userId): ConsentGrant
    {
        $grant = ConsentGrant::findOrFail($grantId);
        $grant->status = 'revoked';
        $grant->save();

        ConsentRevocation::create([
            'consent_grant_id' => $grant->id,
            'revoked_by' => $userId,
            'reason' => 'Patient revoked via mobile app'
        ]);

        $request = null;

        if ($grant->consent_request_id) {
            $request = ConsentRequest::find($grant->consent_request_id);
            if ($request && $request->status === self::STATUS_APPROVED) {
                $request->status = self::STATUS_EXPIRED;
                $request->save();
            }
        }

        $this->auditRow(
            AuditEventType::ConsentRevoked->value,
            patientId: $grant->patient_id,
            facilityId: $grant->facility_id,
            resourceId: $request?->id ?? $grant->id,
            resourceType: $request ? 'consent_request' : 'consent_grant',
            actorId: $userId,
            actorRole: 'patient',
            reason: $request?->purpose,
            after: ['status' => 'revoked', 'grant_id' => $grant->id],
            grantId: $grant->id,
        );

        return $grant;
    }

    public function verifyAccess(
        string $patientId,
        string $facilityId,
        ?string $userId,
        string $scope,
        string $purpose
    ): bool {
        $now = Carbon::now();

        $grants = ConsentGrant::where('consent_grants.patient_id', $patientId)
            ->where('consent_grants.facility_id', $facilityId)   // scope to requesting facility
            ->where('consent_grants.status', 'active')
            ->where('consent_grants.expires_at', '>=', $now)
            ->join('consent_requests', 'consent_grants.consent_request_id', '=', 'consent_requests.id')
            ->where('consent_requests.purpose', $purpose)
            ->select('consent_grants.*')
            ->get();

        foreach ($grants as $grant) {
            $scopes = $grant->scope ?? [];
            if (in_array($scope, $scopes) || in_array('*', $scopes)) {
                return true;
            }
        }

        return false;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Refuse to act on a request that is not open.
     *
     * A request whose own clock has run out is expired HERE rather than being
     * approved: a patient tapping an alert three weeks late must not resurrect
     * a lapsed ask. 409 rather than 404 because the row exists and the caller
     * is entitled to see it — it is the STATE that refuses, and the message
     * says which state.
     */
    private function assertOpen(ConsentRequest $request): void
    {
        if (in_array($request->status, self::OPEN_STATUSES, true)
            && $request->expires_at !== null
            && Carbon::parse($request->expires_at)->isPast()
        ) {
            $this->expireStaleRequests($request->patient_id, $request->requesting_facility_id);
            $request->refresh();
        }

        if (! in_array($request->status, self::OPEN_STATUSES, true)) {
            throw new ConflictHttpException(
                __('consent_requests.not_open', [
                    'status' => __('consent_requests.status.' . $request->status),
                ])
            );
        }
    }

    /**
     * The signed-in patient's users.id, but ONLY from the request attributes
     * the auth middleware stamped, and ONLY if that user really is this
     * patient's account.
     *
     * `patient_user_id` / `user_id` are written by AuthenticateMobilePatient
     * from the bearer token — the same trusted source every controller reads,
     * and the same one AccessLogService already reaches for when it stamps an
     * IP. Nothing here looks at the body, the query string, a header or the
     * session, so a caller cannot name the actor on their own refusal.
     *
     * The ownership check is what makes that safe rather than merely
     * conventional: a value that is not this patient's linked user is
     * discarded and the audit row keeps a null actor instead of a wrong one.
     */
    private function currentPatientUserIdFor(string $patientId): ?string
    {
        try {
            $attributes = request()?->attributes;

            if (! $attributes) {
                return null;
            }

            $userId = $attributes->get('user_id') ?? $attributes->get('patient_user_id');

            if (! is_string($userId) || $userId === '') {
                return null;
            }

            return User::where('id', $userId)->where('patient_id', $patientId)->exists()
                ? $userId
                : null;
        } catch (\Throwable) {
            // No request bound (console, queue) — an anonymous actor is the
            // honest answer, not a guess.
            return null;
        }
    }

    /**
     * Tell the patient, on both surfaces they might be reading.
     *
     * 1. `notifications` — Laravel's own store, which is the ONLY thing
     *    GET /api/mobile/notifications reads. This is the row a patient with a
     *    phone actually sees.
     * 2. `notification_events` — the staff/web bell plus the SMS/email
     *    fan-out, via the NotificationService call this method has always made.
     *
     * The recipient of (2) used to be the PATIENT id. notification_events keys
     * on `recipient_user_id`, so a row addressed to a patients.id was
     * addressed to nobody: it matched no user and appeared in no feed. It is
     * resolved to the linked users.id here, falling back to the patient id
     * when a patient has no user account (which is what the seeded governance
     * tests exercise).
     *
     * 3. The phone itself — one Expo push on the `consent` channel, so the
     *    handset plays the hospital chime and the app can raise its
     *    Approve/Deny prompt at once instead of waiting for the next poll of
     *    GET /mobile/consent-requests?status=pending. Copy is resolved in the
     *    PATIENT's language (App\Support\PatientPush::localeFor), and carries
     *    the facility name and the ask — never anything from the record.
     *
     * All three are best-effort. A notification failure must never undo a
     * consent request that is already committed — the patient's inbox
     * (GET /mobile/consent-requests) still shows it either way.
     */
    private function notifyPatientOfRequest(ConsentRequest $request): void
    {
        $facilityName = Facility::where('id', $request->requesting_facility_id)->value('name')
            ?? __('consent_requests.unknown_facility');

        $patient = null;

        try {
            $patient = Patient::find($request->patient_id);
            $patient?->notify(new ConsentAccessRequestedNotification(
                consentRequestId: $request->id,
                facilityName: $facilityName,
                purpose: $request->purpose,
                requestedScope: (array) $request->requested_scope,
                durationMinutes: (int) $request->duration_minutes,
                justification: $request->justification,
                requestExpiresAt: $request->expires_at
                    ? Carbon::parse($request->expires_at)->format(DATE_ATOM)
                    : null,
            ));
        } catch (\Throwable $e) {
            Log::warning('Consent request raised, but the patient notification could not be written.', [
                'consent_request_id' => $request->id,
                'error'              => $e->getMessage(),
            ]);
        }

        try {
            $patient ??= Patient::find($request->patient_id);

            if ($patient) {
                $locale = PatientPush::localeFor($patient);

                // PatientPush never throws and is a no-op when Expo push is
                // switched off; the try/catch guards the lookups around it.
                PatientPush::send(
                    $patient,
                    'consent_request',
                    __('push.consent_request.title', [], $locale),
                    __('push.consent_request.body', ['facility' => $facilityName], $locale),
                    [
                        'consent_request_id' => $request->id,
                        'facility_name'      => $facilityName,
                        'action_url'         => '/consent-requests',
                    ],
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Consent request raised, but the patient push could not be sent.', [
                'consent_request_id' => $request->id,
                'error'              => $e->getMessage(),
            ]);
        }

        try {
            $recipientId = User::where('patient_id', $request->patient_id)->value('id')
                ?? $request->patient_id;

            $this->notificationService->sendNotification(
                $recipientId,
                'consent.request.pending',
                [
                    'facility_name' => $facilityName,
                    'purpose'       => $request->purpose,
                ],
                'urgent',
                'account_and_security'
            );
        } catch (\Throwable) {
            // Never block consent request creation due to notification failure
        }
    }

    /** Audit a state change, taking the identifiers off the request row. */
    private function audit(
        string $actionType,
        ConsentRequest $request,
        ?string $actorId,
        string $actorRole,
        ?string $reason = null,
        array $after = [],
        ?string $grantId = null,
    ): void {
        $this->auditRow(
            $actionType,
            patientId: $request->patient_id,
            facilityId: $request->requesting_facility_id,
            resourceId: $request->id,
            resourceType: 'consent_request',
            actorId: $actorId,
            actorRole: $actorRole,
            reason: $reason,
            after: $after,
            grantId: $grantId,
        );
    }

    /**
     * One row in audit_events.
     *
     * Swallows its own failure on purpose. This is the opposite of the rule on
     * a chart READ — there, no trail means no disclosure, because the record
     * has not left the building yet. Here the state change is already
     * committed, and throwing would leave the caller believing their consent
     * decision failed while it had in fact been applied. A missing row is
     * logged at error level so it is not silent.
     */
    private function auditRow(
        string $actionType,
        ?string $patientId,
        ?string $facilityId,
        ?string $resourceId,
        string $resourceType,
        ?string $actorId,
        string $actorRole,
        ?string $reason = null,
        array $after = [],
        ?string $grantId = null,
    ): void {
        try {
            AuditEvent::create([
                'actor_id'         => $actorId,
                'actor_role'       => $actorRole,
                'facility_id'      => $facilityId,
                'patient_id'       => $patientId,
                'action_type'      => $actionType,
                'resource_type'    => $resourceType,
                'resource_id'      => $resourceId,
                'consent_grant_id' => $grantId,
                'source_system'    => 'opescare_finder',
                'reason'           => $reason,
                'after_state'      => $after,
                'created_at'       => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Consent state change could not be audited.', [
                'action_type'   => $actionType,
                'resource_id'   => $resourceId,
                'resource_type' => $resourceType,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
