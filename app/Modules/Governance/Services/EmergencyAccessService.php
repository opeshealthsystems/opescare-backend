<?php

namespace App\Modules\Governance\Services;

use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\User;
use App\Models\AccessLog;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\SecurityIncident;
use App\Notifications\EmergencyAccessAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmergencyAccessService
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * BREAK-GLASS — the shape of the exception
     * ═══════════════════════════════════════════════════════════════════
     *
     * How long an override stands. FOUR HOURS, absolute wall clock from the
     * moment the glass was broken: reads under the grant do NOT slide it.
     *
     * The window is sized to the EPISODE, not the shift. An unconscious
     * arrival is resuscitated, stabilised and handed over inside a few hours;
     * four covers that, plus the handover conversation, without asking a
     * clinician to re-authenticate at the bedside mid-resuscitation. It is
     * deliberately shorter than a rota (8-12h) so an override cannot quietly
     * become "the doctor who has access to that patient": when care genuinely
     * continues past four hours the patient is admitted, and an admission is
     * an episode at the facility — basis 4 on the ordinary chart — so the
     * lawful path has re-opened and break-glass is no longer needed.
     */
    public const GRANT_TTL_HOURS = 4;

    /** Window over which repeat invocations are counted and flagged. */
    public const REPEAT_WINDOW_HOURS = 24;

    /**
     * Break-glass CAN be re-invoked after expiry — refusing outright would be
     * the wrong failure mode for a second emergency on the same night. It is
     * bounded instead: a handful per patient, fewer than a caseload per
     * clinician, and every repeat is flagged and escalated rather than
     * silently allowed.
     */
    public const MAX_GRANTS_PER_PATIENT_PER_WINDOW = 3;
    public const MAX_GRANTS_PER_CLINICIAN_PER_WINDOW = 10;

    /**
     * A reason is mandatory and it is FREE TEXT — there is no dropdown here on
     * purpose. A clinician typing what happened in their own words is the only
     * part of this record a reviewer cannot second-guess from the data, and a
     * picklist would turn an account into a click. This floor is what stops
     * "emergency" / "asdf" from passing for one.
     */
    public const MIN_REASON_LENGTH = 20;
    public const MAX_REASON_LENGTH = 2000;

    /** Statuses openBreakGlass() can answer with. */
    public const STATUS_GRANTED = 'granted';
    public const STATUS_REUSED = 'reused';
    public const STATUS_RATE_LIMITED = 'rate_limited';
    public const STATUS_REVOCATION_LOCKED = 'revocation_locked';

    public function requestEmergencyAccess(
        string $patientId,
        string $facilityId,
        string $actorId,
        string $reason,
        ?string $ipAddress = null,
        bool $repeat = false
    ): EmergencyAccessEvent {
        // Verify facility status
        $facility = Facility::findOrFail($facilityId);

        $event = new EmergencyAccessEvent();
        $event->patient_id = $patientId;
        $event->facility_id = $facilityId;
        $event->provider_id = $actorId;
        $event->reason = $reason;
        // The override is a GRANT, not a permanent fact: it dies on its own.
        $event->expires_at = Carbon::now()->addHours(self::GRANT_TTL_HOURS);
        $event->ip_address = $ipAddress;
        $event->is_repeat = $repeat;
        $event->save();

        // Spawn a companion review case. A repeat is not merely "to be
        // reviewed" — the pattern is the abuse signal, so it starts escalated.
        $reviewCase = new EmergencyReviewCase();
        $reviewCase->emergency_access_event_id = $event->id;
        $reviewCase->status = $repeat ? 'escalated' : 'pending';
        $reviewCase->save();

        // Audit the emergency override via append-only AccessLog
        AccessLogService::log(
            $patientId,
            $actorId,
            'User',
            null,
            $facilityId,
            'emergency',
            'clinical_summary',
            'Patient',
            $patientId,
            'override',
            true
        );

        return $event;
    }

    public function buildEmergencyProfile(string $patientId): array
    {
        $patient = Patient::findOrFail($patientId);

        // Fetch active allergies
        $allergies = AllergyRecord::where('patient_id', $patientId)
            ->where('status', 'active')
            ->get(['substance', 'severity', 'status'])
            ->toArray();

        // Fetch chronic or active diagnoses
        $diagnoses = Diagnosis::where('patient_id', $patientId)
            ->where('status', 'active')
            ->get(['code', 'code_system', 'display_name'])
            ->toArray();

        // Strip clinical notes, prescriptions, and billing to strictly follow minimization
        return [
            'identity' => [
                'health_id' => $patient->health_id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
            ],
            'emergency_contact' => $patient->emergency_contact ?? null,
            'allergies' => $allergies,
            'chronic_conditions' => $diagnoses,
        ];
    }

    public function reviewEmergencyAccess(
        string $eventId,
        string $reviewerId,
        string $reviewStatus,
        ?string $comment = null
    ): EmergencyReviewCase {
        // Find review case linked to this event
        $reviewCase = EmergencyReviewCase::where('emergency_access_event_id', $eventId)->firstOrFail();
        $reviewCase->status = $reviewStatus;
        $reviewCase->reviewed_by = $reviewerId;
        $reviewCase->reviewed_at = Carbon::now();
        $reviewCase->reviewer_notes = $comment;
        $reviewCase->save();

        // Log audit event for review action
        AccessLogService::log(
            null,
            $reviewerId,
            'User',
            null,
            null,
            'system_security',
            'audit_log',
            'EmergencyReviewCase',
            $reviewCase->id,
            'review'
        );

        // If abuse is suspected or confirmed, create a high-severity security incident case
        if (in_array($reviewStatus, ['suspected_abuse', 'confirmed_abuse'])) {
            $incident = new SecurityIncident();
            $incident->incident_type = 'data_export_abuse';
            $incident->severity = 'high';
            $incident->status = 'new';
            $incident->summary = "Suspected override abuse detected on EmergencyReviewCase: {$reviewCase->id}. Reviewer Comment: {$comment}";
            $incident->detected_at = Carbon::now();
            $incident->created_by = $reviewerId;
            $incident->save();
        }

        return $reviewCase;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Break-glass: opening it, holding it, closing it
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Break the glass — the whole guarded act, in one place.
     *
     * `requestEmergencyAccess()` above is the low-level writer and stays that:
     * it takes a decision that has already been made and records it. THIS is
     * where the decision is made, because every guardrail that makes an
     * override survivable is a precondition, not a follow-up:
     *
     *   1. A grant this clinician already holds is REUSED, never duplicated.
     *      Re-opening the screen must not multiply events in the patient's
     *      log — a governance export full of duplicates hides the real ones.
     *   2. A grant the PATIENT revoked locks the door for the remainder of the
     *      window it was revoked from. The patient said no while it was live;
     *      immediately re-invoking would make revocation decorative.
     *   3. Repeat invocations are counted and capped, per patient and per
     *      clinician, over REPEAT_WINDOW_HOURS.
     *   4. Every repeat is flagged on the event, starts its review escalated,
     *      and raises a SecurityIncident.
     *   5. The patient is told, synchronously, before the caller gets an
     *      answer. A queued alert is an alert nobody has sent yet.
     *
     * @return array{status:string,event:?EmergencyAccessEvent,repeat:bool,notified:bool,retry_after:?int,prior_count:int}
     */
    public function openBreakGlass(
        Patient $patient,
        string $facilityId,
        string $clinicianId,
        string $reason,
        ?string $ipAddress = null
    ): array {
        $now = Carbon::now();

        // 1. Already holding one? Hand back the same grant.
        $existing = $this->activeGrantFor($patient->id, $clinicianId, $facilityId);

        if ($existing) {
            return [
                'status'      => self::STATUS_REUSED,
                'event'       => $existing,
                'repeat'      => (bool) $existing->is_repeat,
                'notified'    => $existing->notified_at !== null,
                'retry_after' => null,
                'prior_count' => $this->grantCountForPatient($patient->id, $clinicianId),
            ];
        }

        // 2. Revoked by the patient, and the window it was revoked from is
        //    still running. The answer is not "try again", it is "escalate".
        $revoked = EmergencyAccessEvent::query()
            ->where('patient_id', $patient->id)
            ->where('provider_id', $clinicianId)
            ->whereNotNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->orderByDesc('created_at')
            ->first();

        if ($revoked) {
            $this->flagIncident(
                'emergency_access_revoked_retry',
                'high',
                "Break-glass re-invocation attempted after the patient revoked grant {$revoked->id}"
                    . " (clinician {$clinicianId}, facility {$facilityId}).",
                $clinicianId
            );

            return [
                'status'      => self::STATUS_REVOCATION_LOCKED,
                'event'       => null,
                'repeat'      => true,
                'notified'    => false,
                'retry_after' => max(1, Carbon::parse($revoked->expires_at)->getTimestamp() - $now->getTimestamp()),
                'prior_count' => $this->grantCountForPatient($patient->id, $clinicianId),
            ];
        }

        // 3. Throttles.
        $perPatient   = $this->grantCountForPatient($patient->id, $clinicianId);
        $perClinician = $this->grantCountForClinician($clinicianId);

        if ($perPatient >= self::MAX_GRANTS_PER_PATIENT_PER_WINDOW
            || $perClinician >= self::MAX_GRANTS_PER_CLINICIAN_PER_WINDOW) {
            $this->flagIncident(
                'emergency_access_throttled',
                'high',
                "Break-glass throttle hit by clinician {$clinicianId}: {$perPatient} grant(s) on patient"
                    . " {$patient->id} and {$perClinician} in total within " . self::REPEAT_WINDOW_HOURS . "h.",
                $clinicianId
            );

            return [
                'status'      => self::STATUS_RATE_LIMITED,
                'event'       => null,
                'repeat'      => true,
                'notified'    => false,
                'retry_after' => self::REPEAT_WINDOW_HOURS * 3600,
                'prior_count' => $perPatient,
            ];
        }

        // 4. Open it.
        $repeat = $perPatient > 0;

        $event = $this->requestEmergencyAccess(
            $patient->id,
            $facilityId,
            $clinicianId,
            $reason,
            $ipAddress,
            $repeat
        );

        if ($repeat) {
            $this->flagIncident(
                'emergency_access_repeat',
                'medium',
                "Repeat break-glass: clinician {$clinicianId} opened grant {$event->id} on patient"
                    . " {$patient->id} after {$perPatient} earlier grant(s) within "
                    . self::REPEAT_WINDOW_HOURS . 'h.',
                $clinicianId
            );
        }

        // 5. Tell the patient. Now, not when a worker next runs.
        $notified = $this->notifyPatientOfBreakGlass($patient, $event, $ipAddress);

        return [
            'status'      => self::STATUS_GRANTED,
            'event'       => $event->refresh(),
            'repeat'      => $repeat,
            'notified'    => $notified,
            'retry_after' => null,
            'prior_count' => $perPatient,
        ];
    }

    /**
     * The grant this clinician currently holds on this patient AT THIS
     * FACILITY, or null.
     *
     * Facility is part of the key on purpose: a grant is an exception granted
     * to a clinician standing in one place with one patient in front of them.
     * Carrying it to the next facility would be a portable key, which is the
     * opposite of what break-glass is.
     */
    public function activeGrantFor(string $patientId, string $clinicianId, string $facilityId): ?EmergencyAccessEvent
    {
        return EmergencyAccessEvent::query()
            ->where('patient_id', $patientId)
            ->where('provider_id', $clinicianId)
            ->where('facility_id', $facilityId)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Is this grant still in force?
     *
     * A NULL `expires_at` is NOT "no expiry" — it is a row written before
     * break-glass was time-boxed, and it reads as closed. When the safe answer
     * and the permissive answer differ, this returns the safe one.
     */
    public function grantIsActive(EmergencyAccessEvent $event): bool
    {
        if ($event->revoked_at !== null) {
            return false;
        }

        if (empty($event->expires_at)) {
            return false;
        }

        return Carbon::parse($event->expires_at)->isFuture();
    }

    /** Seconds left on a live grant; 0 once it has lapsed. */
    public function grantSecondsRemaining(EmergencyAccessEvent $event): int
    {
        if (! $this->grantIsActive($event)) {
            return 0;
        }

        return max(0, Carbon::parse($event->expires_at)->getTimestamp() - Carbon::now()->getTimestamp());
    }

    /**
     * Close a grant early.
     *
     * The patient can do this from their own app (`$byType` = 'patient'); a
     * governance actor can do it too. Either way the row is stamped, never
     * deleted, and the revocation is itself an audited emergency-flagged event
     * — "the patient shut it down" is exactly the sort of thing a reviewer
     * must be able to see.
     *
     * ── WHY THE AUDIT ROW IS BEST-EFFORT HERE, AND ONLY HERE ─────────────
     *
     * Everywhere else in this file an audit failure kills the operation: no
     * trail, no disclosure. A revocation is the mirror image — it REMOVES
     * access — so failing closed would mean leaving an override the patient
     * has just refused standing open because a log write failed. The
     * revocation itself is durably recorded on the event row (`revoked_at`,
     * `revoked_by_type`, `revoked_by_id`), which is what actually shuts the
     * door, and the access_logs row is the additional uniform trail.
     *
     * `$actorUserId` exists because `access_logs.actor_id` is a NOT NULL
     * foreign key onto `users` — the table cannot hold a `patients` row as an
     * actor. A patient with a linked portal account (users.patient_id) is
     * logged there under that account; one without simply has no users row to
     * point at, so the row is skipped rather than faked with someone else's
     * identity.
     */
    public function revokeGrant(
        EmergencyAccessEvent $event,
        string $byType,
        string $byId,
        ?string $actorUserId = null
    ): EmergencyAccessEvent {
        if ($event->revoked_at === null) {
            $event->revoked_at = Carbon::now();
            $event->revoked_by_type = $byType;
            $event->revoked_by_id = $byId;
            $event->save();
        }

        $auditActor = $byType === 'patient' ? $actorUserId : $byId;

        if (! $auditActor) {
            Log::warning('Break-glass revocation not written to access_logs: no users row for the actor', [
                'emergency_access_event_id' => $event->id,
                'revoked_by_type' => $byType,
                'revoked_by_id' => $byId,
            ]);

            return $event;
        }

        try {
            AccessLogService::log(
                $event->patient_id,
                $auditActor,
                $byType === 'patient' ? 'Patient' : 'User',
                null,
                $event->facility_id,
                'emergency',
                'emergency_profile',
                'EmergencyAccessEvent',
                $event->id,
                'revoke',
                true
            );
        } catch (\Throwable $e) {
            Log::error('Break-glass revocation could not be audited (the revocation still stands)', [
                'emergency_access_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $event;
    }

    /**
     * One audited read under a live grant.
     *
     * `emergency_access = true` is the whole point: these rows must be
     * separable from routine treatment reads with a single WHERE clause, in
     * the patient's own log and in any governance export. The purpose string
     * carries the grant id so a reviewer can pivot from one read to the reason
     * that was typed for it.
     */
    public function logEmergencyRead(
        EmergencyAccessEvent $event,
        string $actorId,
        string $dataCategory = 'emergency_profile'
    ): AccessLog {
        $this->recordCategoryViewed($event, $dataCategory);

        return AccessLogService::log(
            $event->patient_id,
            $actorId,
            'User',
            null,
            $event->facility_id,
            'emergency:break_glass:' . $event->id,
            $dataCategory,
            'Patient',
            $event->patient_id,
            'read',
            true
        );
    }

    /**
     * What a break-glass actually opens: the emergency profile and nothing
     * else.
     *
     * `buildEmergencyProfile()` is already minimised — identity, emergency
     * contact, active allergies, chronic conditions, no notes, no
     * prescriptions, no billing. Blood group is added here because an
     * unconscious patient who may need transfusing is precisely the case this
     * exists for, and it is added HERE rather than in the shared builder so
     * the B2B/admin callers of that method keep the exact shape they had.
     */
    public function buildEmergencyProfileForGrant(EmergencyAccessEvent $event): array
    {
        $profile = $this->buildEmergencyProfile($event->patient_id);

        $patient = Patient::find($event->patient_id);
        $profile['identity']['blood_group'] = $patient?->blood_group;

        return $profile;
    }

    // ── Internals ────────────────────────────────────────────────────────

    /** Grants this clinician opened on this patient inside the repeat window. */
    private function grantCountForPatient(string $patientId, string $clinicianId): int
    {
        return EmergencyAccessEvent::query()
            ->where('patient_id', $patientId)
            ->where('provider_id', $clinicianId)
            ->where('created_at', '>=', Carbon::now()->subHours(self::REPEAT_WINDOW_HOURS))
            ->count();
    }

    /** Grants this clinician opened on ANYONE inside the repeat window. */
    private function grantCountForClinician(string $clinicianId): int
    {
        return EmergencyAccessEvent::query()
            ->where('provider_id', $clinicianId)
            ->where('created_at', '>=', Carbon::now()->subHours(self::REPEAT_WINDOW_HOURS))
            ->count();
    }

    /**
     * Append a data category to the event's `records_viewed` ledger.
     *
     * Additive and de-duplicated: the event says WHAT was opened under it, not
     * how many times (that is what the access_logs rows are for).
     */
    private function recordCategoryViewed(EmergencyAccessEvent $event, string $category): void
    {
        $seen = $event->records_viewed ?? [];

        if (! is_array($seen)) {
            $seen = [];
        }

        if (! in_array($category, $seen, true)) {
            $seen[] = $category;
            $event->records_viewed = array_values($seen);
            $event->save();
        }
    }

    /**
     * Tell the patient, synchronously.
     *
     * EmergencyAccessAlertNotification is a ShouldQueue notification and this
     * deployment's queue is `database`, so ->notify() would leave the alert
     * sitting in a jobs table until a worker happened to run. A break-glass
     * nobody has been told about is indistinguishable from a breach, so it is
     * pushed now.
     *
     * A failure here does NOT fail the override — an unconscious patient does
     * not stop needing care because the notifier is down. It leaves
     * `notified_at` NULL, which is a visible governance defect rather than a
     * silent one, and the caller reports `notified: false` so the UI can say
     * so out loud.
     */
    private function notifyPatientOfBreakGlass(
        Patient $patient,
        EmergencyAccessEvent $event,
        ?string $ipAddress
    ): bool {
        try {
            Notification::sendNow($patient, new EmergencyAccessAlertNotification(
                (string) ($patient->health_id ?? $patient->id),
                trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
                Carbon::parse($event->created_at)->format('d M Y, H:i'),
                (string) $event->facility_id,
                (string) $event->reason,
                (string) ($ipAddress ?? '')
            ));

            $event->notified_at = Carbon::now();
            $event->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Break-glass patient alert failed', [
                'emergency_access_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Raise a security incident once per distinct summary, so a clinician
     * retrying a locked door produces one case for a reviewer, not a flood.
     */
    private function flagIncident(string $type, string $severity, string $summary, ?string $actorId): void
    {
        if (SecurityIncident::where('summary', $summary)->exists()) {
            return;
        }

        $incident = new SecurityIncident();
        $incident->incident_type = $type;
        $incident->severity = $severity;
        $incident->status = 'new';
        $incident->summary = $summary;
        $incident->detected_at = Carbon::now();
        $incident->created_by = $actorId;
        $incident->save();
    }
}
