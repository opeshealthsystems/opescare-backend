<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Appointment;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\EmergencyAccessEvent;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Patient\Passport\PageSource;
use App\Support\ClinicalRelationshipGate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Page 23 — who can see this record, on what basis, since when, until when.
 *
 * THIS IS THE PAGE WHERE BEING WRONG DOES THE QUIETEST DAMAGE. Every other
 * page can be incomplete and the reader simply learns less than they hoped. A
 * patient who reads "nobody has access" while a permission is live has been
 * told something false about their own privacy, and they will make decisions
 * on it. So this page does not list consent grants and stop there: it lists
 * EVERY basis on which somebody may reach the chart.
 *
 * WHAT ACTUALLY CONFERS ACCESS. `App\Support\ClinicalRelationshipGate` is the
 * one place that answers "may this clinician open this chart", and it names
 * five bases. Three of them are things a patient would recognise as a person
 * or an organisation seeing their record, and they are the three this page
 * reports alongside the consent grants:
 *
 *   consent_grant      the patient (or their guardian) said yes to a facility
 *   care_team          a current, un-left member of the patient's care team
 *   facility_episode   a facility where the patient has an episode of care
 *                      that is open, still ahead, or began within
 *                      ClinicalRelationshipGate::FACILITY_EPISODE_LOOKBACK_DAYS
 *
 * The remaining two — `appointment` and `treating_clinician` — are individual
 * clinicians reached through the same facilities and encounters the page
 * already names, and listing every clinician who ever wrote a line would bury
 * the standing permissions that are the point of the page. They are noted here
 * so the omission is a decision and not an oversight.
 *
 * Two more routes exist outside the gate and are shown in their own right:
 *
 *   guardian           a FamilyLink gives a guardian standing access to a
 *                      dependent's record — no clinician involved at all
 *   break-glass        EmergencyAccessService opens a four-hour override with
 *                      no consent whatsoever. Its own red block, because an
 *                      override nobody agreed to must not sit in a list of
 *                      permissions the patient granted.
 *
 * ENDED MUST READ AS ENDED. A grant whose row still says `active` but whose
 * `expires_at` has passed confers nothing — ClinicalRelationshipGate requires
 * `expires_at >= now` — so this page reads it as expired rather than repeating
 * the stale column. Where a permission genuinely has no end date the cell says
 * so instead of being left blank, because a blank "until" is exactly the sort
 * of silence a reader fills in optimistically.
 *
 * WHAT THE DESIGN SHEET ASKS FOR AND THE RECORD CANNOT GIVE. The sheet opens
 * with a per-category sharing matrix (personal information / medications /
 * imaging × providers / insurance / government / research). No such
 * preferences exist in this deployment: `patients.privacy_preferences` holds
 * two booleans, neither of which is read by any access-control path in this
 * codebase, so drawing them here as consent settings would tell a patient they
 * control something they do not. The sheet's "Your rights" and "Manage
 * consent" bullets are declaratory legal text rather than record content, and
 * the sheet's per-grant "Data access" column would need a translated
 * vocabulary for the raw scope tokens (`patient.summary`, `allergies.read`)
 * that does not exist yet. All three are named in the build report rather than
 * invented here.
 */
class ConsentSharingPage extends PageSource
{
    /**
     * Reading order of the bases inside "who has access": what the patient
     * granted, then who stands in for them, then the platform's own rules.
     */
    private const GROUP_GRANT            = 0;
    private const GROUP_GUARDIAN         = 1;
    private const GROUP_CARE_TEAM        = 2;
    private const GROUP_FACILITY_EPISODE = 3;

    /**
     * Family-link statuses, and how each should read.
     *
     * `pending_invite` is deliberately amber and not green: an invitation
     * nobody has accepted is not access, and colouring it like a live
     * permission would overstate what is happening to the record.
     */
    private const LINK_TONES = [
        'active'         => 'success',
        'pending_invite' => 'warning',
        'revoked'        => 'danger',
        'expired'        => 'danger',
        'cancelled'      => 'neutral',
        'declined'       => 'neutral',
    ];

    /** Consent-request statuses, and how each should read. */
    private const REQUEST_TONES = [
        'pending_patient_approval' => 'warning',
        'pending'                  => 'warning',
        'approved'                 => 'success',
        'denied'                   => 'danger',
        'expired'                  => 'danger',
        'withdrawn'                => 'neutral',
    ];

    public function build(Patient $patient): array
    {
        $sections = [];

        if ($access = $this->whoHasAccess($patient)) {
            $sections[] = $access;
        }

        if ($emergency = $this->emergencyOverrides($patient)) {
            $sections[] = $emergency;
        }

        if ($requests = $this->consentRequests($patient)) {
            $sections[] = $requests;
        }

        return $sections;
    }

    // ── 1. Who has access ────────────────────────────────────────────────

    /**
     * Every standing permission over this record, live ones first.
     *
     * The `type` column is the BASIS — why this party can see the record —
     * and it is filled from what the row itself recorded (the purpose the
     * facility asked for, the guardian's relationship, the clinician's care
     * team role) rather than from a vocabulary this page made up.
     */
    private function whoHasAccess(Patient $patient): ?array
    {
        $rows = array_merge(
            $this->grantRows($patient),
            $this->guardianRows($patient),
            $this->careTeamRows($patient),
            $this->facilityEpisodeRows($patient),
        );

        if ($rows === []) {
            return null;
        }

        // Live permissions first — a patient scanning this page is asking
        // "who can see it now", not "who ever could" — then by basis, so the
        // permissions the patient granted themselves head the list and the
        // ones the platform confers follow, each group newest first.
        usort($rows, function (array $a, array $b) {
            if ($a['live'] !== $b['live']) {
                return $a['live'] ? -1 : 1;
            }

            if ($a['group'] !== $b['group']) {
                return $a['group'] <=> $b['group'];
            }

            return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0);
        });

        return $this->table(
            __('passport.sections.who_has_access'),
            [
                __('passport.columns.who'),
                __('passport.columns.type'),
                __('passport.columns.granted'),
                __('passport.columns.expires'),
                __('passport.columns.status'),
            ],
            array_map(fn (array $r) => $r['cells'], $rows),
            'green',
            'shield-check'
        );
    }

    /**
     * Consent grants — the permissions the patient actually gave.
     *
     * Revoked and lapsed grants are kept and marked, not hidden: "this
     * hospital had access until March" is information, and a list that
     * silently drops what ended reads as though it never happened.
     */
    private function grantRows(Patient $patient): array
    {
        $grants = ConsentGrant::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($grants->isEmpty()) {
            return [];
        }

        $facilities = $this->facilityNames($grants->pluck('facility_id')->all());
        $purposes   = $this->requestPurposes($grants->pluck('consent_request_id')->all());

        return $grants->map(function (ConsentGrant $grant) use ($facilities, $purposes) {
            $expiresAt = $grant->expires_at ? Carbon::parse($grant->expires_at) : null;
            $status    = strtolower(trim((string) $grant->status));

            // The row can say `active` and still be dead: the gate requires
            // expires_at >= now, so a lapsed grant is expired whatever the
            // column says. Read it the way access control reads it.
            if ($status === 'active' && $expiresAt !== null && $expiresAt->isPast()) {
                $status = 'expired';
            }

            $live = $status === 'active';

            // The basis, in the words the record used: what the facility asked
            // for, or failing that on whose authority the grant stands.
            $type = $purposes[$grant->consent_request_id] ?? null;
            $type = $type !== null && trim($type) !== ''
                ? $this->statusLabel($type)
                : $this->statusLabel((string) $grant->authorizing_actor);

            return [
                'live'  => $live,
                'group' => self::GROUP_GRANT,
                'sort'  => $grant->created_at ? Carbon::parse($grant->created_at)->getTimestamp() : 0,
                'cells' => [
                    $this->cell((string) ($facilities[$grant->facility_id] ?? __('passport.a_facility'))),
                    $this->cell($type),
                    $this->cell($this->date($grant->created_at)),
                    $this->cell($expiresAt ? $this->date($expiresAt) : __('passport.none_recorded')),
                    $this->cell($this->statusLabel($status), $this->statusTone($status)),
                ],
            ];
        })->all();
    }

    /**
     * Guardians holding standing access to this record.
     *
     * A family link is the one permission on this page that needs no clinician
     * and no facility: another person can open the whole record from their own
     * app. Pending invitations are shown too — an invitation the dependent has
     * not accepted is not access, and its status says so, but a patient is
     * entitled to know one is outstanding in their name.
     */
    private function guardianRows(Patient $patient): array
    {
        $links = FamilyLink::where('dependent_patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($links->isEmpty()) {
            return [];
        }

        $guardians = $this->userNames($links->pluck('guardian_user_id')->all());

        return $links->map(function (FamilyLink $link) use ($guardians) {
            $live = $link->grantsAccess();

            // `status` alone would call an age-lapsed link active. It is not:
            // FamilyLink::grantsAccess() stops honouring it once the
            // dependent's eighteenth-birthday grace period runs out.
            $status = $link->isActive() && $link->isExpiredByAge()
                ? 'expired'
                : strtolower(trim((string) $link->status));

            $since = $link->dependent_consented_at ?: $link->created_at;

            return [
                'live'  => $live,
                'group' => self::GROUP_GUARDIAN,
                'sort'  => $since ? Carbon::parse($since)->getTimestamp() : 0,
                'cells' => [
                    $this->cell((string) ($guardians[$link->guardian_user_id] ?? '')),
                    $this->cell($this->statusLabel((string) $link->relationship)),
                    $this->cell($this->date($since)),
                    $this->cell(
                        $link->age_transition_expires_at
                            ? $this->date($link->age_transition_expires_at)
                            : __('passport.none_recorded')
                    ),
                    $this->cell(
                        $this->statusLabel($status),
                        self::LINK_TONES[$status] ?? $this->statusTone($status)
                    ),
                ],
            ];
        })->all();
    }

    /**
     * Current care-team members — basis 2 of the gate.
     *
     * Only current membership is listed, and on exactly the terms the gate
     * honours it: active and not left. A clinician who has left the team no
     * longer reaches the chart on that basis, so listing them here would
     * overstate who can see the record.
     */
    private function careTeamRows(Patient $patient): array
    {
        $members = CareTeamMember::where('patient_id', $patient->id)
            ->where('is_active', true)
            ->whereNull('left_at')
            ->orderByDesc('joined_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($members->isEmpty()) {
            return [];
        }

        $providers = $this->userNames($members->pluck('provider_id')->all());

        return $members->map(fn (CareTeamMember $member) => [
            'live'  => true,
            'group' => self::GROUP_CARE_TEAM,
            'sort'  => $member->joined_at ? Carbon::parse($member->joined_at)->getTimestamp() : 0,
            'cells' => [
                $this->cell((string) ($providers[$member->provider_id] ?? __('passport.care_team_member'))),
                $this->cell(
                    trim((string) $member->role) !== ''
                        ? $this->statusLabel((string) $member->role)
                        : __('passport.care_team_member')
                ),
                $this->cell($this->date($member->joined_at)),
                $this->cell(__('passport.none_recorded')),
                $this->cell($this->statusLabel('active'), 'success'),
            ],
        ])->all();
    }

    /**
     * Facilities that can reach the record through an episode of care.
     *
     * This is the basis patients never expect and the one they would most want
     * told: a hospital they attended can still open the chart, without asking
     * again, for a year after the visit — and indefinitely while an episode is
     * still open. The window and the four sources are the gate's own
     * (ClinicalRelationshipGate::hasFacilityEpisode), so what this page says is
     * exactly what access control does, not a second opinion about it.
     *
     * A facility already listed under a consent grant is listed again here
     * when an episode also holds. That is deliberate: the two end on different
     * days, and a patient who reads only the grant would believe access stops
     * when it expires.
     */
    private function facilityEpisodeRows(Patient $patient): array
    {
        $episodes = $this->facilityEpisodes($patient);

        if ($episodes === []) {
            return [];
        }

        // Most recent contact first, then the page's usual recency cap.
        uasort($episodes, fn (array $a, array $b) => ($b['last']?->getTimestamp() ?? 0) <=> ($a['last']?->getTimestamp() ?? 0));
        $episodes = array_slice($episodes, 0, self::RECENT_LIMIT, true);

        $facilities = $this->facilityNames(array_keys($episodes));
        $rows       = [];

        foreach ($episodes as $facilityId => $episode) {
            // An open episode has no end, so the access it confers has none
            // either. A closed one lapses a lookback after the last contact —
            // the gate's rule, applied to a recorded date, not a guess.
            $expires = $episode['open'] || $episode['last'] === null
                ? __('passport.none_recorded')
                : $this->date($episode['last']->copy()->addDays(ClinicalRelationshipGate::FACILITY_EPISODE_LOOKBACK_DAYS));

            $rows[] = [
                'live'  => true,
                'group' => self::GROUP_FACILITY_EPISODE,
                'sort'  => $episode['last']?->getTimestamp() ?? 0,
                'cells' => [
                    $this->cell((string) ($facilities[$facilityId] ?? __('passport.a_facility'))),
                    $this->cell(__('passport.sections.encounters')),
                    $this->cell($this->date($episode['first'])),
                    $this->cell($expires),
                    $this->cell($this->statusLabel('active'), 'success'),
                ],
            ];
        }

        return $rows;
    }

    /**
     * The facilities holding a live episode of care, with when it began, when
     * it was last touched, and whether any part of it is still open.
     *
     * Mirrors ClinicalRelationshipGate::hasFacilityEpisode source for source —
     * visits, encounters, appointments and prescriptions, the same lookback,
     * the same open-episode exemption — because a page that told the patient a
     * different set of facilities than the gate honours would be worse than
     * showing none.
     *
     * @return array<string,array{first:?Carbon,last:?Carbon,open:bool}>
     */
    private function facilityEpisodes(Patient $patient): array
    {
        $since    = Carbon::now()->subDays(ClinicalRelationshipGate::FACILITY_EPISODE_LOOKBACK_DAYS);
        $episodes = [];

        $note = function (?string $facilityId, $timestamp, bool $open) use (&$episodes): void {
            if (! $facilityId) {
                return;
            }

            $when = null;

            if ($timestamp) {
                try {
                    $when = Carbon::parse($timestamp);
                } catch (\Throwable) {
                    $when = null;
                }
            }

            $current = $episodes[$facilityId] ?? ['first' => null, 'last' => null, 'open' => false];

            if ($when !== null) {
                if ($current['first'] === null || $when->lt($current['first'])) {
                    $current['first'] = $when;
                }

                if ($current['last'] === null || $when->gt($current['last'])) {
                    $current['last'] = $when;
                }
            }

            $current['open'] = $current['open'] || $open;

            $episodes[$facilityId] = $current;
        };

        DB::table('visits')
            ->where('patient_id', $patient->id)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->get(['facility_id', 'started_at', 'ended_at'])
            ->each(fn ($v) => $note($v->facility_id, $v->started_at, $v->ended_at === null));

        Encounter::where('patient_id', $patient->id)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->get(['facility_id', 'started_at', 'ended_at'])
            ->each(fn (Encounter $e) => $note($e->facility_id, $e->started_at, $e->ended_at === null));

        Appointment::where('patient_id', $patient->id)
            ->where('scheduled_at', '>=', $since)
            ->get(['facility_id', 'scheduled_at'])
            ->each(fn (Appointment $a) => $note($a->facility_id, $a->scheduled_at, false));

        Prescription::where('patient_id', $patient->id)
            ->where('prescribed_at', '>=', $since)
            ->get(['facility_id', 'prescribed_at'])
            ->each(fn (Prescription $p) => $note($p->facility_id, $p->prescribed_at, false));

        return $episodes;
    }

    // ── 2. Emergency overrides ───────────────────────────────────────────

    /**
     * Break-glass: the record opened without consent.
     *
     * Its own block, in the design's danger colour, because it is not a
     * permission — nobody agreed to it. A live override reads red; one that
     * has run out or been shut down reads plainly as ended, which is the
     * reassurance the patient is owed.
     *
     * A NULL `expires_at` is read the way EmergencyAccessService reads it —
     * as a row written before break-glass was time-boxed, and therefore
     * closed. Never as "no expiry".
     */
    private function emergencyOverrides(Patient $patient): ?array
    {
        $events = EmergencyAccessEvent::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $providers  = $this->userNames($events->pluck('provider_id')->all());
        $facilities = $this->facilityNames($events->pluck('facility_id')->all());

        return $this->table(
            __('passport.sections.emergency'),
            [
                __('passport.columns.who'),
                __('passport.columns.facility'),
                __('passport.columns.reason'),
                __('passport.columns.granted'),
                __('passport.columns.expires'),
                __('passport.columns.status'),
            ],
            $events->map(function (EmergencyAccessEvent $event) use ($providers, $facilities) {
                $expiresAt = $event->expires_at ? Carbon::parse($event->expires_at) : null;

                if ($event->revoked_at !== null) {
                    $status = 'revoked';
                    $tone   = 'neutral';
                } elseif ($expiresAt !== null && $expiresAt->isFuture()) {
                    $status = 'active';
                    $tone   = 'danger';
                } else {
                    $status = 'expired';
                    $tone   = 'neutral';
                }

                return [
                    $this->cell((string) ($providers[$event->provider_id] ?? '')),
                    $this->cell((string) ($facilities[$event->facility_id] ?? __('passport.a_facility'))),
                    $this->cell((string) $event->reason),
                    $this->cell($this->date($event->created_at, 'd M Y, H:i')),
                    $this->cell($expiresAt ? $this->date($expiresAt, 'd M Y, H:i') : __('passport.none_recorded')),
                    $this->cell($this->statusLabel($status), $tone),
                ];
            })->all(),
            'red',
            'alert-triangle'
        );
    }

    // ── 3. Requests waiting on the patient ───────────────────────────────

    /**
     * Facilities that have ASKED, and what became of the ask.
     *
     * A pending request is not access and is not drawn as though it were; it
     * is the one thing on this page the patient still has a decision to make
     * about, which is why it carries the design's attention colour. Answered
     * and lapsed requests stay listed so a patient can see they said no —
     * a refusal that vanishes from the record is a refusal nobody can point to.
     */
    private function consentRequests(Patient $patient): ?array
    {
        $requests = ConsentRequest::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        if ($requests->isEmpty()) {
            return null;
        }

        $facilities = $this->facilityNames($requests->pluck('requesting_facility_id')->all());

        $rows = $requests
            ->sortByDesc(fn (ConsentRequest $r) => [
                $this->isOpenRequest((string) $r->status) ? 1 : 0,
                $r->created_at ? Carbon::parse($r->created_at)->getTimestamp() : 0,
            ])
            ->map(function (ConsentRequest $request) use ($facilities) {
                $status = strtolower(trim((string) $request->status));

                // The clinician's own sentence is what the patient was asked
                // to weigh; the coded purpose is the fallback, not the point.
                $why = trim((string) $request->justification) !== ''
                    ? (string) $request->justification
                    : $this->statusLabel((string) $request->purpose);

                return [
                    $this->cell((string) ($facilities[$request->requesting_facility_id] ?? __('passport.a_facility'))),
                    $this->cell($why),
                    $this->cell($this->date($request->created_at)),
                    $this->cell($request->expires_at ? $this->date($request->expires_at) : __('passport.none_recorded')),
                    $this->cell(
                        $this->statusLabel($status),
                        self::REQUEST_TONES[$status] ?? $this->statusTone($status)
                    ),
                ];
            })
            ->values()
            ->all();

        return $this->table(
            __('passport.sections.manage_consent'),
            [
                __('passport.columns.who'),
                __('passport.columns.purpose'),
                __('passport.columns.date'),
                __('passport.columns.expires'),
                __('passport.columns.status'),
            ],
            $rows,
            'amber',
            'clipboard'
        );
    }

    /** The statuses the partial unique index treats as an open ask. */
    private function isOpenRequest(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['pending', 'pending_patient_approval'], true);
    }

    // ── Names ────────────────────────────────────────────────────────────

    /**
     * Facility names by id.
     *
     * Read past the demo-isolation scope on purpose: a demo grant can point at
     * an ordinary facility row, and a permission whose holder shows as "A
     * facility" tells the patient nothing about who is reading their record.
     *
     * @param  array<int,mixed>  $ids
     * @return \Illuminate\Support\Collection<string,string>
     */
    private function facilityNames(array $ids)
    {
        $ids = array_values(array_filter(array_unique($ids)));

        if ($ids === []) {
            return collect();
        }

        return Facility::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $ids)
            ->pluck('name', 'id');
    }

    /**
     * User names by id, for guardians, care-team clinicians and whoever broke
     * the glass. Same reason as above for stepping past demo isolation.
     *
     * @param  array<int,mixed>  $ids
     * @return \Illuminate\Support\Collection<string,string>
     */
    private function userNames(array $ids)
    {
        $ids = array_values(array_filter(array_unique($ids)));

        if ($ids === []) {
            return collect();
        }

        return User::withoutGlobalScope('isolate_demo')
            ->whereIn('id', $ids)
            ->pluck('name', 'id');
    }

    /**
     * The purpose each grant's originating request recorded, keyed by request id.
     *
     * @param  array<int,mixed>  $ids
     * @return array<string,string>
     */
    private function requestPurposes(array $ids): array
    {
        $ids = array_values(array_filter(array_unique($ids)));

        if ($ids === []) {
            return [];
        }

        return ConsentRequest::whereIn('id', $ids)
            ->pluck('purpose', 'id')
            ->map(fn ($p) => (string) $p)
            ->all();
    }
}
