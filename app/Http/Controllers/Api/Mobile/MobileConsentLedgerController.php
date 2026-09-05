<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ConsentGrant;
use App\Modules\Governance\Services\AccessLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mobile Patient API — the consent ledger: WHO CAN SEE MY RECORD RIGHT NOW.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE GAP THIS CLOSES
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The platform already had `POST /api/mobile/consents/{id}/revoke`, and no
 * endpoint anywhere that lists consent grants — so there was no way for a
 * patient to discover the `{id}` that revoke takes. The privacy screen made
 * do with `GET /api/mobile/consent-requests`, which lists consent REQUESTS
 * and reaches a grant only through `consent_requests.grant`. Every grant with
 * a null `consent_request_id` — which on this deployment is every grant that
 * exists — was therefore invisible: the screen said nobody had access while
 * two facilities held live, unexpired grants.
 *
 * `GET /api/mobile/access-logs` does not close that gap either. It answers
 * "who opened my record", in the past tense. This answers "who can open it",
 * in the present tense, and hands back the exact id revoke consumes.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHOSE RECORD — `patient_id` FROM THE TOKEN AND FROM NOWHERE ELSE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `patient_id` is read from `$request->attributes`, where
 * App\Http\Middleware\AuthenticateMobilePatient put it after verifying the
 * bearer token, and from nowhere else — not the body, not the query string,
 * not a header, not the session, and with no fallback. There is no route
 * parameter, no validator rule and no `$request->input()` call in this file
 * that names a patient, so a `patient_id` supplied by a caller is not
 * "overridden": it is never read. Every query below is filtered on that one
 * value, so one patient's ledger can never contain another patient's row.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * AN EXPIRED GRANT IS NOT AN ACTIVE ONE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `consent_grants.status` is written once and never swept: nothing in this
 * application flips a grant to `expired` when its `expires_at` passes (grep
 * for it — there is no command, no job and no scheduler entry). So a grant
 * whose clock ran out three months ago still reads `status = 'active'` in the
 * table. Trusting that column alone would render a dead grant identically to
 * a live one, which is the same class of bug as a retracted prescription
 * still reading "Active".
 *
 * So the state on the wire is DERIVED, in this order (see stateOf()):
 *
 *   revoked  — status says revoked. A decision the patient took.
 *   expired  — status says expired, OR the expiry has passed whatever the
 *              status column claims.
 *   active   — status says active AND the expiry is still ahead.
 *   ended    — any other status. Not guessed as active; filed under history
 *              carrying its raw status so it can be seen and fixed.
 *
 * Only `active` is returned in `data.active`. Everything else is history, in
 * its own array, with its own label and no revoke action.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ACCESS THAT IS NOT A GRANT IS NOT LISTED AS A GRANT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A consent grant is not the only thing that opens this record.
 * MobileDoctorChartController::relationshipBasis() admits a clinician on any
 * ONE of five bases, and four of them are care relationships rather than
 * consent: `appointment`, `care_team`, `treating_clinician`,
 * `facility_episode` — with `consent_grant` as the fifth and last resort.
 *
 * A patient who revokes every grant on this screen and then watches their
 * doctor keep reading the chart has been lied to. But inventing a
 * ConsentGrant row for a care relationship would be a worse lie, and it would
 * hand the client a revoke id that revokes nothing.
 *
 * The choice made here: care relationships are returned, in a THIRD array
 * (`data.care_relationships`) that is not `data.active`, every row carrying
 * `is_consent_grant: false`, `revocable: false`, a named basis and a sentence
 * saying why it exists — plus `meta.care_relationship_note`, which states in
 * the patient's language that these are not consent and that revoking consent
 * does not end them. Nothing in that array has an `id` that revoke accepts,
 * because nothing in it is a grant.
 *
 * The four bases are mirrored from the patient's side of exactly the same
 * tables the chart controller checks, in the same precedence order, so what a
 * patient sees here is what a clinician would actually resolve there.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * READING THIS SCREEN IS ITSELF A PATIENT-SCOPED READ
 * ─────────────────────────────────────────────────────────────────────────
 *
 * One `access_logs` row per call, via AccessLogService — the same audit the
 * rest of the mobile surface writes. Best-effort, for the reason
 * MedicalRecordExportController documents: `access_logs.actor_id` is NOT NULL
 * with a foreign key to `users`, so a patient with no linked user account
 * cannot be logged, and an audit failure must never turn a successful read of
 * the patient's OWN consent list into a 500. Unlike a clinician's chart read
 * — where no trail means no disclosure — this discloses nothing to anyone but
 * the patient themselves, so it degrades to a warning in the application log.
 */
class MobileConsentLedgerController extends Controller
{
    /**
     * An active grant expiring within this many days is flagged. Mirrors the
     * seven-day window the privacy screen already computed client-side, moved
     * here so every client agrees on what "expiring soon" means.
     */
    private const EXPIRING_SOON_DAYS = 7;

    /**
     * Mirrors MobileDoctorChartController::FACILITY_EPISODE_LOOKBACK_DAYS. If
     * that constant moves, this one has to move with it, or the patient is
     * shown a shorter window than the one that actually opens their chart.
     */
    private const FACILITY_EPISODE_LOOKBACK_DAYS = 365;

    /** Hard ceiling per scanned table. Reaching it sets meta.truncated. */
    private const SCAN_CAP = 200;

    /**
     * GET /api/mobile/consents
     *
     * Three arrays and nothing else:
     *
     *   data.active              live grants, newest expiry last, each with
     *                            the `id` that POST /mobile/consents/{id}/revoke
     *                            takes and a `revoke` block saying whether it
     *                            can actually be used.
     *   data.history             revoked, expired, or otherwise ended grants.
     *   data.care_relationships  access that exists WITHOUT a grant. Not
     *                            revocable, and never presented as consent.
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        if (! is_string($patientId) || $patientId === '') {
            return response()->json([
                'error'   => 'IDENTITY_UNRESOLVABLE',
                'message' => __('api.identity_unresolvable_patient'),
            ], 401);
        }

        $locale = $this->resolveLocale($request);
        $now    = Carbon::now();

        // The user account behind the token, when the patient has one. Used
        // only to say whether revoke will work and to attribute the audit row
        // — never for scoping, which is always $patientId above.
        $userId = $request->attributes->get('user_id')
            ?? $request->attributes->get('patient_user_id');
        $userId = is_string($userId) && $userId !== '' ? $userId : null;

        $grants = ConsentGrant::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->limit(self::SCAN_CAP)
            ->get();

        $revocations = $this->revocationsFor($grants->pluck('id')->all());

        // Who holds each grant, resolved OUTSIDE the Eloquent relations on
        // purpose. App\Models\Facility, App\Models\ConsentRequest and the
        // demo-isolation scope they carry hide any row in the other demo
        // bucket, so `$grant->facility` came back null for a live grant held
        // by a real facility — the patient was shown active access granted to
        // nobody, which is a worse answer than no row at all.
        //
        // The isolation invariant is not weakened by this: the demo scope
        // still decides which GRANTS this patient sees (the query above keeps
        // it). These lookups only name the counterparty of a row that scope
        // has already admitted, and that counterparty is the patient's own
        // information.
        $requests   = $this->requestsFor($grants->pluck('consent_request_id')->filter()->all());
        $facilities = $this->facilityRows($grants->pluck('facility_id')->filter()->unique()->all());
        $names      = $this->userNames(
            array_values(array_filter(array_map(fn ($r) => $r->requesting_user_id, $requests)))
        );

        $active  = [];
        $history = [];

        foreach ($grants as $grant) {
            $state = $this->stateOf($grant, $now);
            $row   = $this->formatGrant(
                $grant,
                $state,
                $revocations,
                $requests,
                $facilities,
                $names,
                $userId,
                $now,
                $locale
            );

            if ($state === 'active') {
                $active[] = $row;
            } else {
                $history[] = $row;
            }
        }

        // Soonest expiry first: the one the patient has least time to think
        // about is the one at the top.
        usort($active, fn ($a, $b) => ($a['expires_at'] ?? '') <=> ($b['expires_at'] ?? ''));

        // Most recently ended first.
        usort($history, fn ($a, $b) => ($b['ended_at'] ?? '') <=> ($a['ended_at'] ?? ''));

        $relationships = $this->careRelationships($patientId, $now, $locale);

        $expiringSoon = 0;
        foreach ($active as $row) {
            if ($row['expiring_soon']) {
                $expiringSoon++;
            }
        }

        $this->audit($patientId, $userId);

        return response()->json([
            'data' => [
                'active'              => $active,
                'history'             => $history,
                'care_relationships'  => $relationships['rows'],
            ],
            'meta' => [
                'active_count'             => count($active),
                'history_count'            => count($history),
                'care_relationship_count'  => count($relationships['rows']),
                'expiring_soon_count'      => $expiringSoon,
                'expiring_soon_days'       => self::EXPIRING_SOON_DAYS,
                // True when a scan hit its ceiling, so a client never reports
                // a count it cannot stand behind.
                'truncated'                => $grants->count() >= self::SCAN_CAP || $relationships['truncated'],
                'generated_at'             => $now->toISOString(),
                // Said on every response, not only when the array is
                // non-empty: "you have no other access" is a claim, and it has
                // to be as visible as the list itself.
                'care_relationship_note'   => __('consent_ledger.relationship_note', [], $locale),
                'revoke_endpoint_template' => '/api/mobile/consents/{id}/revoke',
            ],
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // Grants
    // ══════════════════════════════════════════════════════════════════

    /**
     * The state a patient is shown, derived from BOTH the status column and
     * the clock — see the class docblock for why the column alone is not
     * enough. An unrecognised status is never optimistically read as active.
     */
    private function stateOf(ConsentGrant $grant, Carbon $now): string
    {
        $status = is_string($grant->status) ? $grant->status : '';

        if ($status === 'revoked') {
            return 'revoked';
        }

        if ($status === 'expired') {
            return 'expired';
        }

        // `expires_at` is NOT NULL in the schema, but a grant with no expiry
        // is treated as finished rather than as permanent access.
        if ($grant->expires_at === null || $grant->expires_at->lte($now)) {
            return 'expired';
        }

        if ($status === 'active') {
            return 'active';
        }

        return 'ended';
    }

    /**
     * One grant, as the patient reads it: who holds it, what it opens, when it
     * was given, when it runs out, on whose authority it exists, and whether
     * it can be taken back right now.
     *
     * @param  array<string, object>                                       $revocations  keyed by consent_grant_id
     * @param  array<string, object>                                       $requests     keyed by consent_request_id
     * @param  array<string, array{id: string, name: ?string, type: ?string}>  $facilities  keyed by facility id
     * @param  array<string, string|null>                                  $names        user id => name
     */
    private function formatGrant(
        ConsentGrant $grant,
        string $state,
        array $revocations,
        array $requests,
        array $facilities,
        array $names,
        ?string $userId,
        Carbon $now,
        string $locale,
    ): array {
        $request      = $grant->consent_request_id ? ($requests[$grant->consent_request_id] ?? null) : null;
        $clinicianId  = $request?->requesting_user_id;
        $revocation   = $revocations[$grant->id] ?? null;
        $expiresAt    = $grant->expires_at;
        $isActive     = $state === 'active';

        $daysLeft = $isActive && $expiresAt
            ? (int) floor($now->floatDiffInDays($expiresAt, false))
            : null;

        $revokeBlock = $this->revokeBlock($state, $userId, $locale);

        return [
            // The id POST /api/mobile/consents/{id}/revoke takes. Named
            // `consent_grant_id` as well as `id` so no client has to guess
            // which identifier the revoke route wants.
            'id'               => $grant->id,
            'consent_grant_id' => $grant->id,

            'is_consent_grant' => true,
            'state'            => $state,
            'state_label'      => __("consent_ledger.state.$state", [], $locale),
            'state_detail'     => __("consent_ledger.state_detail.$state", [], $locale),
            'is_active'        => $isActive,

            // Raw status kept alongside the derived state precisely so the
            // divergence this controller exists to handle stays visible: an
            // `expired` state over a `raw_status` of `active` is the unswept
            // column, not a bug in this response.
            'raw_status'       => $grant->status,

            'facility' => $facilities[$grant->facility_id] ?? null,

            // Only when the grant's originating request actually named a
            // person. A facility-wide grant names no clinician, and null here
            // means exactly that — it is never filled with "the facility" or
            // with whoever happened to read the record last.
            'clinician' => $clinicianId ? [
                'id'   => $clinicianId,
                'name' => $names[$clinicianId] ?? null,
            ] : null,

            'scope'   => is_array($grant->scope) ? array_values($grant->scope) : [],
            'purpose' => $request?->purpose,

            // WHY this grant exists at all. `facility_policy` is the one a
            // patient most needs to see: it was not granted by them.
            'basis' => [
                'authorizing_actor' => $grant->authorizing_actor,
                'label'             => $this->actorLabel($grant->authorizing_actor, $locale),
                'detail'            => $this->actorDetail($grant->authorizing_actor, $locale),
                'source'            => $grant->consent_request_id ? 'consent_request' : 'direct',
                'source_label'      => $grant->consent_request_id
                    ? __('consent_ledger.source.consent_request', [], $locale)
                    : __('consent_ledger.source.direct', [], $locale),
                'consent_request_id' => $grant->consent_request_id,
            ],

            'granted_at'    => $grant->created_at?->toISOString(),
            'expires_at'    => $expiresAt?->toISOString(),
            'days_left'     => $daysLeft,
            'expiring_soon' => $isActive && $daysLeft !== null && $daysLeft <= self::EXPIRING_SOON_DAYS,

            // When access actually stopped. For a revoked grant that is the
            // revocation row; for an expired one it is the expiry itself.
            // Null while the grant is live.
            'ended_at' => $isActive
                ? null
                : ($revocation?->created_at ?? $expiresAt?->toISOString()),

            'revocation' => $revocation ? [
                'at'      => $revocation->created_at,
                // Whether the patient ended this themselves, or someone else
                // did. Both happen; conflating them would hide the second.
                'by_you'  => $userId !== null && $revocation->revoked_by === $userId,
                'reason'  => $revocation->reason,
            ] : null,

            'revoke' => $revokeBlock,
        ];
    }

    /**
     * Whether revoke can be used on this row, and if not, why not — in words.
     *
     * MobileGovernanceController::revokeConsent resolves the acting user from
     * the request attributes and answers 401 IDENTITY_UNRESOLVABLE when the
     * patient has no linked `users` row. A patient in that position must not
     * be shown a button that always fails; they are told what is wrong so
     * they can ask for it to be fixed.
     */
    private function revokeBlock(string $state, ?string $userId, string $locale): array
    {
        if ($state !== 'active') {
            return [
                'available' => false,
                'reason'    => 'not_active',
                'message'   => __('consent_ledger.revoke_unavailable.not_active', [], $locale),
            ];
        }

        if ($userId === null) {
            return [
                'available' => false,
                'reason'    => 'no_linked_user',
                'message'   => __('consent_ledger.revoke_unavailable.no_linked_user', [], $locale),
            ];
        }

        return [
            'available' => true,
            'reason'    => null,
            'message'   => __('consent_ledger.revoke_available', [], $locale),
        ];
    }

    /**
     * The earliest revocation recorded against each of these grants, keyed by
     * grant id.
     *
     * A separate query rather than a relation: App\Models\ConsentGrant has no
     * `revocations` relation and this feature does not get to add one to a
     * model three other flows read.
     *
     * @param  array<int, string>  $grantIds
     * @return array<string, object>
     */
    private function revocationsFor(array $grantIds): array
    {
        if ($grantIds === []) {
            return [];
        }

        $rows = DB::table('consent_revocations')
            ->whereIn('consent_grant_id', $grantIds)
            ->orderBy('created_at')
            ->get(['consent_grant_id', 'revoked_by', 'reason', 'created_at']);

        $byGrant = [];

        foreach ($rows as $row) {
            // First revocation wins — a grant is revoked once; anything after
            // is a duplicate, not a second ending.
            if (! isset($byGrant[$row->consent_grant_id])) {
                $byGrant[$row->consent_grant_id] = (object) [
                    'revoked_by' => $row->revoked_by,
                    'reason'     => $row->reason,
                    'created_at' => $row->created_at
                        ? Carbon::parse($row->created_at)->toISOString()
                        : null,
                ];
            }
        }

        return $byGrant;
    }

    /**
     * The originating consent requests for these grants, keyed by id.
     *
     * Read off the table rather than through App\Models\ConsentRequest for the
     * same reason the facility lookup is: that model carries the demo
     * isolation scope, and a grant whose request sits in the other bucket
     * would silently lose its purpose and the clinician it named.
     *
     * @param  array<int, string>  $ids
     * @return array<string, object>
     */
    private function requestsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('consent_requests')
            ->whereIn('id', $ids)
            ->get(['id', 'purpose', 'requesting_user_id'])
            ->keyBy('id')
            ->all();
    }

    // ══════════════════════════════════════════════════════════════════
    // Access that is NOT a grant
    // ══════════════════════════════════════════════════════════════════

    /**
     * The care relationships that open this patient's chart without any
     * consent grant — bases 1 to 4 of
     * MobileDoctorChartController::relationshipBasis(), read from the
     * patient's side of the same tables and resolved in the same precedence
     * order, so a clinician appears here under the basis that would actually
     * admit them there.
     *
     * Clinician-level bases (1-3) are deduplicated per clinician, strongest
     * first, exactly as relationshipBasis() returns on first match. Basis 4 is
     * facility-level by nature: an open episode at a facility admits ANY
     * clinician holding that facility's token, so it is reported as the
     * facility it is, with no clinician named.
     *
     * Nothing in here carries a revocable id, because none of it is consent.
     *
     * @return array{rows: array<int, array<string, mixed>>, truncated: bool}
     */
    private function careRelationships(string $patientId, Carbon $now, string $locale): array
    {
        $truncated = false;

        /** @var array<string, array{basis: string, facility_id: ?string, at: ?string}> $byClinician */
        $byClinician = [];

        // Precedence is the whole point: a clinician who is both on the
        // schedule and on the care team is admitted as `appointment` there,
        // so they are shown as `appointment` here.
        $claim = function (?string $providerId, string $basis, ?string $facilityId, ?string $at) use (&$byClinician): void {
            if (! is_string($providerId) || $providerId === '') {
                return;
            }
            if (isset($byClinician[$providerId])) {
                return;
            }
            $byClinician[$providerId] = ['basis' => $basis, 'facility_id' => $facilityId, 'at' => $at];
        };

        // 1. On this clinician's own schedule.
        $appointments = DB::table('appointments')
            ->where('patient_id', $patientId)
            ->whereNotNull('provider_id')
            ->orderByDesc('scheduled_at')
            ->limit(self::SCAN_CAP)
            ->get(['provider_id', 'facility_id', 'scheduled_at']);
        $truncated = $truncated || $appointments->count() >= self::SCAN_CAP;
        foreach ($appointments as $row) {
            $claim($row->provider_id, 'appointment', $row->facility_id, $this->iso($row->scheduled_at));
        }

        // 2. A current member of the care team.
        $careTeam = DB::table('care_team_members')
            ->where('patient_id', $patientId)
            ->where('is_active', true)
            ->whereNull('left_at')
            ->whereNotNull('provider_id')
            ->orderByDesc('joined_at')
            ->limit(self::SCAN_CAP)
            ->get(['provider_id', 'facility_id', 'joined_at']);
        $truncated = $truncated || $careTeam->count() >= self::SCAN_CAP;
        foreach ($careTeam as $row) {
            $claim($row->provider_id, 'care_team', $row->facility_id, $this->iso($row->joined_at));
        }

        // 3. A clinician who personally did something clinical for this
        //    patient. Same five tables and the same columns the chart
        //    controller ORs together.
        $treating = [
            ['encounters',     'attending_provider_id', 'started_at'],
            ['visits',         'provider_id',           'started_at'],
            ['prescriptions',  'prescribed_by',         'prescribed_at'],
            ['lab_orders',     'ordered_by',            'ordered_at'],
            ['imaging_orders', 'ordered_by',            'ordered_at'],
        ];

        foreach ($treating as [$table, $providerColumn, $atColumn]) {
            $rows = DB::table($table)
                ->where('patient_id', $patientId)
                ->whereNotNull($providerColumn)
                ->orderByDesc($atColumn)
                ->limit(self::SCAN_CAP)
                ->get([$providerColumn . ' as provider_id', 'facility_id', $atColumn . ' as at']);
            $truncated = $truncated || $rows->count() >= self::SCAN_CAP;
            foreach ($rows as $row) {
                $claim($row->provider_id, 'treating_clinician', $row->facility_id, $this->iso($row->at));
            }
        }

        $names      = $this->userNames(array_keys($byClinician));
        $facilities = [];
        foreach ($byClinician as $entry) {
            if ($entry['facility_id']) {
                $facilities[$entry['facility_id']] = true;
            }
        }

        // 4. An episode of care at a facility — open, still ahead, or recent
        //    enough to still be care rather than history. This one is not
        //    about a named person: it admits every clinician carrying that
        //    facility's token.
        $episodeFacilities = $this->facilityEpisodes($patientId, $now);
        foreach (array_keys($episodeFacilities) as $facilityId) {
            $facilities[$facilityId] = true;
        }

        $facilityRows = $this->facilityRows(array_keys($facilities));

        $rows = [];

        foreach ($byClinician as $providerId => $entry) {
            $rows[] = $this->relationshipRow(
                basis: $entry['basis'],
                clinician: isset($names[$providerId])
                    ? ['id' => $providerId, 'name' => $names[$providerId]]
                    : ['id' => $providerId, 'name' => null],
                facility: $facilityRows[$entry['facility_id']] ?? null,
                since: $entry['at'],
                locale: $locale,
            );
        }

        foreach ($episodeFacilities as $facilityId => $at) {
            $rows[] = $this->relationshipRow(
                basis: 'facility_episode',
                clinician: null,
                facility: $facilityRows[$facilityId] ?? null,
                since: $at,
                locale: $locale,
            );
        }

        // Strongest relationship first, then most recent — the same order a
        // clinician's own access would be resolved in.
        $order = ['appointment' => 0, 'care_team' => 1, 'treating_clinician' => 2, 'facility_episode' => 3];
        usort($rows, function ($a, $b) use ($order) {
            $byBasis = ($order[$a['basis']] ?? 9) <=> ($order[$b['basis']] ?? 9);

            return $byBasis !== 0 ? $byBasis : (($b['since'] ?? '') <=> ($a['since'] ?? ''));
        });

        return ['rows' => $rows, 'truncated' => $truncated];
    }

    /**
     * One care-relationship row. `is_consent_grant` is false and `revocable`
     * is false on every single one, and there is deliberately no `id` field:
     * nothing here is a grant, so nothing here may be handed to revoke.
     */
    private function relationshipRow(
        string $basis,
        ?array $clinician,
        ?array $facility,
        ?string $since,
        string $locale,
    ): array {
        return [
            'is_consent_grant' => false,
            'revocable'        => false,
            'basis'            => $basis,
            'basis_label'      => __("consent_ledger.relationship.$basis", [], $locale),
            'basis_detail'     => __("consent_ledger.relationship_detail.$basis", [], $locale),
            'clinician'        => $clinician,
            'facility'         => $facility,
            'since'            => $since,
        ];
    }

    /**
     * Facilities where this patient has an episode of care that is still open,
     * still ahead, or began inside the lookback window — the patient-side
     * mirror of MobileDoctorChartController::hasFacilityEpisode().
     *
     * ALL FOUR of that method's sources are checked here, not just the obvious
     * two. It admits a facility on an open/recent visit, an open/recent
     * encounter, a recent APPOINTMENT, or a recent PRESCRIPTION at that
     * facility. Mirroring only visits and encounters would under-report — the
     * patient would be shown fewer facilities than can actually open their
     * chart, which is the one direction this screen must never be wrong in.
     *
     * @return array<string, string|null>  facility id => most recent tie
     */
    private function facilityEpisodes(string $patientId, Carbon $now): array
    {
        $since = $now->copy()->subDays(self::FACILITY_EPISODE_LOOKBACK_DAYS);
        $found = [];

        $note = function (?string $facilityId, mixed $at) use (&$found): void {
            if (! is_string($facilityId) || $facilityId === '') {
                return;
            }
            $iso = $this->iso($at);
            if (! array_key_exists($facilityId, $found) || ($iso ?? '') > ($found[$facilityId] ?? '')) {
                $found[$facilityId] = $iso;
            }
        };

        // Open now, or begun recently enough to still be care rather than history.
        foreach (['visits', 'encounters'] as $table) {
            $rows = DB::table($table)
                ->where('patient_id', $patientId)
                ->whereNotNull('facility_id')
                ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
                ->orderByDesc('started_at')
                ->limit(self::SCAN_CAP)
                ->get(['facility_id', 'started_at']);

            foreach ($rows as $row) {
                $note($row->facility_id, $row->started_at);
            }
        }

        // A recent appointment at the facility — including one still ahead.
        $appointments = DB::table('appointments')
            ->where('patient_id', $patientId)
            ->whereNotNull('facility_id')
            ->where('scheduled_at', '>=', $since)
            ->orderByDesc('scheduled_at')
            ->limit(self::SCAN_CAP)
            ->get(['facility_id', 'scheduled_at']);

        foreach ($appointments as $row) {
            $note($row->facility_id, $row->scheduled_at);
        }

        // A recent prescription written at the facility.
        $prescriptions = DB::table('prescriptions')
            ->where('patient_id', $patientId)
            ->whereNotNull('facility_id')
            ->where('prescribed_at', '>=', $since)
            ->orderByDesc('prescribed_at')
            ->limit(self::SCAN_CAP)
            ->get(['facility_id', 'prescribed_at']);

        foreach ($prescriptions as $row) {
            $note($row->facility_id, $row->prescribed_at);
        }

        return $found;
    }

    /**
     * Names for a set of clinician user ids. A clinician whose user row has
     * gone returns a null name rather than a placeholder — "not recorded", not
     * "Unknown Doctor".
     *
     * @param  array<int, string>  $ids
     * @return array<string, string|null>
     */
    private function userNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<string, array{id: string, name: ?string, type: ?string}>
     */
    private function facilityRows(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('facilities')
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'type']);

        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->id] = ['id' => $row->id, 'name' => $row->name, 'type' => $row->type];
        }

        return $byId;
    }

    // ══════════════════════════════════════════════════════════════════
    // Plumbing
    // ══════════════════════════════════════════════════════════════════

    private function actorLabel(?string $actor, string $locale): string
    {
        return match ($actor) {
            'patient'         => __('consent_ledger.basis.patient', [], $locale),
            'guardian'        => __('consent_ledger.basis.guardian', [], $locale),
            'facility_policy' => __('consent_ledger.basis.facility_policy', [], $locale),
            default           => __('consent_ledger.basis.unknown', [], $locale),
        };
    }

    private function actorDetail(?string $actor, string $locale): string
    {
        return match ($actor) {
            'patient'         => __('consent_ledger.basis_detail.patient', [], $locale),
            'guardian'        => __('consent_ledger.basis_detail.guardian', [], $locale),
            'facility_policy' => __('consent_ledger.basis_detail.facility_policy', [], $locale),
            default           => __('consent_ledger.basis_detail.unknown', [], $locale),
        };
    }

    /** A timestamp of any shape from the query builder, as ISO-8601 or null. */
    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * EN or FR for this request. Same resolution the patient-write endpoints
     * use: these are stateless API calls with no session, so the `web` stack's
     * SetLocale middleware never runs on them.
     */
    private function resolveLocale(Request $request): string
    {
        $supported = ['en', 'fr'];

        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $supported, true)) {
            return $requested;
        }

        $header  = (string) $request->header('Accept-Language', '');
        $primary = strtolower(substr(trim(explode(',', $header)[0] ?? ''), 0, 2));
        if (in_array($primary, $supported, true)) {
            return $primary;
        }

        return in_array(App::getLocale(), $supported, true) ? App::getLocale() : 'en';
    }

    /**
     * Record that the patient looked at their own consent ledger.
     *
     * Best-effort — see the class docblock. Without a linked user account
     * there is no `actor_id` to write, and this read discloses nothing to
     * anybody but the patient who made it, so a failure is a log line rather
     * than a refusal.
     */
    private function audit(string $patientId, ?string $userId): void
    {
        if ($userId === null) {
            Log::warning('Consent ledger read not access-logged: no linked user account.', [
                'patient_id' => $patientId,
            ]);

            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $userId,
                actorType: 'patient',
                organizationId: null,
                facilityId: null,
                purpose: 'patient_consent_review',
                dataCategory: 'consent',
                resourceType: 'ConsentGrant',
                resourceId: null,
                accessType: 'view',
            );
        } catch (\Throwable $e) {
            Log::warning('Consent ledger access log failed.', [
                'patient_id' => $patientId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
