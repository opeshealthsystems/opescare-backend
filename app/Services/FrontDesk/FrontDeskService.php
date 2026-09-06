<?php

namespace App\Services\FrontDesk;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentStatusHistory;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilitySchedule;
use App\Models\Patient;
use App\Models\ProviderAvailability;
use App\Models\User;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\EncounterManagement\Services\VisitManagementService;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use App\Support\StaffCapabilities;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The facility's appointment book as the front desk works it: the day, the
 * clinicians on duty, booking by exact Health ID, routing to a clinician,
 * confirm / check-in / no-show / cancel, and registering a walk-in.
 *
 * Also the ONE booking primitive the clinician's follow-up endpoint uses
 * (MobileDoctorScheduleController::followUp), so a desk booking and a
 * clinician's own follow-up write exactly the same rows.
 *
 * ── Scope ─────────────────────────────────────────────────────────────────
 * Every query is bounded by DeskActor::facilityId, which the controllers take
 * from the bearer token and nowhere else. The desk sees the WHOLE facility
 * (every clinician's list), unlike MobileDoctorScheduleController::mine(),
 * which is one clinician's own diary — that is the difference between the
 * `front_desk` and `schedule` capabilities. It never sees another facility.
 *
 * ── Patients ──────────────────────────────────────────────────────────────
 * By exact Health ID only (Patient::resolveHealthId — case and spacing
 * forgiven, comparison still an equality on the whole identifier, merges
 * followed). No LIKE, no partial, no listing. The only patients the desk can
 * name are the ones already on its own book plus the one whose card is in
 * front of it. A new patient is created through PatientIdentityService and
 * only there; a DuplicateIdentityFound is surfaced, never bypassed.
 *
 * ── Vocabulary ────────────────────────────────────────────────────────────
 * `appointments.status` on this database mixes several vocabularies (patient
 * bookings are `booked`, staff bookings `scheduled`, the doctor app writes
 * `checked_in`, older rows `arrived`). The desk reads them all and answers in
 * one closed set — see WIRE_STATUS. The transition tables below are the same
 * ones MobileDoctorScheduleController applies, so the desk and the clinician
 * can never disagree about what may follow what.
 *
 * ── Rows written ──────────────────────────────────────────────────────────
 * Every transition writes an `audit_events` row in the shape
 * AppointmentService::audit() writes (plus source_system + ip, because this
 * actor is holding a phone) and an append-only `appointment_status_histories`
 * row. Check-in opens a Visit through VisitManagementService exactly as the
 * web desk's AppointmentService::checkIn() does. Cancellation is delegated to
 * AppointmentService::cancel(), the same method the web desk calls.
 */
class FrontDeskService
{
    /** Column vocabulary → the desk's closed wire vocabulary. */
    public const WIRE_STATUS = [
        'booked'      => 'scheduled',
        'scheduled'   => 'scheduled',
        'pending'     => 'scheduled',
        'requested'   => 'scheduled',
        'confirmed'   => 'confirmed',
        'checked_in'  => 'checked_in',
        'arrived'     => 'checked_in',
        'in_progress' => 'in_progress',
        'completed'   => 'completed',
        'cancelled'   => 'cancelled',
        'canceled'    => 'cancelled',
        // A rescheduled row is a closed row that AppointmentService::reschedule
        // replaced with a new one. The nearest honest word is `cancelled`.
        'rescheduled' => 'cancelled',
        'no_show'     => 'no_show',
    ];

    /** Column values that mean "booked, nobody has confirmed it yet". */
    public const PENDING_STATUSES = ['booked', 'scheduled', 'pending', 'requested'];

    /** Column values a check-in may follow. */
    public const CHECK_IN_FROM = ['booked', 'scheduled', 'pending', 'requested', 'confirmed'];

    /** Column values a no-show may follow. */
    public const NO_SHOW_FROM = ['booked', 'scheduled', 'pending', 'requested', 'confirmed', 'checked_in', 'arrived'];

    /** Column values that mean the patient is in the building. */
    public const CHECKED_IN_STATUSES = ['checked_in', 'arrived', 'in_progress'];

    /** Terminal column values — nothing moves out of these. */
    public const CLOSED_STATUSES = ['completed', 'cancelled', 'canceled', 'no_show', 'rescheduled'];

    /** Column values still live on the day. */
    public const ACTIVE_STATUSES = ['booked', 'scheduled', 'pending', 'requested', 'confirmed', 'checked_in', 'arrived', 'in_progress'];

    /** Relations every appointment answer carries. */
    private const WITH = ['patient', 'provider:id,name,role_id', 'provider.role:id,name', 'facility:id,name'];

    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly VisitManagementService $visits,
        private readonly PatientIdentityService $identity,
    ) {
    }

    // ──────────────────────────────────────────────────────────────────
    // Reading the book
    // ──────────────────────────────────────────────────────────────────

    /** Facility identity for the home screen. Not PHI, so the demo partition is not applied. */
    public function facility(string $facilityId): array
    {
        $row = Facility::withoutGlobalScope('isolate_demo')->whereKey($facilityId)->first(['id', 'name']);

        return [
            'id'   => $facilityId,
            'name' => $row?->name,
        ];
    }

    /**
     * The day's figures, each a real count of a real set on this facility's
     * book. `today_total` is every row dated today except the `rescheduled`
     * artefacts AppointmentService::reschedule leaves behind (those rows were
     * replaced, so counting them would count one visit twice).
     *
     * @return array<string,int>
     */
    public function counts(string $facilityId, CarbonInterface $day): array
    {
        $base = fn (): Builder => $this->onDay($facilityId, $day);

        return [
            'today_total'           => $base()->where('status', '!=', 'rescheduled')->count(),
            'awaiting_confirmation' => $base()->whereIn('status', self::PENDING_STATUSES)->count(),
            'confirmed'             => $base()->where('status', 'confirmed')->count(),
            'checked_in'            => $base()->whereIn('status', self::CHECKED_IN_STATUSES)->count(),
            'completed'             => $base()->where('status', 'completed')->count(),
            'no_show'               => $base()->where('status', 'no_show')->count(),
            'cancelled'             => $base()->whereIn('status', ['cancelled', 'canceled'])->count(),
            'unassigned'            => $base()->whereIn('status', self::ACTIVE_STATUSES)->whereNull('provider_id')->count(),
        ];
    }

    /** What each figure in counts() is counting, for the app and the next engineer. */
    public function countsBasis(): array
    {
        return [
            'today_total'           => 'appointments (this facility, the day, every status except rescheduled)',
            'awaiting_confirmation' => 'appointments (booked / scheduled / pending / requested)',
            'confirmed'             => 'appointments (confirmed)',
            'checked_in'            => 'appointments (checked_in / arrived / in_progress)',
            'completed'             => 'appointments (completed)',
            'no_show'               => 'appointments (no_show)',
            'cancelled'             => 'appointments (cancelled)',
            'unassigned'            => 'appointments (still live, provider_id null — waiting to be routed)',
        ];
    }

    /**
     * The whole facility's book for one day, every clinician included,
     * optionally narrowed by wire status and/or clinician. Narrowing only —
     * neither filter can widen the facility bound.
     *
     * @param  list<string>|null  $columnStatuses  from columnStatusesFor()
     * @return Collection<int,Appointment>
     */
    public function dayBook(string $facilityId, CarbonInterface $day, ?array $columnStatuses = null, ?string $providerId = null): Collection
    {
        $query = $this->onDay($facilityId, $day)
            ->with(self::WITH)
            ->orderBy('scheduled_at')
            ->orderBy('created_at');

        if ($columnStatuses !== null) {
            $query->whereIn('status', $columnStatuses);
        }

        if ($providerId !== null && $providerId !== '') {
            $query->where('provider_id', $providerId);
        }

        return $this->withDeskRoles($query->get(), $facilityId);
    }

    /**
     * Query `status` (wire vocabulary, one value or a comma list) → the column
     * values it covers. Null means "no filter"; an unrecognised value narrows
     * to nothing rather than being dropped, so a filter the server does not
     * understand can never quietly return everything.
     *
     * @return list<string>|null
     */
    public function columnStatusesFor(mixed $status): ?array
    {
        if ($status === null || $status === '' || $status === []) {
            return null;
        }

        $wanted = is_array($status) ? $status : explode(',', (string) $status);
        $wanted = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $wanted), fn ($s) => $s !== ''));

        if ($wanted === []) {
            return null;
        }

        $columns = [];

        foreach (self::WIRE_STATUS as $column => $wire) {
            if (in_array($wire, $wanted, true)) {
                $columns[] = $column;
            }
        }

        return $columns === [] ? ['__none__'] : array_values(array_unique($columns));
    }

    /** Column value → wire value. Unknown values pass through verbatim. */
    public static function wireStatus(?string $status): ?string
    {
        return $status === null ? null : (self::WIRE_STATUS[$status] ?? $status);
    }

    /**
     * The clinicians posted at this facility: an active facility_role_assignments
     * row here whose role holds `clinical_chart` (config/staff_capabilities.php).
     * These are the only people an appointment can be routed to. Each user
     * carries `desk_role`, the role they hold AT THIS FACILITY.
     *
     * @return Collection<int,User>
     */
    public function clinicians(string $facilityId): Collection
    {
        $postings = $this->clinicalPostings($facilityId)->with('role:id,name')->get(['user_id', 'role_id']);

        if ($postings->isEmpty()) {
            return new Collection();
        }

        $roleByUser = $postings->mapWithKeys(fn (FacilityRoleAssignment $a) => [(string) $a->user_id => $a->role?->name])->all();

        $users = User::query()
            ->whereIn('id', array_keys($roleByUser))
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        foreach ($users as $user) {
            $user->setAttribute('desk_role', $roleByUser[(string) $user->id] ?? null);
        }

        return $users;
    }

    /** True when the user is a clinician posted at this facility (see clinicians()). */
    public function isClinicianAt(string $facilityId, string $userId): bool
    {
        if (! Str::isUuid($userId)) {
            return false;
        }

        return $this->clinicalPostings($facilityId)->where('user_id', $userId)->exists();
    }

    /** One appointment from this facility's book, or null. Never a 403 — that would confirm it exists elsewhere. */
    public function find(string $facilityId, string $id): ?Appointment
    {
        $appointment = $this->book($facilityId)->with(self::WITH)->find($id);

        if ($appointment) {
            $this->withDeskRoles(new Collection([$appointment]), $facilityId);
        }

        return $appointment;
    }

    /** Re-read a row through the facility bound, so an answer can never widen it. */
    public function reload(string $facilityId, Appointment $appointment): Appointment
    {
        return $this->find($facilityId, $appointment->id) ?? $appointment;
    }

    /**
     * The patient whose card is at the desk: exact Health ID only (case and
     * spacing forgiven, merges followed). A partial value matches nothing.
     */
    public function patientByHealthId(string $entered): ?Patient
    {
        $key = strtoupper(preg_replace('/\s+/', '', trim($entered)) ?? '');

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        return Patient::resolveHealthId($key);
    }

    // ──────────────────────────────────────────────────────────────────
    // Booking
    // ──────────────────────────────────────────────────────────────────

    /**
     * Book an appointment on the actor's facility, as staff.
     *
     * Goes through AppointmentService::book — the same service the platform
     * books with — so the row and its `create` audit event come out exactly
     * as every other booking's. The service also enforces opening hours
     * (facility_schedules) and the clinician's posted hours
     * (provider_availabilities) — WHEN THEY EXIST. On this deployment neither
     * table is populated for most institutions (the web desk,
     * StaffPortalController::appointmentsStore, books with a bare
     * Appointment::create for that reason), so an unconfigured schedule is not
     * treated as "closed all week": when the facility has no opening hours at
     * all, or the clinician has no posted hours here at all, the booking is
     * written the way the web desk writes it — same columns, same audit row.
     * A CONFIGURED schedule is enforced and the refusal names it.
     *
     * @throws FrontDeskRefusal facility_not_available | provider_not_available | booking_failed
     */
    public function bookAppointment(
        DeskActor $actor,
        Patient $patient,
        CarbonInterface $when,
        string $appointmentType,
        ?string $providerId,
        ?string $reason,
        string $historyReason,
    ): Appointment {
        $payload = [
            'patient_id'       => $patient->id,
            'facility_id'      => $actor->facilityId,
            'provider_id'      => $providerId,
            'appointment_type' => $appointmentType,
            'status'           => 'scheduled',
            'scheduled_at'     => Carbon::instance($when),
            'booked_by_type'   => 'staff',
            'booked_by_id'     => $actor->userId,
            'reason'           => $reason,
        ];

        try {
            $appointment = $this->appointments->book($payload);
        } catch (Throwable $e) {
            $appointment = match ($e->getMessage()) {
                // The service checks the facility BEFORE the clinician, so on
                // this branch the clinician's hours were never looked at: when
                // the facility simply has none configured, look at theirs here.
                'FACILITY_NOT_AVAILABLE_FOR_APPOINTMENT' => match (true) {
                    $this->hasOpeningHours($actor->facilityId)
                        => throw new FrontDeskRefusal('facility_not_available', 422),
                    $providerId !== null
                        && $this->hasPostedHours($actor->facilityId, $providerId)
                        && ! $this->providerAvailableAt($actor->facilityId, $providerId, $payload['scheduled_at'])
                        => throw new FrontDeskRefusal('provider_not_available', 422),
                    default => $this->bookWithoutHours($payload, $actor),
                },
                // The facility's hours passed; only the clinician's were refused.
                'PROVIDER_NOT_AVAILABLE_FOR_APPOINTMENT' => $this->hasPostedHours($actor->facilityId, (string) $providerId)
                    ? throw new FrontDeskRefusal('provider_not_available', 422)
                    : $this->bookWithoutHours($payload, $actor),
                default => throw new FrontDeskRefusal('booking_failed', 422),
            };
        }

        $this->history($appointment->id, null, 'scheduled', $actor, $historyReason);

        return $appointment;
    }

    /**
     * Move the appointment in place (the desk's contract keeps the same id),
     * releasing the slot it no longer occupies. Same as the clinician app.
     *
     * @throws FrontDeskRefusal appointment_closed | reschedule_failed
     */
    public function reschedule(DeskActor $actor, Appointment $appointment, CarbonInterface $when): Appointment
    {
        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            throw new FrontDeskRefusal('appointment_closed', 409);
        }

        try {
            return DB::transaction(function () use ($actor, $appointment, $when) {
                /** @var Appointment $locked */
                $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);
                $before = $locked->toArray();

                $this->releaseSlot($locked);

                $locked->update([
                    'scheduled_at'        => Carbon::instance($when),
                    'appointment_slot_id' => null,
                ]);

                $this->audit($actor, $locked, 'reschedule', __('desk_api.reason_rescheduled'), $before);
                $this->history($locked->id, $locked->status, $locked->status, $actor, __('desk_api.reason_rescheduled'));

                return $locked->refresh();
            });
        } catch (Throwable $e) {
            throw new FrontDeskRefusal('reschedule_failed', 422);
        }
    }

    /**
     * Route the appointment to a clinician posted at this facility.
     *
     * @throws FrontDeskRefusal provider_not_at_facility | appointment_closed | route_failed
     */
    public function route(DeskActor $actor, Appointment $appointment, string $providerId): Appointment
    {
        if (! $this->isClinicianAt($actor->facilityId, $providerId)) {
            throw new FrontDeskRefusal('provider_not_at_facility', 422);
        }

        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            throw new FrontDeskRefusal('appointment_closed', 409);
        }

        try {
            return DB::transaction(function () use ($actor, $appointment, $providerId) {
                /** @var Appointment $locked */
                $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);
                $before = $locked->toArray();

                $locked->update(['provider_id' => $providerId]);

                $this->audit($actor, $locked, 'route', __('desk_api.reason_routed'), $before);
                $this->history($locked->id, $locked->status, $locked->status, $actor, __('desk_api.reason_routed'), [
                    'provider_id' => $providerId,
                ]);

                return $locked->refresh();
            });
        } catch (Throwable $e) {
            throw new FrontDeskRefusal('route_failed', 422);
        }
    }

    /**
     * Confirm a booked appointment — the web desk's appointmentsConfirm, with
     * the ledger rows it was missing.
     *
     * @throws FrontDeskRefusal already_confirmed | appointment_closed | not_confirm_eligible | confirm_failed
     */
    public function confirm(DeskActor $actor, Appointment $appointment): Appointment
    {
        if ($appointment->status === 'confirmed') {
            throw new FrontDeskRefusal('already_confirmed', 409);
        }

        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            throw new FrontDeskRefusal('appointment_closed', 409);
        }

        if (! in_array($appointment->status, self::PENDING_STATUSES, true)) {
            throw new FrontDeskRefusal('not_confirm_eligible', 422);
        }

        return $this->transition($actor, $appointment, 'confirmed', 'confirm', __('desk_api.reason_confirmed'), [], 'confirm_failed');
    }

    /**
     * Check the patient in and open their visit — exactly what the web desk's
     * AppointmentService::checkIn() does (VisitManagementService::createVisit
     * with the same fields, then status / visit_id / checked_in_at), over the
     * wider status vocabulary this database actually contains: the service
     * accepts only scheduled|confirmed and would refuse every patient booking,
     * which is written `booked`.
     *
     * @throws FrontDeskRefusal already_checked_in | not_check_in_eligible | check_in_failed
     */
    public function checkIn(DeskActor $actor, Appointment $appointment): Appointment
    {
        if (in_array($appointment->status, self::CHECKED_IN_STATUSES, true)) {
            throw new FrontDeskRefusal('already_checked_in', 409);
        }

        if (! in_array($appointment->status, self::CHECK_IN_FROM, true)) {
            throw new FrontDeskRefusal('not_check_in_eligible', 422);
        }

        try {
            return DB::transaction(function () use ($actor, $appointment) {
                /** @var Appointment $locked */
                $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);

                // Re-checked under the lock: two receptionists tapping at once
                // must not open two visits for one arrival.
                if (! in_array($locked->status, self::CHECK_IN_FROM, true)) {
                    throw new FrontDeskRefusal(
                        in_array($locked->status, self::CHECKED_IN_STATUSES, true) ? 'already_checked_in' : 'not_check_in_eligible',
                        in_array($locked->status, self::CHECKED_IN_STATUSES, true) ? 409 : 422,
                    );
                }

                $before = $locked->toArray();
                $from   = $locked->status;

                $visit = $this->visits->createVisit([
                    'patient_id'  => $locked->patient_id,
                    'facility_id' => $locked->facility_id,
                    'provider_id' => $locked->provider_id,
                    'visit_type'  => $locked->appointment_type,
                ], $actor->userId);

                $locked->update([
                    'status'        => 'checked_in',
                    'visit_id'      => $visit->id,
                    'checked_in_at' => now(),
                ]);

                $this->audit($actor, $locked, 'check_in', __('desk_api.reason_checked_in'), $before);
                $this->history($locked->id, $from, 'checked_in', $actor, __('desk_api.reason_checked_in'), ['visit_id' => $visit->id]);

                return $locked->refresh();
            });
        } catch (FrontDeskRefusal $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new FrontDeskRefusal('check_in_failed', 422);
        }
    }

    /**
     * @throws FrontDeskRefusal already_no_show | not_no_show_eligible | no_show_failed
     */
    public function noShow(DeskActor $actor, Appointment $appointment): Appointment
    {
        if ($appointment->status === 'no_show') {
            throw new FrontDeskRefusal('already_no_show', 409);
        }

        if (! in_array($appointment->status, self::NO_SHOW_FROM, true)) {
            throw new FrontDeskRefusal('not_no_show_eligible', 422);
        }

        return $this->transition($actor, $appointment, 'no_show', 'no_show', __('desk_api.reason_no_show'), [
            'no_show_at' => now(),
        ], 'no_show_failed');
    }

    /**
     * Cancel — delegated to AppointmentService::cancel(), which is what the
     * web desk calls: it locks the row, releases the slot and writes the
     * audit_events row. A completed or missed visit cannot be "cancelled":
     * that would erase, on the register, a visit that took place.
     *
     * @throws FrontDeskRefusal already_cancelled | appointment_closed | cancel_failed
     */
    public function cancel(DeskActor $actor, Appointment $appointment, string $reason): Appointment
    {
        if (in_array($appointment->status, ['cancelled', 'canceled'], true)) {
            throw new FrontDeskRefusal('already_cancelled', 422);
        }

        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            throw new FrontDeskRefusal('appointment_closed', 409);
        }

        $from = $appointment->status;

        try {
            $cancelled = $this->appointments->cancel($appointment, $reason, $actor->userId);
        } catch (Throwable $e) {
            throw new FrontDeskRefusal('cancel_failed', 422);
        }

        $this->history($cancelled->id, $from, 'cancelled', $actor, $reason);

        return $cancelled;
    }

    // ──────────────────────────────────────────────────────────────────
    // Walk-in registration
    // ──────────────────────────────────────────────────────────────────

    /**
     * Register a patient who has walked in without a Health ID — through
     * PatientIdentityService and only there. The MPI's duplicate check is the
     * whole point: a match is surfaced to the desk as a 409 so they look the
     * person up instead of minting a second identity. Never bypassed.
     *
     * The new record is `provisional` (a desk has seen a face, not a document)
     * and joins the demo partition when the facility is a demo facility or
     * the deployment runs in demo mode — otherwise the receptionist who just
     * registered them could not find them a second later.
     *
     * @param  array{first_name:string,last_name:string,date_of_birth:string,sex:string,phone?:string|null}  $data
     * @throws DuplicateIdentityFound
     * @throws FrontDeskRefusal registration_failed
     */
    public function registerWalkIn(DeskActor $actor, array $data): Patient
    {
        $facilityIsDemo = (bool) Facility::withoutGlobalScope('isolate_demo')->whereKey($actor->facilityId)->value('is_demo');
        $isDemo         = (bool) config('demo.enabled', false) || $facilityIsDemo;

        try {
            $patient = $this->identity->createPatientCandidate([
                'first_name'      => trim($data['first_name']),
                'last_name'       => trim($data['last_name']),
                'date_of_birth'   => $data['date_of_birth'],
                'sex'             => $data['sex'],
                'phone_number'    => isset($data['phone']) && trim((string) $data['phone']) !== '' ? trim((string) $data['phone']) : null,
                'identity_status' => 'provisional',
                'country_code'    => 'CM',
            ], $actor->userId, $actor->facilityId);
        } catch (DuplicateIdentityFound $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('front-desk: walk-in registration failed', ['facility_id' => $actor->facilityId, 'error' => $e->getMessage()]);

            throw new FrontDeskRefusal('registration_failed', 422);
        }

        if ((bool) $patient->is_demo !== $isDemo) {
            // is_demo is deliberately not mass-assignable; this is the one
            // sanctioned way to set it.
            $patient->forceFill(['is_demo' => $isDemo])->save();
        }

        return $patient->refresh();
    }

    // ──────────────────────────────────────────────────────────────────
    // Telling the patient
    // ──────────────────────────────────────────────────────────────────

    /**
     * Ring the patient's bell (a `notifications` row in the payload shape the
     * mobile feed reads: type · title · message · severity · action_url ·
     * action_label) and, when asked, push to their phone through
     * App\Support\PatientPush (type `appointment`) if that class exists on
     * this deployment. Best-effort: nothing here can fail the transition that
     * triggered it.
     *
     * Only the facility, date, time and clinician's name are said — a push
     * lands on a lock screen, which is a public surface.
     */
    public function notifyPatient(Appointment $appointment, string $event, bool $push): void
    {
        try {
            $patient = $appointment->patient ?? Patient::query()->find($appointment->patient_id);

            if (! $patient) {
                return;
            }

            $when = $appointment->scheduled_at?->copy()->timezone(config('app.timezone'));

            $replace = [
                'facility' => $appointment->facility?->name
                    ?? Facility::withoutGlobalScope('isolate_demo')->whereKey($appointment->facility_id)->value('name')
                    ?? __('desk_api.the_facility'),
                'date'     => $when?->translatedFormat('j F Y') ?? '',
                'time'     => $when?->format('H:i') ?? '',
                'provider' => $appointment->provider?->name ?? '',
            ];

            $title   = __("desk_api.notify_{$event}_title");
            $message = __("desk_api.notify_{$event}_message", $replace);
            $type    = 'appointment_' . $event;

            DatabaseNotification::create([
                'id'              => (string) Str::uuid(),
                'type'            => $type,
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->getKey(),
                'data'            => [
                    'type'           => $type,
                    'severity'       => 'normal',
                    'title'          => $title,
                    'message'        => $message,
                    'appointment_id' => $appointment->id,
                    'action_url'     => '/appointments',
                    'action_label'   => __('desk_api.notify_action_label'),
                ],
                'read_at'         => null,
            ]);

            if ($push && class_exists(\App\Support\PatientPush::class)) {
                \App\Support\PatientPush::send($patient, 'appointment', $title, $message, [
                    'appointment_id' => $appointment->id,
                    'action_url'     => '/appointments',
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('front-desk: patient notification failed', [
                'appointment_id' => $appointment->id,
                'event'          => $event,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /** The facility's book. A blank facility yields a query that matches nothing — closed, never open. */
    private function book(string $facilityId): Builder
    {
        if ($facilityId === '' || ! Str::isUuid($facilityId)) {
            return Appointment::query()->whereRaw('1 = 0');
        }

        return Appointment::query()->where('facility_id', $facilityId);
    }

    private function onDay(string $facilityId, CarbonInterface $day): Builder
    {
        $tz = config('app.timezone');

        return $this->book($facilityId)->whereBetween('scheduled_at', [
            Carbon::instance($day)->timezone($tz)->startOfDay(),
            Carbon::instance($day)->timezone($tz)->endOfDay(),
        ]);
    }

    /** Active postings at the facility whose role holds `clinical_chart`. */
    private function clinicalPostings(string $facilityId): Builder
    {
        $clinicalRoles = StaffCapabilities::roles('clinical_chart');

        return FacilityRoleAssignment::query()
            ->active()
            ->where('facility_id', $facilityId)
            ->whereHas('role', fn (Builder $q) => $q->whereIn('name', $clinicalRoles));
    }

    /**
     * Stamp each appointment's provider with `desk_role`: the role they hold at
     * THIS facility (facility_role_assignments), falling back to the global
     * role — the same order StaffCapabilities::roleFor() resolves.
     *
     * @param  Collection<int,Appointment>  $appointments
     * @return Collection<int,Appointment>
     */
    private function withDeskRoles(Collection $appointments, string $facilityId): Collection
    {
        $providerIds = $appointments->pluck('provider_id')->filter()->unique()->values()->all();

        if ($providerIds === []) {
            return $appointments;
        }

        $roleByUser = FacilityRoleAssignment::query()
            ->active()
            ->where('facility_id', $facilityId)
            ->whereIn('user_id', $providerIds)
            ->with('role:id,name')
            ->get(['user_id', 'role_id'])
            ->mapWithKeys(fn (FacilityRoleAssignment $a) => [(string) $a->user_id => $a->role?->name])
            ->all();

        foreach ($appointments as $appointment) {
            if ($appointment->provider) {
                $appointment->provider->setAttribute(
                    'desk_role',
                    $roleByUser[(string) $appointment->provider_id] ?? $appointment->provider->role?->name,
                );
            }
        }

        return $appointments;
    }

    private function hasOpeningHours(string $facilityId): bool
    {
        return FacilitySchedule::where('facility_id', $facilityId)->where('is_active', true)->exists();
    }

    private function hasPostedHours(string $facilityId, string $providerId): bool
    {
        if ($providerId === '') {
            return false;
        }

        return ProviderAvailability::where('facility_id', $facilityId)
            ->where('provider_id', $providerId)
            ->where('is_active', true)
            ->exists();
    }

    /** The same test AppointmentService::assertAvailability applies to a clinician's posted hours. */
    private function providerAvailableAt(string $facilityId, string $providerId, CarbonInterface $when): bool
    {
        $time = Carbon::instance($when);

        return ProviderAvailability::where('facility_id', $facilityId)
            ->where('provider_id', $providerId)
            ->where('day_of_week', (int) $time->dayOfWeekIso)
            ->where('is_active', true)
            ->where('starts_at', '<=', $time->format('H:i:s'))
            ->where('ends_at', '>=', $time->format('H:i:s'))
            ->exists();
    }

    /**
     * The web desk's booking (StaffPortalController::appointmentsStore): the
     * row AppointmentService::book would have written, minus the opening-hours
     * assertion that has nothing configured to assert against, plus the same
     * `create` audit row the service writes.
     *
     * @param array<string,mixed> $payload
     */
    private function bookWithoutHours(array $payload, DeskActor $actor): Appointment
    {
        return DB::transaction(function () use ($payload, $actor) {
            $appointment = Appointment::create($payload + [
                'billing_deferred'      => true,
                'telemedicine_deferred' => true,
            ]);

            $this->audit($actor, $appointment, 'create', 'Appointment booked.', null);

            return $appointment->fresh();
        });
    }

    /**
     * Move one appointment to a new column status under a row lock and record
     * it twice (audit_events + appointment_status_histories).
     *
     * @param array<string,mixed> $extra
     * @throws FrontDeskRefusal
     */
    private function transition(DeskActor $actor, Appointment $appointment, string $toStatus, string $action, string $reason, array $extra, string $failureKey): Appointment
    {
        try {
            return DB::transaction(function () use ($actor, $appointment, $toStatus, $action, $reason, $extra) {
                /** @var Appointment $locked */
                $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);
                $before = $locked->toArray();
                $from   = $locked->status;

                $locked->update(array_merge(['status' => $toStatus], $extra));

                $this->audit($actor, $locked, $action, $reason, $before);
                $this->history($locked->id, $from, $toStatus, $actor, $reason);

                return $locked->refresh();
            });
        } catch (Throwable $e) {
            throw new FrontDeskRefusal($failureKey, 422);
        }
    }

    /**
     * Same columns AppointmentService::audit() writes, so desk actions, the
     * clinician app's and the web's all land in one readable trail.
     *
     * @param array<string,mixed>|null $before
     */
    private function audit(DeskActor $actor, Appointment $appointment, string $action, ?string $reason, ?array $before): void
    {
        AuditEvent::create([
            'actor_id'      => $actor->userId,
            'facility_id'   => $appointment->facility_id,
            'patient_id'    => $appointment->patient_id,
            'encounter_id'  => $appointment->visit_id,
            'action_type'   => $action,
            'resource_type' => 'appointment',
            'resource_id'   => $appointment->id,
            'source_system' => $actor->channel,
            'ip_address'    => $actor->ip,
            'reason'        => $reason,
            'before_state'  => $before,
            'after_state'   => $appointment->fresh()?->toArray(),
        ]);
    }

    /**
     * The append-only transition ledger. Never updated, never deleted.
     * `reason` is a varchar(255): a desk's free-text cancellation reason (up
     * to 500) is kept whole on the appointment row and trimmed here.
     */
    private function history(string $appointmentId, ?string $from, string $to, DeskActor $actor, ?string $reason, array $metadata = []): void
    {
        AppointmentStatusHistory::record(
            appointmentId: $appointmentId,
            fromStatus: $from,
            toStatus: $to,
            changedBy: $actor->userId,
            changedByType: 'provider',
            reason: $reason === null ? null : mb_substr($reason, 0, 255),
            metadata: ['channel' => $actor->channel] + $metadata,
        );
    }

    /** Give the clinic back the capacity a moved appointment was holding. */
    private function releaseSlot(Appointment $appointment): void
    {
        if (! $appointment->appointment_slot_id) {
            return;
        }

        AppointmentSlot::where('id', $appointment->appointment_slot_id)
            ->where('booked_count', '>', 0)
            ->decrement('booked_count');
    }
}
