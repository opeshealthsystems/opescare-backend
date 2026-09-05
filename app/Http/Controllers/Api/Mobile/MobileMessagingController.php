<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CareFacility;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mobile Patient API — Messaging
 *
 * Wraps the Messaging module (App\Modules\Messaging) for the patient mobile
 * app. No patient-facing entry point existed before this — the module was
 * only reachable via the B2B/staff `CommunicationController` (integration
 * client auth). This controller is the real, additive patient surface.
 *
 * Identity: the patient's own `patient_id` (from AuthenticateMobilePatient
 * middleware, never request input) is used directly as the actor identity
 * in message_thread_participants.user_id / messages.sender_id — that column
 * is a plain string with no FK constraint, so no dependency on a linked
 * `users` row existing for every patient.
 *
 * Note: message_threads.patient_id/facility_id/organization_id are typed
 * unsignedBigInteger in the schema, incompatible with this platform's UUID
 * patient/facility ids — deliberately left null on create (see start()) to
 * avoid a Postgres type error; context_type/context_id (string columns)
 * carry the appointment linkage instead.
 *
 * ══════════════════════════════════════════════════════════════════════════
 * WHO A PATIENT MAY WRITE TO
 * ══════════════════════════════════════════════════════════════════════════
 *
 * start() used to take an `appointment_id` and nothing else, so a patient with
 * no appointment on file could not open a conversation with anyone: the
 * compose sheet listed appointments, found none, and offered no way forward.
 * There are now THREE ways in, and the first one has not changed a byte of its
 * behaviour — existing clients and existing threads are untouched.
 *
 *   1. `appointment_id`  (unchanged)   — the provider on one of the patient's
 *                                        own appointments.
 *   2. `facility_id`     (new)         — the facility's FRONT DESK. This is the
 *                                        default, and the reason the button
 *                                        always works.
 *   3. `clinician_id`    (new)         — one named clinician, and ONLY where a
 *                                        care relationship already exists.
 *
 * Exactly one of the three per request. Two is a 422, none is a 422: guessing
 * a recipient is how a message ends up somewhere the patient did not choose.
 *
 * ── Facility is the default, and deliberately so ─────────────────────────
 * Most of what a patient asks — opening hours, help rebooking, "have my
 * results come back", "do you stock this drug" — is not for a named doctor,
 * and routing it to one makes a clinician's inbox unusable. The front desk
 * always exists, so the composer always has a recipient.
 *
 * ── A named clinician needs a care relationship, enforced HERE ────────────
 * clinicianBasis() is MobileDoctorChartController::relationshipBasis() read
 * from the patient's side, basis for basis and in the same order:
 * appointment → care_team → treating_clinician → facility_episode →
 * consent_grant. No basis, no thread — a 403, server-side. The mobile app
 * hides ineligible clinicians as a courtesy; the refusal does not depend on it.
 * There is no directory of clinicians to browse and DM: recipients() answers
 * only with names the patient is ALREADY in care with.
 *
 * ── Never a thread into a void ───────────────────────────────────────────
 * A recipient is only offered, and only accepted, when a human can actually
 * open the result:
 *
 *   • a facility must have at least one active staff account
 *     (deskUserIds()) — the 468 register rows with no accounts behind them
 *     are not messaging destinations, and pretending otherwise would swallow
 *     a patient's question in silence;
 *   • a clinician thread must carry an `appointment` context, because that
 *     context is what MobileDoctorMessagingController::visibleThreads()
 *     derives the patient and the facility from. A relationship that is real
 *     but carries no appointment (care_team, treating_clinician,
 *     facility_episode, consent_grant) is AUTHORISED here and reported as
 *     `deliverable: false`, and start() refuses it with
 *     MESSAGE_RECIPIENT_UNREACHABLE pointing at the facility desk, rather
 *     than writing a message no inbox lists. See the note on
 *     clinicianDeliveryContext().
 */
class MobileMessagingController extends Controller
{
    /**
     * How far back an episode of care at a clinician's own facility still
     * counts as a live relationship. Same window, same reasoning as
     * MobileDoctorChartController::FACILITY_EPISODE_LOOKBACK_DAYS — one rule,
     * read from two sides, not two rules that drift apart.
     */
    private const FACILITY_EPISODE_LOOKBACK_DAYS = 365;

    /**
     * Staff added to a facility thread. Matches the web portal's care-team
     * fan-out (PatientMessagingController::careTeamRecipientIds) so a facility
     * inbox behaves the same whichever side opened the conversation.
     */
    private const MAX_DESK_PARTICIPANTS = 15;

    /** Ceiling on either list in recipients(). A picker, not an export. */
    private const MAX_RECIPIENTS = 25;

    /**
     * Who counts as "the front desk".
     *
     * The participant list IS the disclosure list — StaffMessagingController
     * and MobileDoctorMessagingController both gate on participation, so every
     * account added here is an account that can read the patient's message.
     * "Every non-patient user at the facility" (the web portal's rule) put
     * finance, insurance-claims and pre-authorisation accounts inside a
     * conversation that opens "have my results come back". That is not a
     * routing detail, it is a disclosure, so the desk is an ALLOW-list:
     * unknown and future roles are out until someone puts them in.
     *
     * Reception first — the desk proper, and the account that becomes the
     * thread's nominal recipient when one exists.
     */
    private const DESK_ROLES = [
        'front_desk', 'receptionist', 'clinic_admin', 'facility_admin', 'hospital_admin', 'branch_admin',
    ];

    /**
     * …then the clinical staff, so a small facility with no reception account
     * still has a reader instead of reading as "unreachable". Deliberately
     * absent: insurance, billing, finance, cashier, developer/API, device and
     * kiosk, platform and compliance accounts.
     */
    private const DESK_CLINICAL_ROLES = [
        'doctor', 'multi_doctor', 'consultant', 'resident', 'intern',
        'nurse', 'nurse_supervisor', 'midwife', 'medical_supervisor',
        'pharmacist', 'pharmacy_manager', 'pharmacy_technician', 'dispensing_officer',
        'lab_manager', 'labtech', 'lab_scientist', 'records_officer',
    ];

    public function __construct(
        private readonly MessagingService $messagingService,
        private readonly MessagePermissionService $permissionService,
    ) {
    }

    /** GET /api/mobile/messages/threads — the patient's own conversations. */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        // The patient's own participant rows are the whole authorisation story
        // here: a thread is only listed when this patient is an active
        // participant in it. Keyed by thread_id so the per-thread read cursor
        // is a lookup, not a query.
        $participants = MessageThreadParticipant::where('user_id', $patientId)
            ->where('status', 'active')
            ->get()
            ->keyBy('thread_id');

        $threads = MessageThread::whereIn('id', $participants->keys())
            ->orderByDesc('updated_at')
            ->get();

        // Latest message per thread in two bounded queries instead of one
        // query per thread. MAX(id) is the ordering the detail view uses.
        $lastMessageIds = Message::whereIn('thread_id', $threads->modelKeys())
            ->groupBy('thread_id')
            ->selectRaw('thread_id, MAX(id) AS id')
            ->pluck('id');

        $lastMessages = Message::whereIn('id', $lastMessageIds)->get()->keyBy('thread_id');

        $data = $threads->map(function (MessageThread $thread) use ($patientId, $participants, $lastMessages) {
            $lastMessage = $lastMessages->get($thread->id);
            $participant = $participants->get($thread->id);

            // `last_read_at` is a plain timestamp column and the participant
            // model declares no casts, so it arrives as a string — comparing
            // it with `->lt()` directly is a fatal call-on-string. Parse it.
            $lastReadAt = $participant?->last_read_at
                ? Carbon::parse($participant->last_read_at)
                : null;

            // `lte`, not `lt`. Both `last_read_at` and `messages.created_at` are
            // stored to WHOLE SECONDS, so a strict `<` marked any message that
            // arrived in the same second as a read permanently read — it never
            // appeared as unread and the patient never knew it existed. Erring
            // the other way costs one badge that clears a second later, which is
            // the cheaper mistake by a wide margin. The clinician side of this
            // same comparison had the identical bug.
            $unread = $lastMessage
                && $lastMessage->sender_id !== $patientId
                && (! $lastReadAt || $lastReadAt->lte($lastMessage->created_at));

            return [
                'id'           => $thread->id,
                'title'        => $thread->title,
                'status'       => $thread->status,
                'priority'     => $thread->priority,
                'thread_type'  => $thread->thread_type,
                'updated_at'   => $thread->updated_at?->toISOString(),
                'unread'       => (bool) $unread,
                'last_message' => $lastMessage ? [
                    'body'       => $this->messagingService->decryptBody($lastMessage->body),
                    'is_mine'    => $lastMessage->sender_id === $patientId,
                    'created_at' => $lastMessage->created_at?->toISOString(),
                ] : null,
            ];
        });

        return response()->json(['data' => $data->values()]);
    }

    /** GET /api/mobile/messages/threads/{id} — thread detail + full message list. */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $thread = $this->findThread($id);

        if (! $this->permissionService->canViewThread($patientId, $thread->uuid)) {
            return response()->json([
                'error_code' => 'MESSAGE_ACCESS_DENIED',
                'message'    => 'You do not have access to this conversation.',
            ], 403);
        }

        $messages = $thread->messages()->orderBy('id')->get()->map(fn ($m) => [
            'id'         => $m->id,
            'is_mine'    => $m->sender_id === $patientId,
            'body'       => $this->messagingService->decryptBody($m->body),
            'status'     => $m->status,
            'created_at' => $m->created_at?->toISOString(),
        ]);

        MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $patientId)
            ->update(['last_read_at' => now()]);

        return response()->json(['data' => [
            'id'       => $thread->id,
            'title'    => $thread->title,
            'status'   => $thread->status,
            'messages' => $messages,
        ]]);
    }

    /** POST /api/mobile/messages/threads/{id}/messages — reply in an existing thread. */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $validated = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $thread = $this->findThread($id);
        if (! $this->permissionService->canViewThread($patientId, $thread->uuid)) {
            return response()->json([
                'error_code' => 'MESSAGE_ACCESS_DENIED',
                'message'    => 'You do not have access to this conversation.',
            ], 403);
        }

        try {
            $message = $this->messagingService->sendMessage($thread->uuid, $patientId, $validated['body']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        // `Message` declares no `$touches`, so without this the parent thread's
        // `updated_at` never moves and index()'s `orderByDesc('updated_at')`
        // degrades into thread-creation order — the inbox stops behaving like
        // an inbox after the very first reply.
        $thread->touch();

        return response()->json(['data' => [
            'id'         => $message->id,
            'is_mine'    => true,
            'body'       => $validated['body'],
            'status'     => $message->status,
            'created_at' => $message->created_at?->toISOString(),
        ]], 201);
    }

    /**
     * GET /api/mobile/messages/recipients — who this patient may write to.
     *
     * Two lists, and they are not interchangeable:
     *
     *   `facilities` — front desks. Every entry is a facility with at least one
     *     active staff account, so every entry can actually be answered. The
     *     patient's own care facilities come first and carry a `basis`; an
     *     optional `q` searches the rest of the register by name or city, and a
     *     patient with no care history at all still gets a starting list rather
     *     than an empty screen.
     *
     *   `clinicians` — ONLY people already looking after this patient, each
     *     with the basis that put them on the list. This is not a directory:
     *     there is no way to reach a name that is not already in the patient's
     *     own record, and no search parameter touches this half.
     *
     * `patient_id` comes from the bearer token. Nothing in the query string can
     * name a patient, so there is no "someone else's recipients" to ask for.
     */
    public function recipients(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        return response()->json(['data' => [
            'notice'     => __('messaging_recipients.not_emergency'),
            'facilities' => $this->facilityRecipients($patientId, $query),
            'clinicians' => $this->clinicianRecipients($patientId),
        ]]);
    }

    /**
     * POST /api/mobile/messages/threads — start a new conversation.
     *
     * One of three recipients, never a guess (see the class docblock):
     *   `appointment_id` — unchanged; the provider on the patient's own appointment.
     *   `facility_id`    — the facility's front desk.
     *   `clinician_id`   — a named clinician the patient is already in care with.
     */
    public function start(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        // `appointment_id` stays a uuid and still works alone, so a client that
        // knows nothing about the other two paths behaves exactly as before.
        // `required_without_all` is what keeps "send nothing" a 422 rather than
        // a thread addressed to whatever the server felt like.
        $validated = $request->validate([
            'appointment_id' => ['nullable', 'uuid', 'required_without_all:facility_id,clinician_id'],
            'facility_id'    => ['nullable', 'uuid'],
            'clinician_id'   => ['nullable', 'uuid'],
            'body'           => ['required', 'string', 'max:4000'],
        ], [
            'appointment_id.required_without_all' => __('messaging_recipients.recipient_required'),
        ]);

        $named = array_filter([
            $validated['appointment_id'] ?? null,
            $validated['facility_id'] ?? null,
            $validated['clinician_id'] ?? null,
        ]);

        if (count($named) > 1) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_AMBIGUOUS',
                'message'    => __('messaging_recipients.recipient_ambiguous'),
            ], 422);
        }

        if (! empty($validated['facility_id'])) {
            return $this->startFacilityThread($request, $patientId, $validated['facility_id'], $validated['body']);
        }

        if (! empty($validated['clinician_id'])) {
            return $this->startClinicianThread($request, $patientId, $validated['clinician_id'], $validated['body']);
        }

        return $this->startAppointmentThread($request, $patientId, $validated['appointment_id'], $validated['body']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // The three send paths
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Path 1 — the provider on one of the patient's own appointments.
     *
     * Byte for byte the behaviour this endpoint has always had, moved into its
     * own method: same lookup scoped to `patient_id`, same 404, same 422 on an
     * appointment with no provider, same thread shape (`patient_provider` +
     * `context_type='appointment'`), same 201 payload. Only the hardcoded
     * English strings became translatable.
     */
    private function startAppointmentThread(Request $request, string $patientId, string $appointmentId, string $body): JsonResponse
    {
        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patientId)
            ->with('provider:id,name')
            ->first();

        if (! $appointment) {
            return response()->json([
                'error_code' => 'NOT_FOUND',
                'message'    => __('messaging_recipients.appointment_not_found'),
            ], 404);
        }

        if (! $appointment->provider_id) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_NOT_ALLOWED',
                'message'    => __('messaging_recipients.appointment_no_provider'),
            ], 422);
        }

        return $this->createThread(
            request: $request,
            patientId: $patientId,
            details: [
                'thread_type'    => 'patient_provider',
                'title'          => $appointment->provider?->name ?? __('messaging_recipients.facility_desk'),
                'context_type'   => 'appointment',
                'context_id'     => (string) $appointment->id,
                'doctor_id'      => $appointment->provider_id,
                'recipient_id'   => $appointment->provider_id,
                'recipient_role' => 'provider',
            ],
            body: $body,
        );
    }

    /**
     * Path 2 — the facility front desk.
     *
     * `facility_id` is accepted as EITHER an internal `facilities` id or the
     * `care_facilities` (register) id the app browses, because the two live
     * side by side all over this API and making the phone know which one this
     * endpoint wanted would be a trap. resolveFacility() collapses both.
     *
     * The desk must exist before the thread does: a facility with no staff
     * accounts is refused, because a thread nobody can open is worse than a
     * refusal that says "call them instead".
     */
    private function startFacilityThread(Request $request, string $patientId, string $facilityId, string $body): JsonResponse
    {
        $facility = $this->resolveFacility($facilityId);

        if (! $facility) {
            return response()->json([
                'error_code' => 'NOT_FOUND',
                'message'    => __('messaging_recipients.facility_not_found'),
            ], 404);
        }

        $desk = $this->deskUserIds($facility->id);

        if ($desk === []) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_UNREACHABLE',
                'message'    => __('messaging_recipients.facility_unreachable', ['facility' => $facility->name]),
            ], 422);
        }

        return $this->createThread(
            request: $request,
            patientId: $patientId,
            details: [
                // NOT 'patient_provider': that type means one named clinician,
                // and MessagePermissionService reads it as a claim to a
                // doctor_id + care context this thread deliberately has not got.
                'thread_type'    => 'patient_facility',
                'title'          => $facility->name,
                'context_type'   => 'facility',
                'context_id'     => (string) $facility->id,
                'recipient_id'   => $desk[0],
                'recipient_role' => 'facility_desk',
            ],
            body: $body,
            // The rest of the desk, so the conversation is the facility's and
            // not one staff member's private mail.
            extraParticipants: array_slice($desk, 1),
        );
    }

    /**
     * Path 3 — one named clinician, and only with a care relationship.
     *
     * Two separate questions, answered in order and never conflated:
     *
     *   MAY the patient write to them?  clinicianBasis() — the five bases
     *     MobileDoctorChartController enforces on the chart. None → 403.
     *
     *   CAN the clinician read it?      clinicianDeliveryContext() — today
     *     only an appointment context resolves on the clinician's side, so a
     *     real-but-appointment-less relationship is refused with a message
     *     that names the facility desk instead of silently filing the message
     *     where nobody looks.
     */
    private function startClinicianThread(Request $request, string $patientId, string $clinicianId, string $body): JsonResponse
    {
        $clinician = User::whereKey($clinicianId)->whereNull('patient_id')->first();

        if (! $clinician) {
            return response()->json([
                'error_code' => 'NOT_FOUND',
                'message'    => __('messaging_recipients.clinician_not_found'),
            ], 404);
        }

        $basis = $this->clinicianBasis($patientId, $clinician);

        if ($basis === null) {
            // The same error code the chart refusal uses, for the same reason:
            // holding an id is not authorisation.
            return response()->json([
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'message'         => __('messaging_recipients.no_relationship'),
                'required_action' => 'book_appointment',
            ], 403);
        }

        $appointment = $this->clinicianDeliveryContext($patientId, $clinician->id);

        if (! $appointment) {
            $facility = $this->clinicianFacility($clinician);

            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_UNREACHABLE',
                'message'    => __('messaging_recipients.clinician_unreachable', [
                    'clinician' => $clinician->name,
                    'facility'  => $facility?->name ?? __('messaging_recipients.facility_desk'),
                ]),
                'basis'       => $basis,
                'facility_id' => $facility?->id,
            ], 422);
        }

        return $this->createThread(
            request: $request,
            patientId: $patientId,
            details: [
                'thread_type'    => 'patient_provider',
                'title'          => $clinician->name,
                'context_type'   => 'appointment',
                'context_id'     => (string) $appointment->id,
                'doctor_id'      => $clinician->id,
                'recipient_id'   => $clinician->id,
                'recipient_role' => 'provider',
            ],
            body: $body,
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // Thread creation — one writer for all three paths
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Create the thread, add the participants, send the first message.
     *
     * The patient is added TWICE on purpose, under two identities:
     *
     *   • `patient_id`      — the actor identity this controller has always
     *                         used, and the one index()/show()/sendMessage()
     *                         key off. MessagingService adds it as creator.
     *   • `patient_user_id` — the linked `users` row, resolved by
     *                         AuthenticateMobilePatient from the token. The
     *                         staff web inbox (StaffMessagingController) walks
     *                         participants → users.patient_id → patients to
     *                         decide which threads belong to its facility, so
     *                         WITHOUT this row a patient-opened thread cannot
     *                         resolve to a patient there at all and the front
     *                         desk never sees it. Same human, two ids, because
     *                         two surfaces were built on two identities.
     *
     * Duplicate participation is harmless everywhere else: every reader keys on
     * its own id, and canViewThread() is an exists() check.
     *
     * @param  array<string,mixed>  $details
     * @param  array<int,string>    $extraParticipants
     */
    private function createThread(
        Request $request,
        string $patientId,
        array $details,
        string $body,
        array $extraParticipants = [],
    ): JsonResponse {
        $patientUserId = $request->attributes->get('patient_user_id');

        try {
            [$thread, $message] = DB::transaction(function () use ($patientId, $details, $body, $extraParticipants, $patientUserId) {
                $thread = $this->messagingService->createThread($patientId, 'patient', $details);

                $seen = array_filter([$patientId, $details['recipient_id'] ?? null]);

                foreach ($extraParticipants as $userId) {
                    if (in_array($userId, $seen, true)) {
                        continue;
                    }
                    $seen[] = $userId;
                    MessageThreadParticipant::create([
                        'thread_id'      => $thread->id,
                        'user_id'        => $userId,
                        'role_in_thread' => $details['recipient_role'] ?? 'participant',
                        'status'         => 'active',
                    ]);
                }

                if ($patientUserId && ! in_array($patientUserId, $seen, true)) {
                    MessageThreadParticipant::create([
                        'thread_id'      => $thread->id,
                        'user_id'        => $patientUserId,
                        'role_in_thread' => 'patient',
                        'status'         => 'active',
                    ]);
                }

                $message = $this->messagingService->sendMessage($thread->uuid, $patientId, $body);

                return [$thread, $message];
            });
        } catch (\Exception $e) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_NOT_ALLOWED',
                'message'    => $e->getMessage(),
            ], 422);
        }

        return response()->json(['data' => [
            'id'       => $thread->id,
            'title'    => $thread->title,
            'status'   => $thread->status,
            'messages' => [[
                'id'         => $message->id,
                'is_mine'    => true,
                'body'       => $body,
                'status'     => $message->status,
                'created_at' => $message->created_at?->toISOString(),
            ]],
        ]], 201);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Recipients — facilities
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Front desks this patient may write to, care facilities first.
     *
     * Ordering is the whole point of the shape: the places the patient is
     * actually in care with sit at the top with the reason they are there, and
     * the rest of the register is reachable only through an explicit search.
     * That is a facility list, not a clinician directory — a front desk is a
     * published phone number with an inbox attached.
     *
     * EVERY entry has a live desk. A facility register row with no staff
     * accounts behind it is not listed, at all, under any query: offering it
     * would be offering to swallow the message.
     *
     * @return array<int,array<string,mixed>>
     */
    private function facilityRecipients(string $patientId, string $query): array
    {
        $care = $this->careFacilityBases($patientId);

        // A search reaches past the patient's own care history — "does this
        // pharmacy stock it" is a question for a facility they have never
        // visited. Two characters minimum, so `q=` cannot mean "everything".
        //
        // While a search is running it is the WHOLE list, care facilities
        // included: they are register rows too, so one that matches comes back
        // in the hits (and still sorts to the top, because `is_care_facility`
        // drives the order). Keeping them unconditionally would mean a query
        // that matches nothing still answered with something, and "no results"
        // would be a state the patient could never reach.
        if (mb_strlen($query) >= 2) {
            $ids = $this->searchFacilityIds($query);
        } else {
            $ids = array_keys($care);

            // No care history and no search — still give the patient somewhere
            // to start, rather than an empty picker behind a button that
            // "works". Bounded: MAX_RECIPIENTS is what the list can show.
            if ($ids === []) {
                $ids = array_slice($this->staffedFacilityIds(), 0, self::MAX_RECIPIENTS);
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        $staffed = $this->staffedFacilityIds($ids);

        if ($staffed === []) {
            return [];
        }

        $facilities = Facility::whereIn('id', $staffed)->get()->keyBy('id');

        // The register row carries the city and the id the app already holds
        // from the facility page, so the client can match a recipient to the
        // facility it navigated from without a second call.
        $register = CareFacility::whereIn('facility_id', $staffed)
            ->get(['id', 'facility_id', 'city'])
            ->keyBy('facility_id');

        $rows = collect($staffed)
            ->map(function (string $id) use ($facilities, $register, $care) {
                $facility = $facilities->get($id);

                if (! $facility) {
                    return null;
                }

                $basis = $care[$id] ?? null;

                return [
                    'id'                => $facility->id,
                    'care_facility_id'  => $register->get($id)?->id,
                    'name'              => $facility->name,
                    'city'              => $register->get($id)?->city,
                    // Why this facility is on the list — null for a search hit,
                    // which is honest: the patient put it there, not the record.
                    'basis'             => $basis,
                    // `facility_basis`, not `basis`: the clinician wording
                    // ("you have an appointment with this clinician") is a lie
                    // on a row that names a building.
                    'basis_label'       => $basis ? __('messaging_recipients.facility_basis.' . $basis) : null,
                    'is_care_facility'  => $basis !== null,
                ];
            })
            ->filter()
            // Care facilities first, then alphabetical inside each group.
            ->sortBy(fn (array $row) => ($row['is_care_facility'] ? '0' : '1') . mb_strtolower((string) $row['name']))
            ->take(self::MAX_RECIPIENTS)
            ->values();

        return $rows->all();
    }

    /**
     * Facility ids where this patient has a care history, mapped to the basis.
     *
     * The same five ideas as clinicianBasis(), asked of a PLACE instead of a
     * person: where they are registered, where they have appointments, where
     * they were seen, where they were prescribed, and where they have granted
     * consent. Bounded by the lookback window everywhere a date exists —
     * a facility visited once, years ago, is history, not a care relationship.
     *
     * @return array<string,string>  facility_id => basis
     */
    private function careFacilityBases(string $patientId): array
    {
        $since = Carbon::now()->subDays(self::FACILITY_EPISODE_LOOKBACK_DAYS);
        $bases = [];

        // Weakest first, so a stronger basis overwrites it below.
        foreach (
            ConsentGrant::where('patient_id', $patientId)
                ->where('status', 'active')
                ->where('expires_at', '>=', Carbon::now())
                ->pluck('facility_id') as $id
        ) {
            if ($id) {
                $bases[(string) $id] = 'consent_grant';
            }
        }

        foreach (
            Encounter::where('patient_id', $patientId)
                ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
                ->pluck('facility_id') as $id
        ) {
            if ($id) {
                $bases[(string) $id] = 'facility_episode';
            }
        }

        foreach (
            Prescription::where('patient_id', $patientId)
                ->where('prescribed_at', '>=', $since)
                ->pluck('facility_id') as $id
        ) {
            if ($id) {
                $bases[(string) $id] = 'facility_episode';
            }
        }

        foreach (
            Appointment::where('patient_id', $patientId)
                ->where('scheduled_at', '>=', $since)
                ->pluck('facility_id') as $id
        ) {
            if ($id) {
                $bases[(string) $id] = 'appointment';
            }
        }

        // `patients.facility_id` — where this patient is registered. Strongest,
        // and the one StaffMessagingController keys its facility inbox on, so
        // it goes last and wins.
        $home = DB::table('patients')->where('id', $patientId)->value('facility_id');

        if ($home) {
            $bases[(string) $home] = 'facility_episode';
        }

        return $bases;
    }

    /**
     * Register search → internal facility ids.
     *
     * A case-folded substring match on the register's PUBLIC columns — facility
     * name and city — and nothing else. The platform's ban on `LIKE` search is
     * a ban on walking the PATIENT index; this query contains no patient data,
     * takes no patient identifier, and reads a directory the finder already
     * lets anyone browse. `%` and `_` in the term are escaped so a caller
     * cannot turn the pattern into "everything".
     *
     * @return array<int,string>
     */
    private function searchFacilityIds(string $query): array
    {
        $term = '%' . str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($query)) . '%';

        return CareFacility::whereNotNull('facility_id')
            ->where('listing_status', 'active')
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(facility_name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(city) LIKE ?', [$term]);
            })
            ->limit(200)
            ->pluck('facility_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Of these facilities (or of all of them), the ones with a live desk.
     *
     * One grouped query rather than a per-facility exists() — the register has
     * 482 facilities and 18 of them have accounts, so the filter has to be the
     * cheap half of this endpoint.
     *
     * @param  array<int,string>|null  $within
     * @return array<int,string>
     */
    private function staffedFacilityIds(?array $within = null): array
    {
        $q = $this->deskQuery();

        if ($within !== null) {
            if ($within === []) {
                return [];
            }
            $q->whereIn('primary_facility_id', $within);
        }

        return $q->groupBy('primary_facility_id')
            ->pluck('primary_facility_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Accept either identifier the app might be holding: the internal
     * `facilities` id (what an appointment carries) or the `care_facilities`
     * register id (what the facility page and the finder browse). Resolving
     * both here is what lets a "Message this facility" button pass whichever
     * id it already has.
     */
    private function resolveFacility(string $id): ?Facility
    {
        $facility = Facility::whereKey($id)->first();

        if ($facility) {
            return $facility;
        }

        $linked = CareFacility::whereKey($id)->value('facility_id');

        return $linked ? Facility::whereKey($linked)->first() : null;
    }

    /**
     * The one definition of a desk account, used by BOTH halves of the
     * promise: staffedFacilityIds() decides which facilities may be offered,
     * deskUserIds() decides who is added to the thread. If those two ever
     * disagreed, the endpoint would offer a facility it then refuses, or
     * create a thread for a desk it never checked existed.
     */
    private function deskQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->whereNotNull('primary_facility_id')
            // A patient account is never somebody else's desk.
            ->whereNull('patient_id')
            ->where('status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('name', array_merge(self::DESK_ROLES, self::DESK_CLINICAL_ROLES)));
    }

    /**
     * The desk accounts at one facility, reception first.
     *
     * The ordering is not cosmetic: element 0 becomes the thread's
     * `recipient_id`, so a facility with a receptionist gets its receptionist
     * named as the addressee rather than whichever account happens to be
     * oldest.
     *
     * @return array<int,string>
     */
    private function deskUserIds(string $facilityId): array
    {
        return $this->deskQuery()
            ->where('primary_facility_id', $facilityId)
            ->with('role:id,name')
            ->orderBy('created_at')
            ->limit(self::MAX_DESK_PARTICIPANTS)
            ->get(['id', 'role_id', 'created_at'])
            ->sortBy(fn (User $u) => in_array($u->role?->name, self::DESK_ROLES, true) ? 0 : 1)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Recipients — clinicians
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The clinicians this patient is already in care with — and nobody else.
     *
     * Built by collecting the candidate ids OUT OF THE PATIENT'S OWN RECORD
     * (their appointments, care team, encounters, visits, prescriptions and
     * orders) and then running each one through clinicianBasis(), the same
     * gate start() enforces. So the list can never contain a name the send
     * path would refuse, and it can never contain a name that is not already
     * in this patient's record: there is no facility roster read here, no
     * search parameter, and no way to widen it from the request.
     *
     * @return array<int,array<string,mixed>>
     */
    private function clinicianRecipients(string $patientId): array
    {
        $candidates = $this->clinicianCandidateIds($patientId);

        if ($candidates === []) {
            return [];
        }

        $clinicians = User::whereIn('id', $candidates)
            ->whereNull('patient_id')
            ->get();

        $facilities = Facility::whereIn('id', $clinicians->pluck('primary_facility_id')->filter()->unique()->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        return $clinicians
            ->map(function (User $clinician) use ($patientId, $facilities) {
                $basis = $this->clinicianBasis($patientId, $clinician);

                if ($basis === null) {
                    return null;
                }

                $appointment = $this->clinicianDeliveryContext($patientId, $clinician->id);
                $facility    = $clinician->primary_facility_id
                    ? $facilities->get($clinician->primary_facility_id)
                    : null;

                return [
                    'id'            => $clinician->id,
                    'name'          => $clinician->name,
                    'facility_id'   => $clinician->primary_facility_id,
                    'facility_name' => $facility?->name,
                    'basis'         => $basis,
                    'basis_label'   => __('messaging_recipients.basis.' . $basis),
                    // Authorised is not the same as reachable — see
                    // clinicianDeliveryContext(). The app must not offer a send
                    // button for a recipient whose inbox cannot list the result.
                    'deliverable'    => $appointment !== null,
                    'appointment_id' => $appointment?->id,
                ];
            })
            ->filter()
            // Reachable names first, then alphabetical — the same composite-key
            // sort the facility list uses, for the same reason: a picker whose
            // top entries are the ones that will actually work.
            ->sortBy(fn (array $row) => ($row['deliverable'] ? '0' : '1') . mb_strtolower((string) $row['name']))
            ->take(self::MAX_RECIPIENTS)
            ->values()
            ->all();
    }

    /**
     * Clinician ids that appear anywhere in THIS patient's own record.
     *
     * The union of the columns clinicianBasis() reads, so nothing offered here
     * can fail the gate — every candidate is still run through it.
     *
     * The reverse does NOT hold, on purpose. Bases 4 and 5 (facility_episode,
     * consent_grant) are properties of a FACILITY, so the gate would accept any
     * clinician working at a hospital the patient has an open episode at or has
     * granted access to. Listing all of them would be publishing that hospital's
     * roster as a DM directory, which is exactly what this feature must not be.
     * So the picker offers only people who are already IN the patient's record
     * — a deliberately narrower set than the rule the server enforces, which is
     * the safe direction for the two to differ in.
     *
     * @return array<int,string>
     */
    private function clinicianCandidateIds(string $patientId): array
    {
        $ids = collect()
            ->merge(Appointment::where('patient_id', $patientId)->pluck('provider_id'))
            ->merge(
                CareTeamMember::where('patient_id', $patientId)
                    ->where('is_active', true)
                    ->whereNull('left_at')
                    ->pluck('provider_id')
            )
            ->merge(Encounter::where('patient_id', $patientId)->pluck('attending_provider_id'))
            ->merge(DB::table('visits')->where('patient_id', $patientId)->pluck('provider_id'))
            ->merge(Prescription::where('patient_id', $patientId)->pluck('prescribed_by'))
            ->merge(LabOrder::where('patient_id', $patientId)->pluck('ordered_by'))
            ->merge(ImagingOrder::where('patient_id', $patientId)->pluck('ordered_by'));

        return $ids
            ->filter(fn ($id) => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The basis on which this patient may write to this clinician, or null.
     *
     * MobileDoctorChartController::relationshipBasis() read from the other
     * side. Same five bases, same order, cheapest and most specific first, and
     * the returned string is the same vocabulary — one rule about what a care
     * relationship IS, asked by the chart when a clinician wants to read and
     * asked here when a patient wants to write.
     *
     * Bases 4 and 5 need a facility. It is the CLINICIAN'S own facility
     * (`users.primary_facility_id`, or their staff profile) — resolved from
     * the clinician record, never from anything the caller sent, exactly as
     * the chart takes it from the provider's token and never from a parameter.
     */
    private function clinicianBasis(string $patientId, User $clinician): ?string
    {
        $clinicianId = (string) $clinician->id;

        // 1. On this clinician's own schedule.
        if (Appointment::where('patient_id', $patientId)->where('provider_id', $clinicianId)->exists()) {
            return 'appointment';
        }

        // 2. A current member of this patient's care team.
        if (
            CareTeamMember::where('patient_id', $patientId)
                ->where('provider_id', $clinicianId)
                ->where('is_active', true)
                ->whereNull('left_at')
                ->exists()
        ) {
            return 'care_team';
        }

        // 3. This clinician personally did something clinical for this patient.
        $ownAct = Encounter::where('patient_id', $patientId)->where('attending_provider_id', $clinicianId)->exists()
            || DB::table('visits')->where('patient_id', $patientId)->where('provider_id', $clinicianId)->exists()
            || Prescription::where('patient_id', $patientId)->where('prescribed_by', $clinicianId)->exists()
            || LabOrder::where('patient_id', $patientId)->where('ordered_by', $clinicianId)->exists()
            || ImagingOrder::where('patient_id', $patientId)->where('ordered_by', $clinicianId)->exists();

        if ($ownAct) {
            return 'treating_clinician';
        }

        $facility = $this->clinicianFacility($clinician);

        if (! $facility) {
            return null;
        }

        // 4. An episode of care at the clinician's own facility, still live.
        if ($this->hasFacilityEpisode($patientId, $facility->id)) {
            return 'facility_episode';
        }

        // 5. The patient has granted that facility access outright.
        if ($this->hasActiveConsentGrant($patientId, $facility->id)) {
            return 'consent_grant';
        }

        return null;
    }

    /**
     * Is there a live episode of care for this patient at this facility?
     *
     * "Live" is: still open, scheduled ahead, or begun within the lookback
     * window. The chart controller's rule, unchanged — a ten-year-old closed
     * visit is history, and history is not a reason to open a conversation
     * today any more than it is a reason to open a record.
     */
    private function hasFacilityEpisode(string $patientId, string $facilityId): bool
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

    /** An active, unexpired consent grant for this patient scoped to this facility. */
    private function hasActiveConsentGrant(string $patientId, string $facilityId): bool
    {
        return ConsentGrant::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->exists();
    }

    /** The clinician's own facility: their account's, else their staff profile's. */
    private function clinicianFacility(User $clinician): ?Facility
    {
        $facilityId = $clinician->primary_facility_id
            ?: DB::table('staff_profiles')->where('user_id', $clinician->id)->value('facility_id');

        return $facilityId ? Facility::whereKey($facilityId)->first() : null;
    }

    /**
     * The appointment a clinician thread must hang off to be READABLE, or null.
     *
     * Not an authorisation check — clinicianBasis() is that, and it runs first.
     * This is the delivery question, and it has a concrete answer:
     * MobileDoctorMessagingController::visibleThreads() resolves a patient
     * thread's patient AND its facility from `context_type='appointment'` →
     * `appointments`, and drops any thread typed `patient_provider` that does
     * not resolve one. The staff web inbox cannot pick the thread up either
     * (StaffMessagingController matches on `patients.facility_id`, which is
     * null for every patient row on this deployment).
     *
     * So a relationship that is real but carries no appointment — care_team,
     * treating_clinician, facility_episode, consent_grant — currently has no
     * inbox that would list the result. start() refuses it and names the
     * facility desk instead. The alternative, writing the thread anyway, is
     * the worst outcome available: the patient believes they have been heard.
     *
     * Lifting this is one change in MobileDoctorMessagingController (accept the
     * non-appointment bases as patient threads and take their facility from the
     * clinician's own token) — that file is not this one's to edit.
     */
    private function clinicianDeliveryContext(string $patientId, string $clinicianId): ?Appointment
    {
        return Appointment::where('patient_id', $patientId)
            ->where('provider_id', $clinicianId)
            ->orderByDesc('scheduled_at')
            ->first();
    }

    /**
     * `message_threads.id` is a bigint. Handing Postgres a non-numeric route
     * segment raises `invalid input syntax for type bigint` — a 500 where a
     * 404 belongs — so the shape is checked before the lookup.
     */
    private function findThread(string $id): MessageThread
    {
        if (! ctype_digit($id)) {
            abort(404);
        }

        return MessageThread::findOrFail($id);
    }

    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');
        if (! $patientId) {
            abort(401, 'Unauthenticated.');
        }

        return $patientId;
    }
}
