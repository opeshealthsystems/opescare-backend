<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\MedicineReservation;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * THE ONE care-relationship rule — why a clinician may open, write to, or
 * order against a patient's record.
 *
 * Holding a patient's id is not authorisation, and neither is holding a valid
 * provider token. Every clinician-side surface that touches one patient's
 * record (the chart, opening a conversation, placing a lab order) asks this
 * class the same question and gets the same answer: on what BASIS does a
 * relationship exist, or none.
 *
 * Until 2026-09 the rule lived in MobileDoctorChartController and was copied
 * verbatim into MobileDoctorMessagingController and
 * MobileDoctorLabOrderController. It now lives here and only here.
 *
 *     $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patientId);
 *
 *     $verdict->holds()      // true when any basis exists
 *     $verdict->basis        // 'appointment' | 'care_team' | 'treating_clinician'
 *                            //   | 'facility_episode' | 'consent_grant' | null
 *     $verdict->purpose()    // 'treatment:<basis>' — the access-log purpose
 *                            //   ('treatment:no_relationship' when refused)
 *
 * THE FIVE BASES, checked in this order (cheapest and most specific first):
 *
 *   1. `appointment`         — the patient is on this clinician's own schedule.
 *   2. `care_team`           — the clinician is a CURRENT member of the
 *                              patient's care team (active, not left).
 *   3. `treating_clinician`  — the clinician personally performed a clinical
 *                              act for this patient: attending on an
 *                              encounter, provider on a visit, prescriber, or
 *                              the clinician who raised a lab / imaging order.
 *   4. `facility_episode`    — the patient has an episode of care at the
 *                              clinician's OWN facility (the token's facility,
 *                              never a named one) that is still open,
 *                              scheduled in the future, or began within
 *                              FACILITY_EPISODE_LOOKBACK_DAYS.
 *   5. `consent_grant`       — the patient has granted this facility an
 *                              active, unexpired ConsentGrant.
 *
 * Nothing else holds by default. There is deliberately no "emergency" or
 * "break-glass" basis here; that is a different act with its own trail.
 *
 * ONE OPT-IN BASIS, for messaging only:
 *
 *   6. `dispensing`          — the token's facility is the DISPENSING facility
 *                              of one of this patient's open prescriptions
 *                              (routed to / claimed by that counter and not yet
 *                              dispensed), or holds an open medicine
 *                              reservation of theirs at one of its listings —
 *                              AND the clinician holds the `dispense`
 *                              capability there. A pharmacist filling a
 *                              script may write to the patient about it; they
 *                              may not open the chart on that strength, so
 *                              this basis is never applied unless a caller
 *                              asks for it by name:
 *
 *     ClinicalRelationshipGate::evaluate($c, $f, $p, [ClinicalRelationshipGate::DISPENSING]);
 *
 * INPUTS ARE THE TOKEN'S. `$clinicianId` and `$facilityId` are what
 * AuthenticateMobileProvider stamped on the request from the bearer token —
 * callers read them from `$request->attributes` and from nowhere else. A null
 * clinician never holds; a null facility simply cannot satisfy bases 4 and 5.
 *
 * Pure: no request, no session, no side effects — reads only.
 */
final class ClinicalRelationshipGate
{
    public const APPOINTMENT        = 'appointment';
    public const CARE_TEAM          = 'care_team';
    public const TREATING_CLINICIAN = 'treating_clinician';
    public const FACILITY_EPISODE   = 'facility_episode';
    public const CONSENT_GRANT      = 'consent_grant';
    /** Opt-in (see the class docblock): the pharmacy counter filling the patient's script. */
    public const DISPENSING         = 'dispensing';

    /** Every basis applied by default, in the order it is checked. */
    public const BASES = [
        self::APPOINTMENT,
        self::CARE_TEAM,
        self::TREATING_CLINICIAN,
        self::FACILITY_EPISODE,
        self::CONSENT_GRANT,
    ];

    /** Bases a caller may add by name. Anything else passed to evaluate() is ignored. */
    public const OPT_IN_BASES = [self::DISPENSING];

    /** `prescriptions.status` values a counter can still act on. */
    private const OPEN_RX_STATUSES = ['active', 'partially_dispensed'];

    /** `prescriptions.pharmacy_routing_status` values from which the counter still has work to do. */
    private const OPEN_ROUTING_STATUSES = ['pending', 'sent', 'confirmed', 'ready'];

    /** `medicine_reservations.status` values that are still a live hold (MedicineReservationStatus::isOpen). */
    private const OPEN_RESERVATION_STATUSES = ['pending', 'confirmed', 'ready_for_pickup'];

    /**
     * How far back an episode at the clinician's own facility still counts as
     * a live care relationship. Older, closed episodes need one of the other
     * four bases — most naturally a new appointment, which is basis 1. A
     * ten-year-old closed visit is history, and history is not a reason to
     * open a chart today.
     */
    public const FACILITY_EPISODE_LOOKBACK_DAYS = 365;

    /** The access-log purpose written when the gate refuses. */
    public const NO_RELATIONSHIP_PURPOSE = 'treatment:no_relationship';

    /**
     * @param  string|null  $basis  one of BASES, or null when nothing holds
     */
    private function __construct(public readonly ?string $basis)
    {
    }

    /**
     * The basis on which this clinician may act on this patient's record.
     *
     * Returns the FIRST basis that holds. The verdict's `basis` goes into the
     * audit row's purpose, so the log says on what authority the record was
     * opened, not merely that it was.
     *
     * @param  list<string>  $alsoAllow  opt-in bases (OPT_IN_BASES) this caller
     *                                   accepts on top of the five defaults
     */
    public static function evaluate(?string $clinicianId, ?string $facilityId, string $patientId, array $alsoAllow = []): self
    {
        if (! $clinicianId) {
            return self::none();
        }

        // 1. On this clinician's own schedule.
        if (Appointment::where('patient_id', $patientId)->where('provider_id', $clinicianId)->exists()) {
            return new self(self::APPOINTMENT);
        }

        // 2. A current member of this patient's care team.
        if (
            CareTeamMember::where('patient_id', $patientId)
                ->where('provider_id', $clinicianId)
                ->where('is_active', true)
                ->whereNull('left_at')
                ->exists()
        ) {
            return new self(self::CARE_TEAM);
        }

        // 3. This clinician personally did something clinical for this patient.
        $ownAct = Encounter::where('patient_id', $patientId)->where('attending_provider_id', $clinicianId)->exists()
            || DB::table('visits')->where('patient_id', $patientId)->where('provider_id', $clinicianId)->exists()
            || Prescription::where('patient_id', $patientId)->where('prescribed_by', $clinicianId)->exists()
            || LabOrder::where('patient_id', $patientId)->where('ordered_by', $clinicianId)->exists()
            || ImagingOrder::where('patient_id', $patientId)->where('ordered_by', $clinicianId)->exists();

        if ($ownAct) {
            return new self(self::TREATING_CLINICIAN);
        }

        // 4. An episode of care at the clinician's OWN facility — the token's
        //    facility, which no caller can name — that is open, still ahead,
        //    or recent enough to still be care rather than history.
        if ($facilityId && self::hasFacilityEpisode($patientId, $facilityId)) {
            return new self(self::FACILITY_EPISODE);
        }

        // 5. The patient has granted this facility access outright.
        if ($facilityId && self::hasActiveConsentGrant($patientId, $facilityId)) {
            return new self(self::CONSENT_GRANT);
        }

        // 6. (opt-in) The token's facility is filling the patient's script.
        if (
            $facilityId
            && in_array(self::DISPENSING, $alsoAllow, true)
            && self::hasDispensingRelationship($patientId, $facilityId, $clinicianId)
        ) {
            return new self(self::DISPENSING);
        }

        return self::none();
    }

    /** The refusing verdict. */
    public static function none(): self
    {
        return new self(null);
    }

    /** Does any basis hold? */
    public function holds(): bool
    {
        return $this->basis !== null;
    }

    /**
     * The access-log `purpose` for this verdict: `treatment` (why) plus the
     * basis (on what authority), or the refusal purpose.
     */
    public function purpose(): string
    {
        return $this->basis === null
            ? self::NO_RELATIONSHIP_PURPOSE
            : 'treatment:' . $this->basis;
    }

    /**
     * Is there a live episode of care for this patient at this facility?
     *
     * "Live" is: still open, scheduled in the future, or begun within
     * FACILITY_EPISODE_LOOKBACK_DAYS — across visits, encounters,
     * appointments and prescriptions at that facility.
     */
    public static function hasFacilityEpisode(string $patientId, string $facilityId): bool
    {
        $since = Carbon::now()->subDays(self::FACILITY_EPISODE_LOOKBACK_DAYS);

        $visit = DB::table('visits')
            ->where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->exists();

        if ($visit) {
            return true;
        }

        $encounter = Encounter::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->exists();

        if ($encounter) {
            return true;
        }

        $appointment = Appointment::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('scheduled_at', '>=', $since)
            ->exists();

        if ($appointment) {
            return true;
        }

        return Prescription::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('prescribed_at', '>=', $since)
            ->exists();
    }

    /**
     * An active, unexpired consent grant for this patient scoped to this
     * facility. Also what the chart header reports as `consent_granted`.
     */
    public static function hasActiveConsentGrant(string $patientId, ?string $facilityId): bool
    {
        if (! $facilityId) {
            return false;
        }

        return ConsentGrant::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->exists();
    }

    /**
     * Is this facility the counter filling one of the patient's open scripts,
     * or holding one of their open medicine reservations — and does the
     * clinician hold `dispense` there?
     *
     * "Open" is a prescription still dispensable (active / partially
     * dispensed) whose routing has not ended (pending / sent / confirmed /
     * ready — PharmacyDeskService's open routing states) and whose
     * `pharmacy_route_id` names one of this facility's active dispensing
     * routes; or a medicine reservation still live (pending / accepted /
     * ready) at one of the directory listings linked to this facility. A
     * dispensed, rejected or voided script is history, and history is not a
     * reason to write to the patient today.
     *
     * The role check is what makes this "a pharmacist at the dispensing
     * facility" rather than "anyone at it": StaffCapabilities resolves the
     * role held at THIS facility (the posting), falling back to the global
     * role, and asks whether it carries `dispense`. Pass a null clinician to
     * ask the facility-level question alone.
     */
    public static function hasDispensingRelationship(string $patientId, string $facilityId, ?string $clinicianId = null): bool
    {
        if ($clinicianId !== null) {
            $user = User::query()->find($clinicianId);

            if (! $user || ! StaffCapabilities::canAt($user, $facilityId, 'dispense')) {
                return false;
            }
        }

        $routeIds = PharmacyRoute::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($routeIds !== []) {
            $routed = Prescription::query()
                ->where('patient_id', $patientId)
                ->whereIn('pharmacy_route_id', $routeIds)
                ->whereIn('status', self::OPEN_RX_STATUSES)
                ->whereIn('pharmacy_routing_status', self::OPEN_ROUTING_STATUSES)
                ->exists();

            if ($routed) {
                return true;
            }
        }

        $listingIds = DB::table('care_facilities')
            ->where('facility_id', $facilityId)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($listingIds === []) {
            return false;
        }

        return MedicineReservation::query()
            ->where('patient_id', $patientId)
            ->whereIn('care_facility_id', $listingIds)
            ->whereIn('status', self::OPEN_RESERVATION_STATUSES)
            ->exists();
    }
}
