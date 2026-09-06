<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GuardianAccessMiddleware;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * PatientMessagingController — patient-facing web UI for secure messaging.
 *
 * Built ON TOP of the existing Messaging module (models + MessagingService +
 * MessagePermissionService). Message bodies are KMS-encrypted at rest and are
 * always rendered through MessagingService::decryptBody().
 *
 * Authorization mirrors Api\V1\CommunicationController: thread access is gated
 * by MessagePermissionService::canViewThread(id, threadUuid), which checks
 * active participation and denies legal-hold threads to non-compliance users.
 *
 * WHOSE INBOX. Every page here shows the inbox of the patient whose record is
 * open: the signed-in user's own patient, or — while a guardian has switched
 * to a dependent (GuardianAccessMiddleware sets `viewing_patient` and
 * `guardian_link`) — the dependent's. Threads are addressed to a patient under
 * two coexisting conventions (see inboxIds()), so "the dependent's threads"
 * means "threads any of the dependent's ids participates in".
 *
 * GUARDIANS. A read-only link may READ but never reply or start a thread — the
 * refusal is a flash and a redirect back, never a 403 page or a 500. A full
 * link may act; the message is recorded under the guardian's OWN user id (the
 * person who actually wrote it) and an AuditEvent attributes the action to the
 * guardian as actor on the dependent's record, the same way
 * PatientPortalController::generateTemporaryQr does.
 */
class PatientMessagingController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
        private readonly MessagePermissionService $permissions,
        private readonly PortalContextService $ctx,
    ) {}

    // ── Whose inbox is open ─────────────────────────────────────────────────

    /**
     * Resolve the authenticated user's id as a validated UUID string.
     * Aborts 401 if there is no authenticated user context.
     */
    private function actorUserId(): string
    {
        $userId = Auth::id();
        if (!is_string($userId) || !Str::isUuid($userId)) {
            abort(401, 'Authenticated user context is required.');
        }
        return $userId;
    }

    /**
     * The patient whose inbox is open: the dependent while a guardian has
     * switched, else the signed-in user's own patient (null for an account
     * that holds no Health ID yet).
     */
    private function inboxPatient(): ?Patient
    {
        if (request()->attributes->has('viewing_patient')) {
            return request()->attributes->get('viewing_patient');
        }
        return Auth::user()?->patient;
    }

    /** True while a guardian has a dependent's record open. */
    private function isSwitched(): bool
    {
        return request()->attributes->has('viewing_patient');
    }

    /** True while a guardian views a dependent through a link that does not grant writes. */
    private function isReadOnlyGuardian(): bool
    {
        return GuardianAccessMiddleware::isReadOnly(request()->attributes->get('guardian_link'));
    }

    private function viewingName(): string
    {
        $p = request()->attributes->get('viewing_patient');
        return $p ? trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) : '';
    }

    /**
     * The polite refusal for a read-only guardian: back to where they were
     * with a flash, never a 403 page and never a 500.
     */
    private function refuseReadOnly(): RedirectResponse
    {
        return back()->with('error', __('family.read_only_refused', ['name' => $this->viewingName()]));
    }

    /**
     * The ids a patient's threads are addressed to.
     *
     * Two conventions coexist in message_thread_participants: the web keys on
     * users.id, the mobile app (and ConsentService's no-account fallback) on
     * patients.id — and a child registered by a guardian has no user account
     * at all, so the patient id is often the only key. Off a switch the
     * signed-in user's own id is always included: an account without a Health
     * ID still has an inbox. On a switch the guardian's own id is deliberately
     * NOT included — the page is the dependent's inbox, not a merge of both.
     *
     * @return list<string>
     */
    private function inboxIds(?Patient $patient): array
    {
        $ids = $this->isSwitched() ? [] : [$this->actorUserId()];

        if ($patient) {
            $ids[] = (string) $patient->id;
            foreach (User::where('patient_id', $patient->id)->pluck('id') as $uid) {
                $ids[] = (string) $uid;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Everyone who writes from the patient's side of a conversation: the
     * inbox ids plus every guardian currently linked to the patient. A guardian
     * with full access sends under their OWN id (see send()), so this set is
     * what decides which bubbles sit on "our" side and which count as inbound.
     *
     * @param  list<string>  $inboxIds
     * @return array{ids: list<string>, guardians: array<string,string>}  ids, and guardian id => display name
     */
    private function ownSide(?Patient $patient, array $inboxIds): array
    {
        $guardians = [];
        if ($patient) {
            $guardianIds = FamilyLink::active()
                ->where('dependent_patient_id', $patient->id)
                ->pluck('guardian_user_id')
                ->map(fn ($id) => (string) $id)
                ->all();
            if ($guardianIds) {
                $guardians = User::whereIn('id', $guardianIds)->pluck('name', 'id')
                    ->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])
                    ->all();
            }
        }

        return [
            'ids'       => array_values(array_unique(array_merge($inboxIds, array_keys($guardians)))),
            'guardians' => $guardians,
        ];
    }

    /**
     * Whether any of the inbox ids may open the thread — the module's own gate
     * (active participation, legal hold), asked once per id.
     *
     * @param  list<string>  $inboxIds
     */
    private function canOpen(array $inboxIds, string $threadUuid): bool
    {
        foreach ($inboxIds as $id) {
            if ($this->permissions->canViewThread($id, $threadUuid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The id a NEW thread for this patient is addressed to: their account when
     * they have one, else the patient id — the same fallback ConsentService and
     * the mobile app use, so the thread lands in the inbox inboxIds() reads.
     */
    private function primaryInboxId(Patient $patient): string
    {
        return (string) (User::where('patient_id', $patient->id)->value('id') ?? $patient->id);
    }

    /**
     * Audit a guardian acting on a dependent's inbox. The actor is the guardian
     * (PortalContextService reads Auth::id()); the patient is the dependent;
     * the link that authorised it travels in after_state.
     */
    private function auditOnBehalf(string $actionType, string $resourceType, ?string $resourceId, Patient $patient): void
    {
        $link = request()->attributes->get('guardian_link');

        $this->ctx->auditPatientAccess(
            actionType:   $actionType,
            resourceType: $resourceType,
            resourceId:   $resourceId,
            patientId:    $patient->id,
            extra: [
                'after_state' => [
                    'on_behalf_of_patient_id' => (string) $patient->id,
                    'family_link_id'          => $link?->id,
                    'access_level'            => $link?->access_level,
                ],
            ],
        );
    }

    // ── Pages ───────────────────────────────────────────────────────────────

    /**
     * Inbox — list threads where the open patient is an active participant,
     * newest-activity first.
     */
    public function index(): \Illuminate\View\View
    {
        $patient  = $this->inboxPatient();
        $inboxIds = $this->inboxIds($patient);
        $ownSide  = $this->ownSide($patient, $inboxIds);

        $threads = MessageThread::query()
            ->whereHas('participants', fn ($q) =>
                $q->whereIn('user_id', $inboxIds)->where('status', 'active')
            )
            ->with([
                'messages' => fn ($q) => $q->orderByDesc('created_at')->limit(1),
                'participants',
            ])
            ->orderByDesc('updated_at')
            ->get();

        $rows = $threads->map(function (MessageThread $thread) use ($inboxIds, $ownSide) {
            $last = $thread->messages->first();
            $snippet = null;
            if ($last) {
                $decrypted = $this->messaging->decryptBody($last->body);
                $snippet = Str::limit($decrypted, 120);
            }

            // Unread heuristic: the patient-side participant's last_read_at
            // predates the most recent INBOUND message (sent from the other side).
            $participant = $thread->participants
                ->first(fn ($p) => in_array((string) $p->user_id, $inboxIds, true));
            $unread = false;
            if ($last && !in_array((string) $last->sender_id, $ownSide['ids'], true)) {
                $lastRead = $participant?->last_read_at;
                $unread = $lastRead === null || $last->created_at?->gt($lastRead);
            }

            return [
                'uuid'         => $thread->uuid,
                'title'        => $thread->title,
                'status'       => $thread->status,
                'snippet'      => $snippet,
                'unread'       => $unread,
                'last_at'      => $last?->created_at ?? $thread->updated_at,
                'context_type' => $thread->context_type,
            ];
        });

        return view('portals.patient.messages', [
            'threads'  => $rows,
            'readOnly' => $this->isReadOnlyGuardian(),
            'viewing'  => $this->isSwitched() ? $patient : null,
        ]);
    }

    /**
     * Thread view — message history + reply form.
     */
    public function show(string $thread): \Illuminate\View\View
    {
        $userId   = $this->actorUserId();
        $patient  = $this->inboxPatient();
        $inboxIds = $this->inboxIds($patient);
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        if (!$this->canOpen($inboxIds, $threadModel->uuid)) {
            abort(403);
        }

        $threadModel->load(['messages' => fn ($q) => $q->orderBy('created_at')]);

        // Resolve the linked clinical object (if any) for a context card.
        $context = null;
        if ($patient && $threadModel->context_type && $threadModel->context_id) {
            $model = $this->resolveContext($threadModel->context_type, $threadModel->context_id, $patient);
            if ($model) {
                $context = [
                    'type'  => $threadModel->context_type,
                    'label' => $this->contextLabel($threadModel->context_type, $model),
                ];
            }
        }

        $ownSide  = $this->ownSide($patient, $inboxIds);
        $switched = $this->isSwitched();
        $sideName = $switched ? trim((string) ($patient->first_name ?? '')) : '';

        $messages = $threadModel->messages->map(function ($msg) use ($userId, $inboxIds, $ownSide, $switched, $sideName) {
            $sender = (string) $msg->sender_id;

            // Who wrote it, truthfully: me; the patient (named while a guardian
            // reads); a guardian writing on the patient's behalf; or the care team.
            if ($sender === $userId) {
                $who = __('messaging.you');
            } elseif (in_array($sender, $inboxIds, true)) {
                $who = $switched && $sideName !== '' ? $sideName : __('messaging.you');
            } elseif (isset($ownSide['guardians'][$sender])) {
                $who = __('family.inbox_guardian_label', ['name' => $ownSide['guardians'][$sender]]);
            } else {
                $who = __('messaging.participant');
            }

            return [
                'uuid'      => $msg->uuid,
                'body'      => $this->messaging->decryptBody($msg->body),
                'sender_id' => $sender,
                'sent_at'   => $msg->created_at,
                'mine'      => in_array($sender, $ownSide['ids'], true),
                'who'       => $who,
            ];
        });

        // A guardian reading someone else's clinical conversation is a
        // disclosure worth recording; the patient reading their own is not new.
        if ($switched) {
            $this->auditOnBehalf('message_thread_view', 'MessageThread', $threadModel->uuid, $patient);
        }

        return view('portals.patient.messages-thread', [
            'thread'   => $threadModel,
            'messages' => $messages,
            'userId'   => $userId,
            'context'  => $context,
            'readOnly' => $this->isReadOnlyGuardian(),
            'viewing'  => $switched ? $patient : null,
        ]);
    }

    /**
     * Reply — append a message to a thread the open patient participates in.
     */
    public function send(Request $request, string $thread): RedirectResponse
    {
        $userId   = $this->actorUserId();
        $patient  = $this->inboxPatient();
        $inboxIds = $this->inboxIds($patient);
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        if (!$this->canOpen($inboxIds, $threadModel->uuid)) {
            abort(403);
        }

        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            // The sender is the person who actually wrote it — a guardian sends
            // under their own id; the audit event below ties it to the dependent.
            $this->messaging->sendMessage($threadModel->uuid, $userId, $validated['body']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('portals.patient.messages.show', $threadModel->uuid)
                ->with('error', __('messaging.reply_failed'));
        }

        if ($this->isSwitched()) {
            $this->auditOnBehalf('message_sent', 'MessageThread', $threadModel->uuid, $patient);
        }

        return redirect()
            ->route('portals.patient.messages.show', $threadModel->uuid)
            ->with('success', __('messaging.reply_sent'));
    }

    // ── Compose a new thread (patient → care team) with optional clinical context ──

    /**
     * Allowed context types a patient may reference, mapped to their model and
     * a closure that renders a human label. Each model is scoped to the patient.
     */
    private function contextModels(): array
    {
        return [
            'lab_result'       => \App\Models\LabResult::class,
            'prescription'     => \App\Models\Prescription::class,
            'appointment'      => \App\Models\Appointment::class,
            'visit'            => \App\Models\Visit::class,
            'insurance_policy' => \App\Models\PatientInsurancePolicy::class,
        ];
    }

    /** A short, safe label for a referenced clinical object. */
    private function contextLabel(string $type, $model): string
    {
        return match ($type) {
            'lab_result'       => __('messaging.ctx_lab') . ' · ' . ($model->resulted_at?->isoFormat('LL') ?? '—'),
            'prescription'     => __('messaging.ctx_rx') . ' · ' . ($model->prescribed_at?->isoFormat('LL') ?? '—'),
            'appointment'      => __('messaging.ctx_appt') . ' · ' . ($model->scheduled_at?->isoFormat('LL') ?? '—'),
            'visit'            => __('messaging.ctx_visit') . ' · ' . ($model->started_at?->isoFormat('LL') ?? '—'),
            'insurance_policy' => __('messaging.ctx_insurance'),
            default            => __('messaging.ctx_generic'),
        };
    }

    /**
     * Resolve a (type,id) context pair to a patient-owned model, or null if the
     * type is unknown or the record does not belong to this patient.
     */
    private function resolveContext(?string $type, ?string $id, Patient $patient)
    {
        if (!$type || !$id || !array_key_exists($type, $this->contextModels())) {
            return null;
        }

        return ($this->contextModels()[$type])::query()
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();
    }

    /** The patient's own referenceable records, grouped by context type. */
    private function contextCatalog(Patient $patient): array
    {
        return [
            'appointment'      => \App\Models\Appointment::where('patient_id', $patient->id)->orderByDesc('scheduled_at')->limit(20)->get()
                ->map(fn ($a) => ['id' => $a->id, 'label' => trim(($a->appointment_type ?? __('messaging.ctx_appt')) . ' — ' . ($a->scheduled_at?->isoFormat('LL') ?? '—'))]),
            'lab_result'       => \App\Models\LabResult::where('patient_id', $patient->id)->orderByDesc('resulted_at')->limit(20)->get()
                ->map(fn ($l) => ['id' => $l->id, 'label' => __('messaging.ctx_lab') . ' — ' . ($l->resulted_at?->isoFormat('LL') ?? '—')]),
            'prescription'     => \App\Models\Prescription::where('patient_id', $patient->id)->orderByDesc('prescribed_at')->limit(20)->get()
                ->map(fn ($p) => ['id' => $p->id, 'label' => __('messaging.ctx_rx') . ' — ' . ($p->prescribed_at?->isoFormat('LL') ?? '—')]),
            'visit'            => \App\Models\Visit::where('patient_id', $patient->id)->orderByDesc('started_at')->limit(20)->get()
                ->map(fn ($v) => ['id' => $v->id, 'label' => trim(($v->visit_type ?? __('messaging.ctx_visit')) . ' — ' . ($v->started_at?->isoFormat('LL') ?? '—'))]),
            'insurance_policy' => \App\Models\PatientInsurancePolicy::where('patient_id', $patient->id)->latest()->limit(20)->get()
                ->map(fn ($i) => ['id' => $i->id, 'label' => __('messaging.ctx_insurance') . ($i->plan?->name ? ' — ' . $i->plan->name : '')]),
        ];
    }

    /** Active staff users at the patient's facility — the care-team recipients. */
    private function careTeamRecipientIds(Patient $patient): array
    {
        if (!$patient->facility_id) {
            return [];
        }

        return \App\Models\User::where('primary_facility_id', $patient->facility_id)
            ->whereHas('role', fn ($q) => $q->where('name', '!=', 'patient'))
            ->limit(15)
            ->pluck('id')
            ->all();
    }

    /** GET — compose form: subject, optional clinical reference, first message. */
    public function composeForm(): \Illuminate\View\View
    {
        $patient = $this->inboxPatient();
        abort_if($patient === null, 403);

        return view('portals.patient.messages-compose', [
            'catalog'   => $this->contextCatalog($patient),
            'hasTeam'   => count($this->careTeamRecipientIds($patient)) > 0,
            'readOnly'  => $this->isReadOnlyGuardian(),
            'viewing'   => $this->isSwitched() ? $patient : null,
        ]);
    }

    /** POST — create a patient→care-team thread (optionally context-linked). */
    public function store(Request $request): RedirectResponse
    {
        $userId  = $this->actorUserId();
        $patient = $this->inboxPatient();
        abort_if($patient === null, 403);

        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:150'],
            'body'         => ['required', 'string', 'max:5000'],
            'context_type' => ['nullable', 'string', 'in:' . implode(',', array_keys($this->contextModels()))],
            'context_id'   => ['nullable', 'uuid', 'required_with:context_type'],
        ]);

        // Validate any referenced record belongs to this patient.
        $context = $this->resolveContext($validated['context_type'] ?? null, $validated['context_id'] ?? null, $patient);
        if (($validated['context_type'] ?? null) && !$context) {
            return back()->withInput()->with('error', __('messaging.ctx_invalid'));
        }

        $recipients = $this->careTeamRecipientIds($patient);
        if (count($recipients) === 0) {
            return back()->withInput()->with('error', __('messaging.no_care_team'));
        }

        // The thread is the PATIENT's: on a switch it is addressed to the
        // dependent's inbox id so the dependent's inbox lists it, while the
        // first message stays signed by the guardian who wrote it.
        $switched  = $this->isSwitched();
        $creatorId = $switched ? $this->primaryInboxId($patient) : $userId;

        try {
            // NOTE: message_threads.patient_id/facility_id are legacy bigint columns
            // and cannot hold the UUID PKs used by patients/facilities. The thread
            // is linked to the patient via participants (user_id) and to the
            // clinical record via the varchar context_type/context_id pair.
            $thread = $this->messaging->createThread($creatorId, 'patient', [
                'thread_type'    => 'patient_care_team',
                'title'          => $validated['title'],
                'context_type'   => $validated['context_type'] ?? null,
                'context_id'     => $validated['context_id'] ?? null,
                'recipient_id'   => $recipients[0],
                'recipient_role' => 'care_team',
            ]);

            // Add any remaining care-team members as participants.
            foreach (array_slice($recipients, 1) as $rid) {
                \App\Modules\Messaging\Models\MessageThreadParticipant::create([
                    'thread_id' => $thread->id,
                    'user_id'   => $rid,
                    'role_in_thread' => 'care_team',
                    'status'    => 'active',
                ]);
            }

            $this->messaging->sendMessage($thread->uuid, $userId, $validated['body']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('messaging.compose_failed'));
        }

        if ($switched) {
            $this->auditOnBehalf('message_thread_created', 'MessageThread', $thread->uuid, $patient);
        }

        return redirect()->route('portals.patient.messages.show', $thread->uuid)
            ->with('success', __('messaging.compose_sent'));
    }
}
