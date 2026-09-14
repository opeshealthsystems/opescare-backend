<?php

namespace App\Services\Queue;

use App\Models\Appointment;
use App\Models\AppointmentCheckIn;
use App\Models\Patient;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Services\FrontDesk\DeskActor;
use App\Services\FrontDesk\FrontDeskRefusal;
use App\Services\FrontDesk\FrontDeskService;
use App\Services\Triage\TriageDeskService;
use App\Support\TriageAcuity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The patient queue — every WRITE the waiting-room desk performs, and the one
 * ranked READ the screen is built on.
 *
 * `queue_tickets` and `queue_status_histories` have existed with models since
 * the operational MVP; nothing wrote to them but a demo seeder and nothing read
 * them but a dashboard tile that could therefore never be non-zero. This class
 * is the domain behind the first real surface.
 *
 * ── THE FOUR RULES THIS CLASS ENFORCES ─────────────────────────────────────
 *
 * 1. A TIMESTAMP, ONCE SET, IS NEVER OVERWRITTEN. `checked_in_at`, `called_at`,
 *    `service_started_at`, `completed_at` and `cancelled_at` are each written
 *    at most once, by the transition that owns them, and only when still
 *    null. A patient called twice because they did not hear the first time
 *    keeps their FIRST `called_at`: the wait they actually endured is the one
 *    that is measured. This is what makes the figures in summaryFor()
 *    evidence rather than a claim.
 *
 * 2. EVERY TRANSITION APPENDS A HISTORY ROW. `queue_status_histories` is
 *    append-only; there is no update and no delete anywhere in this class,
 *    and a re-call appends a `called → called` row rather than nothing.
 *
 * 3. THE SORT IS THE FEATURE, AND IT HAPPENS IN SQL. See ranked() — the same
 *    argument as TriageDeskController::index(), and the same trap: ordering a
 *    paginated page in PHP orders each page separately, which is a more
 *    convincing bug than no ordering at all.
 *
 * 4. NOTHING HERE GATES CARE. A ticket is a record of who is waiting, never a
 *    permission to be seen. No clinical code reads this table (held by
 *    tests/Feature/Architecture/CareIsNeverGatedOnQueueTest), and a patient
 *    with no ticket is consulted exactly as one with a ticket is.
 */
class PatientQueueService
{
    public const STATUS_WAITING   = 'waiting';
    public const STATUS_CALLED    = 'called';
    public const STATUS_STARTED   = 'service_started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW   = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';

    /** Still in the room. The dashboard tile counts exactly these. */
    public const ACTIVE_STATUSES = [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_STARTED];

    /** Over, one way or another. */
    public const CLOSED_STATUSES = [self::STATUS_COMPLETED, self::STATUS_NO_SHOW, self::STATUS_CANCELLED];

    /**
     * The ARRIVAL priorities the front desk may record. Deliberately three,
     * deliberately not the five triage levels: a receptionist is not assigning
     * an acuity. 1 sends the patient to triage now; 3 is the courtesy lane
     * (pregnant, infant, elderly, disabled); 5 is everyone else. A triage
     * record for the visit outranks all three the moment it exists.
     *
     * @var list<int>
     */
    public const ARRIVAL_PRIORITIES = [1, 3, 5];

    public const DEFAULT_ARRIVAL_PRIORITY = 5;

    /** The one queue name this pass writes. Stations/transfers are unbuilt. */
    public const QUEUE_GENERAL = 'general';

    public function __construct(
        private readonly FrontDeskService $frontDesk,
        private readonly TriageDeskService $triage,
    ) {
    }

    // ──────────────────────────────────────────────────────────────────
    // Check-in
    // ──────────────────────────────────────────────────────────────────

    /**
     * Put a patient in the queue.
     *
     * With an appointment, the booking is checked in through the front desk
     * service (status → checked_in, visit opened, `appointments.checked_in_at`
     * set) and an `appointment_check_ins` row is written — the richer table
     * that until now had no writer. Without one, a walk-in gets the facility's
     * open visit for the patient (reused if triage already opened it, so the
     * triage acuity and this ticket point at the same encounter).
     *
     * A patient already active in this facility's queue is refused, not
     * duplicated: two tickets are two places in line for one person.
     *
     * @param array{
     *     patient: Patient,
     *     facility_id: string,
     *     actor_id: string,
     *     appointment?: Appointment|null,
     *     priority_level?: int|null,
     *     priority_reason?: string|null,
     *     ip?: string|null
     * } $data
     *
     * @throws QueueRefusal already_in_queue | appointment_not_checkable | number_exhausted
     */
    public function checkIn(array $data): QueueTicket
    {
        $patient     = $data['patient'];
        $facilityId  = $data['facility_id'];
        $actorId     = $data['actor_id'];
        $appointment = $data['appointment'] ?? null;
        $priority    = (int) ($data['priority_level'] ?? self::DEFAULT_ARRIVAL_PRIORITY);

        if (! in_array($priority, self::ARRIVAL_PRIORITIES, true)) {
            throw new \InvalidArgumentException('An arrival priority must be one of ' . implode(',', self::ARRIVAL_PRIORITIES) . '.');
        }

        if ($this->activeTicketFor($patient->id, $facilityId)) {
            throw new QueueRefusal('already_in_queue', 409);
        }

        return DB::transaction(function () use ($patient, $facilityId, $actorId, $appointment, $priority, $data) {
            $visitId          = null;
            $checkInId        = null;

            if ($appointment) {
                $visitId   = $this->checkInAppointment($appointment, $facilityId, $actorId, $data['ip'] ?? null);
                $checkInId = AppointmentCheckIn::create([
                    'appointment_id' => $appointment->id,
                    'patient_id'     => $patient->id,
                    'facility_id'    => $facilityId,
                    'checked_in_by'  => $actorId,
                    'check_in_mode'  => 'manual',
                    'status'         => 'checked_in',
                    'checked_in_at'  => now(),
                ])->id;
            } else {
                $visitId = $this->triage->openVisitFor($patient->id, $facilityId, null)->id;
            }

            $ticket = $this->createTicket([
                'patient_id'      => $patient->id,
                'facility_id'     => $facilityId,
                'visit_id'        => $visitId,
                'appointment_id'  => $appointment?->id,
                'current_queue'   => self::QUEUE_GENERAL,
                'status'          => self::STATUS_WAITING,
                'priority_level'  => $priority,
                'priority_reason' => $data['priority_reason'] ?? null,
                'checked_in_at'   => now(),
            ]);

            QueueStatusHistory::record($ticket->id, null, self::STATUS_WAITING, null, $actorId, 'checked_in');

            return $ticket;
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // Transitions
    // ──────────────────────────────────────────────────────────────────

    /**
     * Call the patient to the room. Allowed from `waiting` and — a re-call,
     * for the patient who did not hear — from `called`. `called_at` is set on
     * the first call only.
     */
    public function call(QueueTicket $ticket, string $actorId): QueueTicket
    {
        return $this->transition($ticket, [self::STATUS_WAITING, self::STATUS_CALLED], self::STATUS_CALLED, $actorId, [
            'called_at'      => now(),
            'assigned_to_id' => $actorId,
        ]);
    }

    /**
     * The consultation has begun. From `called`, or straight from `waiting`
     * when the clinician fetched the patient themselves — in which case
     * `called_at` is set now too, so the wait is still measured.
     */
    public function start(QueueTicket $ticket, string $actorId): QueueTicket
    {
        return $this->transition($ticket, [self::STATUS_WAITING, self::STATUS_CALLED], self::STATUS_STARTED, $actorId, [
            'called_at'          => now(),
            'service_started_at' => now(),
            'assigned_to_id'     => $actorId,
        ]);
    }

    public function complete(QueueTicket $ticket, string $actorId): QueueTicket
    {
        return $this->transition($ticket, [self::STATUS_STARTED], self::STATUS_COMPLETED, $actorId, [
            'completed_at' => now(),
        ]);
    }

    /** Called and did not come. Only from `called` — nobody is a no-show before being called. */
    public function noShow(QueueTicket $ticket, string $actorId, ?string $reason = null): QueueTicket
    {
        return $this->transition($ticket, [self::STATUS_CALLED], self::STATUS_NO_SHOW, $actorId, [
            'cancelled_at'  => now(),
            'status_reason' => $reason,
        ], $reason);
    }

    /** Left, or was entered in error. Never from `service_started`: a consultation that began is completed, not cancelled. */
    public function cancel(QueueTicket $ticket, string $actorId, string $reason): QueueTicket
    {
        return $this->transition($ticket, [self::STATUS_WAITING, self::STATUS_CALLED], self::STATUS_CANCELLED, $actorId, [
            'cancelled_at'  => now(),
            'status_reason' => $reason,
        ], $reason);
    }

    // ──────────────────────────────────────────────────────────────────
    // Reading the room
    // ──────────────────────────────────────────────────────────────────

    /**
     * Every active ticket at ONE facility, MOST URGENT FIRST, then longest
     * waiting — sorted in SQL, before any limit.
     *
     * The priority is derived, in this precedence:
     *
     *   1. the triage acuity for the ticket's visit — the newest triage record
     *      on that visit, read exactly as TriageDeskService::currentPriority()
     *      reads it (latest reassessment, else latest score, else the acuity
     *      on the record), ranked P1..P5 → 1..5;
     *   2. else the arrival priority the desk recorded (`priority_level`);
     *   3. else 6 — last for urgency, but IN the queue.
     *
     * Ties break on `checked_in_at` ascending: two equals are separated by
     * who has waited longer, never by who was typed in last.
     *
     * The derivation is selected as `priority_rank` and the raw triage level
     * as `triage_priority`, so the screen can say WHERE a priority came from
     * without a second query per row.
     */
    public function ranked(string $facilityId): Builder
    {
        $triageCurrent = '(SELECT COALESCE('
            . '(SELECT r.new_priority FROM triage_reassessments r WHERE r.triage_assessment_id = tr.id ORDER BY r.reassessed_at DESC, r.created_at DESC LIMIT 1), '
            . '(SELECT s.priority_level FROM triage_scores s WHERE s.triage_assessment_id = tr.id ORDER BY s.scored_at DESC, s.created_at DESC LIMIT 1), '
            . 'tr.acuity_score) '
            . 'FROM triage_records tr WHERE tr.visit_id = queue_tickets.visit_id '
            . 'ORDER BY tr.created_at DESC LIMIT 1)';

        // Ranked explicitly, never alphabetically, and an unrecognised triage
        // value yields NULL so the arrival priority takes over rather than an
        // accidental rank.
        $triageRank = "CASE {$triageCurrent}"
            . " WHEN '" . TriageAcuity::P1 . "' THEN 1"
            . " WHEN '" . TriageAcuity::P2 . "' THEN 2"
            . " WHEN '" . TriageAcuity::P3 . "' THEN 3"
            . " WHEN '" . TriageAcuity::P4 . "' THEN 4"
            . " WHEN '" . TriageAcuity::P5 . "' THEN 5"
            . ' ELSE NULL END';

        $arrivalRank = 'CASE WHEN queue_tickets.priority_level BETWEEN 1 AND 5 THEN queue_tickets.priority_level ELSE NULL END';

        $rank = "COALESCE({$triageRank}, {$arrivalRank}, 6)";

        return QueueTicket::query()
            ->select('queue_tickets.*')
            ->selectRaw("{$rank} AS priority_rank")
            ->selectRaw("{$triageCurrent} AS triage_priority")
            ->where('queue_tickets.facility_id', $facilityId)
            ->whereIn('queue_tickets.status', self::ACTIVE_STATUSES)
            ->orderByRaw($rank)
            ->orderBy('queue_tickets.checked_in_at')
            ->orderBy('queue_tickets.created_at');
    }

    /** Tickets closed on one day at one facility, most recent first. */
    public function closedOn(string $facilityId, CarbonInterface $day): Builder
    {
        return QueueTicket::query()
            ->where('facility_id', $facilityId)
            ->whereIn('status', self::CLOSED_STATUSES)
            ->whereBetween('checked_in_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderByDesc('checked_in_at');
    }

    /**
     * The day's measured figures for one facility — the numbers that let this
     * platform PROVE it shortens a wait rather than assert it.
     *
     * Every duration is between two timestamps this class wrote and never
     * rewrote (rule 1). Median rather than mean: one patient who slept in the
     * chairs for four hours must not make the day look worse than it was, and
     * one who was seen instantly must not make it look better. Postgres
     * ordered-set aggregate with FILTER; no MySQL date function anywhere.
     *
     * @return array{
     *     checked_in: int, waiting: int, active: int, seen: int, left: int,
     *     median_wait_seconds: int|null, longest_wait_seconds: int|null,
     *     median_service_seconds: int|null
     * }
     */
    public function summaryFor(string $facilityId, CarbonInterface $day): array
    {
        $row = DB::table('queue_tickets')
            ->selectRaw(
                'COUNT(*) AS checked_in, '
                . 'COUNT(*) FILTER (WHERE status = ?) AS waiting, '
                . 'COUNT(*) FILTER (WHERE status IN (?, ?, ?)) AS active, '
                . 'COUNT(*) FILTER (WHERE status = ?) AS seen, '
                . 'COUNT(*) FILTER (WHERE status IN (?, ?)) AS left_count, '
                . 'PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (called_at - checked_in_at))) FILTER (WHERE called_at IS NOT NULL) AS median_wait_seconds, '
                . 'MAX(EXTRACT(EPOCH FROM (called_at - checked_in_at))) FILTER (WHERE called_at IS NOT NULL) AS longest_wait_seconds, '
                . 'PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (completed_at - service_started_at))) FILTER (WHERE completed_at IS NOT NULL AND service_started_at IS NOT NULL) AS median_service_seconds',
                [
                    self::STATUS_WAITING,
                    self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_STARTED,
                    self::STATUS_COMPLETED,
                    self::STATUS_NO_SHOW, self::STATUS_CANCELLED,
                ]
            )
            ->where('facility_id', $facilityId)
            ->whereBetween('checked_in_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->first();

        $seconds = fn ($v) => $v === null ? null : (int) round((float) $v);

        return [
            'checked_in'             => (int) ($row->checked_in ?? 0),
            'waiting'                => (int) ($row->waiting ?? 0),
            'active'                 => (int) ($row->active ?? 0),
            'seen'                   => (int) ($row->seen ?? 0),
            'left'                   => (int) ($row->left_count ?? 0),
            'median_wait_seconds'    => $seconds($row->median_wait_seconds ?? null),
            'longest_wait_seconds'   => $seconds($row->longest_wait_seconds ?? null),
            'median_service_seconds' => $seconds($row->median_service_seconds ?? null),
        ];
    }

    /**
     * The two durations one ticket can honestly report, in seconds.
     *
     * `waited` runs from check-in to the first call, or to now while still
     * waiting; `served` from the start of service to completion, or to now
     * while in the room. Null where the ticket never reached that stage.
     *
     * @return array{waited: int|null, served: int|null}
     */
    public function durations(QueueTicket $ticket, ?CarbonInterface $now = null): array
    {
        $now = $now ?? now();

        $waited = null;
        if ($ticket->checked_in_at) {
            $end = $ticket->called_at
                ?? ($ticket->status === self::STATUS_WAITING ? $now : ($ticket->cancelled_at ?? null));
            $waited = $end ? max(0, $ticket->checked_in_at->diffInSeconds($end, false)) : null;
        }

        $served = null;
        if ($ticket->service_started_at) {
            $end    = $ticket->completed_at ?? ($ticket->status === self::STATUS_STARTED ? $now : null);
            $served = $end ? max(0, $ticket->service_started_at->diffInSeconds($end, false)) : null;
        }

        return ['waited' => $waited === null ? null : (int) $waited, 'served' => $served === null ? null : (int) $served];
    }

    /** The patient's active ticket at this facility, if any. */
    public function activeTicketFor(string $patientId, string $facilityId): ?QueueTicket
    {
        return QueueTicket::query()
            ->where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderByDesc('checked_in_at')
            ->first();
    }

    /** Seconds → "12 min" / "1 h 05 min", translated; null → an em dash. */
    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $minutes = intdiv(max(0, $seconds), 60);

        if ($minutes < 60) {
            return __('queue_desk.duration_minutes', ['n' => $minutes]);
        }

        return __('queue_desk.duration_hours', [
            'h' => intdiv($minutes, 60),
            'm' => str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT),
        ]);
    }

    /** The short form printed on the ticket and the board: the day's serial. */
    public static function displayNumber(QueueTicket $ticket): string
    {
        $number = (string) $ticket->queue_number;
        $pos    = strrpos($number, '-');

        return $pos === false ? $number : substr($number, $pos + 1);
    }

    // ──────────────────────────────────────────────────────────────────
    // internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * One transition: guard the source status under a row lock, set only the
     * timestamps still null, append the history row. The caller's facility
     * scope has already been applied — this method trusts the ticket it is
     * handed because the controller found it inside the facility bound.
     *
     * @param  list<string>          $from
     * @param  array<string, mixed>  $stamps  timestamp / attribution columns to set IF NULL
     *
     * @throws QueueRefusal not_allowed_from_<status>
     */
    private function transition(QueueTicket $ticket, array $from, string $to, string $actorId, array $stamps, ?string $reason = null): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $from, $to, $actorId, $stamps, $reason) {
            /** @var QueueTicket $locked */
            $locked = QueueTicket::lockForUpdate()->findOrFail($ticket->id);

            if (! in_array($locked->status, $from, true)) {
                throw new QueueRefusal('not_allowed_from_' . $locked->status, 409);
            }

            $update = ['status' => $to];

            foreach ($stamps as $column => $value) {
                // Rule 1: a value already written stays exactly as written.
                if ($locked->{$column} === null) {
                    $update[$column] = $value;
                }
            }

            $locked->update($update);

            QueueStatusHistory::record($locked->id, $ticket->status, $to, null, $actorId, $reason);

            return $locked->refresh();
        });
    }

    /**
     * Check a booked appointment in through the front desk service, or reuse
     * the visit it already opened when the booking was checked in earlier
     * (the patient stepped out and came back to the desk). Returns the visit
     * id the ticket should carry.
     *
     * @throws QueueRefusal appointment_not_checkable
     */
    private function checkInAppointment(Appointment $appointment, string $facilityId, string $actorId, ?string $ip): ?string
    {
        if (in_array($appointment->status, FrontDeskService::CHECKED_IN_STATUSES, true)) {
            return $appointment->visit_id;
        }

        try {
            $actor = new DeskActor($actorId, $facilityId, $ip, 'portal');

            return $this->frontDesk->checkIn($actor, $appointment)->visit_id;
        } catch (FrontDeskRefusal $e) {
            throw new QueueRefusal('appointment_not_checkable', $e->status);
        }
    }

    /**
     * Insert the ticket with the next number of the day for this facility.
     *
     * `queue_number` is unique per facility (schema) and must be readable
     * aloud in a waiting room, so it is `YYMMDD-NNN`: the day keeps it unique
     * across days, the serial is what the board shows. Two receptionists
     * checking in at the same instant race on the serial; the unique index
     * catches it and the insert is retried with a fresh count.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws QueueRefusal number_exhausted
     */
    private function createTicket(array $attributes): QueueTicket
    {
        $prefix = now()->format('ymd') . '-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $serial = QueueTicket::query()
                ->where('facility_id', $attributes['facility_id'])
                ->where('queue_number', 'like', $prefix . '%')
                ->count() + 1;

            $attributes['queue_number'] = $prefix . str_pad((string) $serial, 3, '0', STR_PAD_LEFT);

            try {
                // Nested inside checkIn()'s transaction this becomes a
                // SAVEPOINT, which is the point: a unique violation aborts a
                // Postgres transaction, and only a rollback to the savepoint
                // lets the retry run in the same transaction.
                return DB::transaction(fn () => QueueTicket::create($attributes));
            } catch (QueryException $e) {
                // 23505 = unique_violation. Anything else is not ours to retry.
                if (($e->errorInfo[0] ?? null) !== '23505') {
                    throw $e;
                }
            }
        }

        throw new QueueRefusal('number_exhausted', 409);
    }
}
