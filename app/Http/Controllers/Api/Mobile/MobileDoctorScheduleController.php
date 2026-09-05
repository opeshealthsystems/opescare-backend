<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentStatusHistory;
use App\Models\AuditEvent;
use App\Models\CriticalValueAlert;
use App\Models\Facility;
use App\Models\User;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mobile Provider API — the clinician's day: today, the diary, one appointment
 * and the bookings awaiting a decision.
 *
 * Serves the `home / schedule / appointments / appointment-requests` half of
 * the client contract in `lib/api/doctorQueries.ts` (mobile). Every route sits
 * behind `auth.provider` (AuthenticateMobileProvider), so:
 *
 *     facility_id ← $request->attributes 'provider_facility_id'
 *     provider_id ← $request->attributes 'provider_user_id'
 *
 * Both are stamped by the middleware from the bearer token. NOTHING in this
 * file reads a facility or a clinician id from the body, the query string, a
 * header or the session, and no validator here declares a `facility_id` or
 * `provider_id` key — a body carrying one is simply not consulted. That is the
 * platform rule: a caller who could name their own facility could read every
 * appointment in the country.
 *
 * Scoping, twice over:
 *   • `facility_id = provider_facility_id` on every query — a clinician never
 *     sees another facility's register;
 *   • `provider_id = provider_user_id` wherever the endpoint means "my work" —
 *     the diary, the day, the requests. A colleague's list is not this
 *     clinician's list even inside the same hospital.
 *
 * No patient enumeration. There is no search parameter, no LIKE, and no way to
 * ask for a patient. The only patients that appear are the ones the clinician's
 * own appointments already name. Exact-Health-ID lookup lives in
 * MobilePrescribingController and stays there.
 *
 * Auditing. Every state transition writes an `audit_events` row in exactly the
 * shape AppointmentService::audit() writes (same actor / facility / patient /
 * resource / before / after), plus an append-only `appointment_status_histories`
 * row so the transition ledger is complete. Cancellation is delegated to
 * AppointmentService::cancel(), which also releases the booked slot.
 *
 * Two deliberate deviations from AppointmentService, both about vocabulary:
 *   1. AppointmentService::checkIn() accepts only `scheduled|confirmed`, but
 *      every patient booking on this deployment is written `booked`
 *      (MobileAppointmentController::book), so the service would refuse the
 *      real rows. The transition is therefore done here, over the wider status
 *      vocabulary this database actually contains.
 *   2. AppointmentService::reschedule() closes the row and books a NEW one; the
 *      client contract expects the same appointment to move. This moves it in
 *      place and releases the slot it no longer occupies.
 */
class MobileDoctorScheduleController extends Controller
{
    /** Wire vocabulary (`StaffAppointmentStatus`) ← the wider column vocabulary. */
    private const WIRE_STATUS = [
        'booked'      => 'scheduled',
        'scheduled'   => 'scheduled',
        'pending'     => 'scheduled',
        'requested'   => 'scheduled',
        'confirmed'   => 'confirmed',
        'checked_in'  => 'arrived',
        'arrived'     => 'arrived',
        'in_progress' => 'in_progress',
        'completed'   => 'completed',
        'cancelled'   => 'cancelled',
        'canceled'    => 'cancelled',
        // A rescheduled row is a closed row that was replaced. The contract's
        // status union has no word for it; `cancelled` is the honest nearest.
        'rescheduled' => 'cancelled',
        'no_show'     => 'no_show',
    ];

    /** Column values that mean "booked, nobody has confirmed it yet". */
    private const PENDING_STATUSES = ['booked', 'scheduled', 'pending', 'requested'];

    /** Column values a check-in may follow. */
    private const CHECK_IN_FROM = ['booked', 'scheduled', 'pending', 'requested', 'confirmed'];

    /** Column values a no-show may follow. */
    private const NO_SHOW_FROM = ['booked', 'scheduled', 'pending', 'requested', 'confirmed', 'checked_in', 'arrived'];

    /** Terminal column values — nothing moves out of these. */
    private const CLOSED_STATUSES = ['completed', 'cancelled', 'canceled', 'no_show', 'rescheduled'];

    /** Column values that are still live on the day. */
    private const ACTIVE_STATUSES = ['booked', 'scheduled', 'pending', 'requested', 'confirmed', 'checked_in', 'arrived', 'in_progress'];

    /** How many days ahead `appointment-requests` looks when the client says nothing. */
    private const REQUEST_DAYS_DEFAULT = 30;

    /** Rows on the home screen's "next today" list. */
    private const HOME_NEXT_LIMIT = 5;

    /** Rows on the home screen's urgent-results list. */
    private const HOME_URGENT_LIMIT = 5;

    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    // ──────────────────────────────────────────────────────────────────
    // The clinician
    // ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/doctor/me — the signed-in clinician's account row.
     *
     * Deliberately separate from /auth/provider/me: the app's doctor data layer
     * expects the `{ data: … }` envelope every other clinician endpoint uses.
     */
    public function me(Request $request): JsonResponse
    {
        $user = User::with('role')->find($this->providerId($request));

        if (! $user) {
            return $this->refuse('provider_not_found', 401);
        }

        $facilityId = $this->facilityId($request);

        return response()->json([
            'data' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'role'          => $user->role?->name,
                'facility_id'   => $facilityId,
                'facility_name' => $this->facilityName($facilityId),
                'is_active'     => $user->status === 'active',
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Home
    // ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/doctor/home — the landing screen's counts and lists.
     *
     * Every figure is counted here, against this database, for THIS clinician
     * at THIS facility. Nothing is estimated and nothing is hard-coded: a zero
     * on this screen is a real count of an empty set.
     *
     * `meta.counts_basis` names the table each figure came from, so a clinician
     * (or the next engineer) can tell what "results to acknowledge" is counting
     * on this deployment. The only acknowledgement ledger this schema has is
     * `critical_value_alerts`, reached through lab_results → lab_orders and
     * scoped to the orders this clinician placed at this facility — so on the
     * finder, "unacknowledged results" and "critical unacknowledged" are the
     * same set. They are reported as such rather than invented apart.
     */
    public function home(Request $request): JsonResponse
    {
        $facilityId = $this->facilityId($request);
        $userId     = $this->providerId($request);

        if (! $facilityId || ! $userId) {
            return $this->refuse('provider_not_found', 401);
        }

        $user = User::with('role')->find($userId);

        if (! $user) {
            return $this->refuse('provider_not_found', 401);
        }

        $tz         = config('app.timezone');
        $now        = Carbon::now($tz);
        $dayStart   = $now->copy()->startOfDay();
        $dayEnd     = $now->copy()->endOfDay();

        $todayBase = fn (): Builder => $this->mine($request)
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd]);

        // "Appointments today" is what is still standing today — a cancelled
        // booking is not an appointment the clinician has.
        $appointmentsToday = $todayBase()->whereIn('status', self::ACTIVE_STATUSES)->count();

        $next = $todayBase()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('scheduled_at', '>=', $now)
            ->with(['patient', 'facility:id,name', 'slot:id,starts_at,ends_at'])
            ->orderBy('scheduled_at')
            ->limit(self::HOME_NEXT_LIMIT)
            ->get();

        $urgent = $this->unacknowledgedCriticalValues($facilityId, $userId)
            ->with('patient')
            ->orderByDesc('created_at')
            ->limit(self::HOME_URGENT_LIMIT)
            ->get();

        $criticalPending = $this->unacknowledgedCriticalValues($facilityId, $userId)->count();

        return response()->json([
            'data' => [
                'provider' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->role?->name,
                ],
                'counts' => [
                    'appointments_today'       => $appointmentsToday,
                    'unread_messages'          => $this->unreadMessageCount($userId),
                    'unacknowledged_results'   => $criticalPending,
                    'critical_unacknowledged'  => $criticalPending,
                ],
                'next_appointments' => $next->map(fn (Appointment $a) => $this->formatHomeAppointment($a))->values(),
                'urgent_results'    => $urgent->map(fn (CriticalValueAlert $c) => $this->formatCriticalValue($c))->values(),
            ],
            'meta' => [
                'counts_basis' => [
                    'appointments_today'      => 'appointments (this clinician, this facility, today, not cancelled)',
                    'unread_messages'         => 'messages (threads this clinician participates in, newer than last_read_at)',
                    'unacknowledged_results'  => 'critical_value_alerts (unacknowledged, on lab orders this clinician placed at this facility)',
                    'critical_unacknowledged' => 'critical_value_alerts (same set — this schema records no non-critical result acknowledgement)',
                ],
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Schedule / appointments
    // ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/doctor/appointments — this clinician's own diary.
     * GET /api/mobile/doctor/schedule    — the same list under the name the
     *                                      contract's endpoint table uses.
     *
     * Query: from=YYYY-MM-DD, to=YYYY-MM-DD, status[]=…, page, per_page.
     * `from` defaults to today and `to` defaults to `from`, so a bare call is
     * "my day", never "every appointment ever filed".
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from'     => ['nullable', 'date_format:Y-m-d'],
            'to'       => ['nullable', 'date_format:Y-m-d'],
            'status'   => ['nullable'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $tz   = config('app.timezone');
        $from = Carbon::parse($validated['from'] ?? Carbon::now($tz)->toDateString(), $tz)->startOfDay();
        $to   = Carbon::parse($validated['to'] ?? $from->toDateString(), $tz)->endOfDay();

        // A backwards window is a client bug, not a licence to scan the table.
        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }

        $query = $this->mine($request)
            ->whereBetween('scheduled_at', [$from, $to])
            ->with(['patient', 'provider:id,name', 'facility:id,name', 'slot:id,starts_at,ends_at'])
            ->orderBy('scheduled_at');

        // Narrowing an already-scoped diary, never widening it.
        $columnStatuses = $this->columnStatusesFor($validated['status'] ?? null);

        if ($columnStatuses !== null) {
            $query->whereIn('status', $columnStatuses);
        }

        $page = $query->paginate(
            perPage: (int) ($validated['per_page'] ?? 50),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($this->paginated($page), 200);
    }

    /** GET /api/mobile/doctor/appointments/{id} — one row from this clinician's own diary. */
    public function show(Request $request, string $id): JsonResponse
    {
        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        return response()->json(['data' => $this->formatAppointment($appointment)], 200);
    }

    /**
     * PUT /api/mobile/doctor/appointments/{id} — move the appointment.
     *
     * `duration_minutes` is accepted (the client echoes the row's current value
     * back) but cannot be stored: `appointments` has no duration column and the
     * figure is derived from the booked slot. The response carries the derived
     * value, so the client is never told a duration that was not recorded.
     */
    public function reschedule(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            return $this->refuse('appointment_closed', 409);
        }

        $when = Carbon::parse($validated['scheduled_at'], config('app.timezone'));

        try {
            $appointment = DB::transaction(function () use ($appointment, $when, $request) {
                /** @var Appointment $locked */
                $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);
                $before = $locked->toArray();

                // The appointment no longer occupies the slot it was booked
                // into, so the capacity goes back to the clinic rather than
                // staying spent on a visit that has moved off it.
                $this->releaseSlot($locked);

                $locked->update([
                    'scheduled_at'        => $when,
                    'appointment_slot_id' => null,
                ]);

                $this->audit($request, $locked, 'reschedule', __('doctor_schedule.reason_rescheduled'), $before);
                $this->recordStatusHistory($locked->id, $locked->status, $locked->status, $this->providerId($request), __('doctor_schedule.reason_rescheduled'));

                $locked->refresh();

                return $locked;
            });
        } catch (\Throwable $e) {
            return $this->refuse('reschedule_failed', 422);
        }

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    /**
     * PATCH /api/mobile/doctor/appointments/{id}/status — `{ status }`.
     *
     * The guarded endpoints below are the preferred route for check-in,
     * no-show and cancellation; this one funnels into exactly the same code so
     * the two paths can never diverge.
     */
    public function setStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:scheduled,confirmed,arrived,in_progress,completed,cancelled,no_show'],
        ]);

        return match ($validated['status']) {
            'arrived'   => $this->checkIn($request, $id),
            'no_show'   => $this->noShow($request, $id),
            'cancelled' => $this->cancel($request, $id),
            default     => $this->applyPlainStatus($request, $id, $validated['status']),
        };
    }

    /** POST /api/mobile/doctor/appointments/{id}/check-in — 422 unless still booked or confirmed. */
    public function checkIn(Request $request, string $id): JsonResponse
    {
        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        if (in_array($appointment->status, ['checked_in', 'arrived'], true)) {
            return $this->refuse('already_checked_in', 409);
        }

        if (! in_array($appointment->status, self::CHECK_IN_FROM, true)) {
            return $this->refuse('not_check_in_eligible', 422);
        }

        try {
            $this->transition($request, $appointment, 'checked_in', 'check_in', __('doctor_schedule.reason_checked_in'), [
                'checked_in_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            return $this->refuse('check_in_failed', 422);
        }

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    /** POST /api/mobile/doctor/appointments/{id}/no-show — 409 if already a no-show. */
    public function noShow(Request $request, string $id): JsonResponse
    {
        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        if ($appointment->status === 'no_show') {
            return $this->refuse('already_no_show', 409);
        }

        if (! in_array($appointment->status, self::NO_SHOW_FROM, true)) {
            return $this->refuse('not_no_show_eligible', 422);
        }

        try {
            $this->transition($request, $appointment, 'no_show', 'no_show', __('doctor_schedule.reason_no_show'), [
                'no_show_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            return $this->refuse('no_show_failed', 422);
        }

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    /**
     * POST /api/mobile/doctor/appointments/{id}/cancel — `{ cancel_reason? }`.
     *
     * Delegated to AppointmentService::cancel(), which is what already cancels
     * appointments on the web side: it locks the row, releases the slot and
     * writes the audit_events row. A reason is required by the service, so an
     * omitted one becomes the recorded default rather than an empty string.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        if (in_array($appointment->status, ['cancelled', 'canceled'], true)) {
            return $this->refuse('already_cancelled', 422);
        }

        // A visit that has already happened, or that the patient already failed
        // to attend, is not a booking that can be withdrawn. Without this the
        // endpoint would rewrite a completed consultation as `cancelled` —
        // erasing, on the register, a visit that took place.
        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            return $this->refuse('appointment_closed', 409);
        }

        $reason = $validated['cancel_reason'] ?? __('doctor_schedule.reason_cancelled');
        $from   = $appointment->status;

        try {
            $this->appointments->cancel($appointment, $reason, $this->providerId($request));
        } catch (\Throwable $e) {
            return $this->refuse('cancel_failed', 422);
        }

        $this->recordStatusHistory($appointment->id, $from, 'cancelled', $this->providerId($request), $reason);

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Appointment requests
    // ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/doctor/appointment-requests — bookings awaiting a decision.
     *
     * There is no separate "request" table on this platform: a request IS an
     * appointment that nobody has confirmed yet. `status=accepted` is therefore
     * the ones this clinician confirmed, and `status=declined` the ones THIS
     * clinician cancelled — a cancellation by the patient is not a decline and
     * is not listed as one.
     */
    public function requests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['nullable', 'string', 'in:pending,accepted,declined'],
            'days'     => ['nullable', 'integer', 'min:1', 'max:365'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $decision = $validated['status'] ?? 'pending';
        $days     = (int) ($validated['days'] ?? self::REQUEST_DAYS_DEFAULT);
        $now      = Carbon::now(config('app.timezone'));

        $query = $this->mine($request)
            ->whereBetween('scheduled_at', [$now, $now->copy()->addDays($days)])
            ->with(['patient', 'provider:id,name', 'facility:id,name', 'slot:id,starts_at,ends_at'])
            ->orderBy('scheduled_at');

        match ($decision) {
            'accepted' => $query->where('status', 'confirmed'),
            'declined' => $query->whereIn('status', ['cancelled', 'canceled'])
                                ->where('cancelled_by_id', $this->providerId($request)),
            default    => $query->whereIn('status', self::PENDING_STATUSES),
        };

        $page = $query->paginate(
            perPage: (int) ($validated['per_page'] ?? 50),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($this->paginated($page), 200);
    }

    /** POST /api/mobile/doctor/appointment-requests/{id}/accept → the appointment, now confirmed. */
    public function accept(Request $request, string $id): JsonResponse
    {
        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('request_not_found', 404);
        }

        if (! in_array($appointment->status, self::PENDING_STATUSES, true)) {
            return $this->refuse('not_pending', 409);
        }

        try {
            $this->transition($request, $appointment, 'confirmed', 'accept', __('doctor_schedule.reason_accepted'));
        } catch (\Throwable $e) {
            return $this->refuse('decision_failed', 422);
        }

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    /** POST /api/mobile/doctor/appointment-requests/{id}/decline — `{ reason? }` → now cancelled. */
    public function decline(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('request_not_found', 404);
        }

        if (! in_array($appointment->status, self::PENDING_STATUSES, true)) {
            return $this->refuse('not_pending', 409);
        }

        $reason = $validated['reason'] ?? __('doctor_schedule.reason_declined');
        $from   = $appointment->status;

        try {
            $this->appointments->cancel($appointment, $reason, $this->providerId($request));
        } catch (\Throwable $e) {
            return $this->refuse('decision_failed', 422);
        }

        $this->recordStatusHistory($appointment->id, $from, 'cancelled', $this->providerId($request), $reason);

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Identity — the only source of facility and clinician
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility this session may read — set by AuthenticateMobileProvider
     * from the token. Platform rule: facility_id comes only from the request
     * attributes, never from a header, body, query string or session.
     */
    private function facilityId(Request $request): ?string
    {
        $value = $request->attributes->get('provider_facility_id');

        return is_string($value) ? $value : null;
    }

    /** The clinician's user id, likewise stamped by the middleware. */
    private function providerId(Request $request): ?string
    {
        $value = $request->attributes->get('provider_user_id');

        return is_string($value) ? $value : null;
    }

    /**
     * The base query for "my work": this clinician's own appointments at the
     * facility their token was minted for. Both bounds come from the middleware.
     *
     * A null facility or clinician yields a query that matches nothing rather
     * than an unscoped one — failing closed, never open.
     */
    private function mine(Request $request): Builder
    {
        $facilityId = $this->facilityId($request);
        $userId     = $this->providerId($request);

        if (! $facilityId || ! $userId) {
            return Appointment::query()->whereRaw('1 = 0');
        }

        return Appointment::query()
            ->where('facility_id', $facilityId)
            ->where('provider_id', $userId);
    }

    /** One appointment from that same scope, or null. Never a 403 — that would confirm it exists. */
    private function findMine(Request $request, string $id): ?Appointment
    {
        return $this->mine($request)
            ->with(['patient', 'provider:id,name', 'facility:id,name', 'slot:id,starts_at,ends_at'])
            ->find($id);
    }

    /** Re-read a row through the scoped query, so a response can never widen it. */
    private function reload(Appointment $appointment): Appointment
    {
        return Appointment::with(['patient', 'provider:id,name', 'facility:id,name', 'slot:id,starts_at,ends_at'])
            ->findOrFail($appointment->id);
    }

    private function facilityName(?string $facilityId): ?string
    {
        return $facilityId ? Facility::whereKey($facilityId)->value('name') : null;
    }

    /**
     * One refusal shape for every guard in this file: the translated sentence
     * plus a stable machine code.
     *
     * The code matters because SetLocale is registered on the `web` middleware
     * group only (bootstrap/app.php), so an /api/* response ignores
     * Accept-Language and `message` always comes back in the application's
     * default locale. `error_code` is what lets the phone render the refusal in
     * the clinician's own language — the same `error_code` + `message` pair
     * MobileAppointmentController already answers with. The wording lives in
     * lang/{en,fr}/doctor_schedule.php and, client-side, in
     * lib/i18n/pending/doctor_schedule.{en,fr}.json.
     */
    private function refuse(string $key, int $status): JsonResponse
    {
        return response()->json([
            'error_code' => strtoupper($key),
            'message'    => __('doctor_schedule.' . $key),
        ], $status);
    }

    // ──────────────────────────────────────────────────────────────────
    // Transitions + audit
    // ──────────────────────────────────────────────────────────────────

    /**
     * Move one appointment to a new column status, under a row lock, and record
     * it twice: an `audit_events` row shaped exactly like AppointmentService's,
     * and an append-only `appointment_status_histories` row.
     *
     * @param array<string,mixed> $extra
     */
    private function transition(
        Request $request,
        Appointment $appointment,
        string $toStatus,
        string $action,
        string $reason,
        array $extra = [],
    ): void {
        DB::transaction(function () use ($request, $appointment, $toStatus, $action, $reason, $extra) {
            /** @var Appointment $locked */
            $locked = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $before = $locked->toArray();
            $from   = $locked->status;

            $locked->update(array_merge(['status' => $toStatus], $extra));

            $this->audit($request, $locked, $action, $reason, $before);
            $this->recordStatusHistory($locked->id, $from, $toStatus, $this->providerId($request), $reason);
        });
    }

    /**
     * The statuses that carry no side effect of their own: confirming, opening
     * and closing a consultation. Check-in, no-show and cancellation each have
     * their own guarded endpoint and do not come through here.
     */
    private function applyPlainStatus(Request $request, string $id, string $wireStatus): JsonResponse
    {
        $column = match ($wireStatus) {
            'scheduled'   => 'scheduled',
            'confirmed'   => 'confirmed',
            'in_progress' => 'in_progress',
            'completed'   => 'completed',
            default       => null,
        };

        $appointment = $this->findMine($request, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        if ($column === null) {
            return $this->refuse('unsupported_status', 422);
        }

        if (in_array($appointment->status, self::CLOSED_STATUSES, true)) {
            return $this->refuse('appointment_closed', 409);
        }

        try {
            $this->transition($request, $appointment, $column, 'status_change', __('doctor_schedule.reason_status'));
        } catch (\Throwable $e) {
            return $this->refuse('decision_failed', 422);
        }

        return response()->json(['data' => $this->formatAppointment($this->reload($appointment))], 200);
    }

    /**
     * Same columns AppointmentService::audit() writes, so the mobile actions and
     * the web actions land in one readable trail. `source_system` and the client
     * address are added because this actor is holding a phone.
     *
     * @param array<string,mixed>|null $before
     */
    private function audit(Request $request, Appointment $appointment, string $action, ?string $reason, ?array $before = null): void
    {
        AuditEvent::create([
            'actor_id'      => $this->providerId($request),
            'facility_id'   => $appointment->facility_id,
            'patient_id'    => $appointment->patient_id,
            'encounter_id'  => $appointment->visit_id,
            'action_type'   => $action,
            'resource_type' => 'appointment',
            'resource_id'   => $appointment->id,
            'source_system' => 'mobile_doctor',
            'ip_address'    => $request->ip(),
            'reason'        => $reason,
            'before_state'  => $before,
            'after_state'   => $appointment->fresh()?->toArray(),
        ]);
    }

    /** The append-only transition ledger. Never updated, never deleted. */
    private function recordStatusHistory(string $appointmentId, ?string $from, string $to, ?string $actorId, ?string $reason): void
    {
        AppointmentStatusHistory::record(
            appointmentId: $appointmentId,
            fromStatus: $from,
            toStatus: $to,
            changedBy: $actorId,
            changedByType: 'provider',
            reason: $reason,
            metadata: ['channel' => 'mobile_doctor'],
        );
    }

    /** Give the clinic back the capacity a moved or closed appointment was holding. */
    private function releaseSlot(Appointment $appointment): void
    {
        if (! $appointment->appointment_slot_id) {
            return;
        }

        AppointmentSlot::where('id', $appointment->appointment_slot_id)
            ->where('booked_count', '>', 0)
            ->decrement('booked_count');
    }

    // ──────────────────────────────────────────────────────────────────
    // Counting
    // ──────────────────────────────────────────────────────────────────

    /**
     * Messages waiting on this clinician: every message in a thread they are an
     * active participant of, sent by someone else, newer than their own read
     * cursor. Counted, not estimated.
     */
    private function unreadMessageCount(string $userId): int
    {
        $participants = MessageThreadParticipant::where('user_id', $userId)
            ->where('status', 'active')
            ->get(['thread_id', 'last_read_at']);

        if ($participants->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($participants->groupBy(fn ($p) => (string) $p->last_read_at) as $group) {
            $lastReadAt = $group->first()->last_read_at;

            $query = Message::whereIn('thread_id', $group->pluck('thread_id')->all())
                ->where('sender_id', '!=', $userId)
                ->whereNull('deleted_for_sender_at');

            if ($lastReadAt) {
                $query->where('created_at', '>', Carbon::parse($lastReadAt));
            }

            $total += $query->count();
        }

        return $total;
    }

    /**
     * Critical lab values still awaiting acknowledgement on the orders THIS
     * clinician placed at THIS facility.
     *
     * `critical_value_alerts` carries neither a facility nor an ordering
     * clinician, so both bounds are applied through the order the result
     * belongs to (lab_results → lab_orders). An alert on someone else's order,
     * or another facility's, is not this clinician's to see or count.
     *
     * @return Builder<CriticalValueAlert>
     */
    private function unacknowledgedCriticalValues(string $facilityId, string $userId): Builder
    {
        return CriticalValueAlert::query()
            ->where('acknowledged', false)
            ->whereIn('lab_result_id', function ($q) use ($facilityId, $userId) {
                $q->select('lab_results.id')
                    ->from('lab_results')
                    ->join('lab_orders', 'lab_orders.id', '=', 'lab_results.lab_order_id')
                    ->where('lab_orders.facility_id', $facilityId)
                    ->where('lab_orders.ordered_by', $userId);
            });
    }

    // ──────────────────────────────────────────────────────────────────
    // Presentation
    // ──────────────────────────────────────────────────────────────────

    /**
     * Query `status` (the wire vocabulary, one value or a list) → the column
     * values it covers. Null means "no status filter".
     *
     * An unrecognised status narrows to nothing rather than being dropped: a
     * filter the server does not understand must not quietly return everything.
     *
     * @return list<string>|null
     */
    private function columnStatusesFor(mixed $status): ?array
    {
        if ($status === null || $status === '' || $status === []) {
            return null;
        }

        $wanted = is_array($status) ? $status : explode(',', (string) $status);
        $wanted = array_filter(array_map(fn ($s) => trim((string) $s), $wanted), fn ($s) => $s !== '');

        if ($wanted === []) {
            return null;
        }

        $columns = [];

        foreach (self::WIRE_STATUS as $column => $wire) {
            if (in_array($wire, $wanted, true)) {
                $columns[] = $column;
            }
        }

        // Nothing matched — an empty IN () list, which is exactly "no rows".
        return $columns === [] ? ['__none__'] : array_values(array_unique($columns));
    }

    /** Column value → the contract's closed status union. Unknown values pass through verbatim. */
    private function wireStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return self::WIRE_STATUS[$status] ?? $status;
    }

    /**
     * Minutes between the booked slot's start and end. Null when the
     * appointment is not on a slot — `appointments` has no duration column and
     * a made-up length on a diary is a made-up length.
     */
    private function durationMinutes(Appointment $appointment): ?int
    {
        $slot = $appointment->slot;

        if (! $slot || ! $slot->starts_at || ! $slot->ends_at) {
            return null;
        }

        $minutes = Carbon::parse($slot->starts_at)->diffInMinutes(Carbon::parse($slot->ends_at));

        return $minutes > 0 ? (int) $minutes : null;
    }

    /**
     * `StaffAppointment` on the wire.
     *
     * Fields this schema does not record are null, never guessed:
     *   patient_medical_id — the finder keeps no facility MRN, only the Health ID;
     *   visit_mode, location, room, priority — no such columns on `appointments`.
     *
     * @return array<string,mixed>
     */
    private function formatAppointment(Appointment $appointment): array
    {
        $patient = $appointment->patient;

        return [
            'id'                 => $appointment->id,
            'patient_id'         => $appointment->patient_id,
            // The patient relation carries the IsDemoRecord global scope, as it
            // does everywhere else: a null name means scoped out, not missing.
            'patient_name'       => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
            'patient_health_id'  => $patient?->health_id,
            'patient_medical_id' => null,
            'patient_sex'        => $patient?->sex,
            'patient_dob'        => $patient?->date_of_birth?->toDateString(),
            'provider_id'        => $appointment->provider_id,
            'provider_name'      => $appointment->provider?->name,
            'facility_id'        => $appointment->facility_id,
            'facility_name'      => $appointment->facility?->name,
            'scheduled_at'       => $appointment->scheduled_at?->toIso8601String(),
            'duration_minutes'   => $this->durationMinutes($appointment),
            'appointment_type'   => $appointment->appointment_type,
            'reason'             => $appointment->reason,
            'status'             => $this->wireStatus($appointment->status),
            'visit_mode'         => null,
            'location'           => null,
            'room'               => null,
            'priority'           => null,
            'checked_in_at'      => $appointment->checked_in_at?->toIso8601String(),
            'encounter_id'       => $appointment->visit_id,
            'cancel_reason'      => $appointment->cancellation_reason,
            'cancelled_at'       => $appointment->cancelled_at?->toIso8601String(),
            'created_at'         => $appointment->created_at?->toIso8601String(),
        ];
    }

    /**
     * The slimmer `DoctorAppointment` the home screen lists.
     *
     * @return array<string,mixed>
     */
    private function formatHomeAppointment(Appointment $appointment): array
    {
        $patient = $appointment->patient;

        return [
            'id'                => $appointment->id,
            'scheduled_at'      => $appointment->scheduled_at?->toIso8601String(),
            'duration_minutes'  => $this->durationMinutes($appointment),
            'status'            => $this->wireStatus($appointment->status),
            'appointment_type'  => $appointment->appointment_type,
            'visit_mode'        => null,
            'location'          => null,
            'reason'            => $appointment->reason,
            'patient_id'        => $appointment->patient_id,
            'patient_name'      => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
            'patient_health_id' => $patient?->health_id,
        ];
    }

    /**
     * One unacknowledged critical value as the contract's `InboxResult`.
     *
     * Everything here is read off the alert row. The lab's own threshold and
     * value are reproduced verbatim; nothing is re-interpreted.
     *
     * @return array<string,mixed>
     */
    private function formatCriticalValue(CriticalValueAlert $alert): array
    {
        $patient = $alert->patient;

        return [
            'id'                    => $alert->id,
            'category'              => 'lab_result',
            'category_label'        => __('doctor_schedule.critical_value_category'),
            'severity'              => 'critical',
            'priority'              => 'high',
            'is_critical'           => true,
            'title'                 => $alert->test_name,
            'body'                  => __('doctor_schedule.critical_value_body', [
                'value'     => $alert->result_value,
                'threshold' => $alert->critical_threshold,
            ]),
            'patient_id'            => $alert->patient_id,
            'patient_name'          => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
            'patient_health_id'     => $patient?->health_id,
            'reported_at'           => $alert->created_at?->toIso8601String(),
            'read_at'               => null,
            'acknowledged_at'       => null,
            'acknowledged_by'       => null,
            'acknowledged_by_name'  => null,
            'action_route'          => null,
            'action_id'             => $alert->lab_result_id,
        ];
    }

    /**
     * The `DoctorPage<T>` envelope: `{ data, meta: { pagination } }`.
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator<int,Appointment> $page
     * @return array<string,mixed>
     */
    private function paginated($page): array
    {
        return [
            'data' => $page->getCollection()
                ->map(fn (Appointment $a) => $this->formatAppointment($a))
                ->values(),
            'meta' => [
                'pagination' => [
                    'current_page' => $page->currentPage(),
                    'per_page'     => $page->perPage(),
                    'total'        => $page->total(),
                    'last_page'    => $page->lastPage(),
                    'from'         => $page->firstItem(),
                    'to'           => $page->lastItem(),
                ],
            ],
        ];
    }
}
