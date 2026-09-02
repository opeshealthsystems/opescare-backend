<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
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
 * by MessagePermissionService::canViewThread(userId, threadUuid), which checks
 * active participation and denies legal-hold threads to non-compliance users.
 *
 * The patient logs in as a User (UUID primary key). Threads belong to the user
 * via MessageThreadParticipant.user_id. Patients reply to threads that providers
 * started — starting a brand-new patient_provider thread requires the care-context
 * permission flow (doctor_id + context_id) and is intentionally out of scope here.
 */
class PatientMessagingController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
        private readonly MessagePermissionService $permissions,
    ) {}

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
     * Inbox — list threads where the user is an active participant,
     * newest-activity first.
     */
    public function index(): \Illuminate\View\View
    {
        $userId = $this->actorUserId();

        $threads = MessageThread::query()
            ->whereHas('participants', fn ($q) =>
                $q->where('user_id', $userId)->where('status', 'active')
            )
            ->with([
                'messages' => fn ($q) => $q->orderByDesc('created_at')->limit(1),
                'participants',
            ])
            ->orderByDesc('updated_at')
            ->get();

        $rows = $threads->map(function (MessageThread $thread) use ($userId) {
            $last = $thread->messages->first();
            $snippet = null;
            if ($last) {
                $decrypted = $this->messaging->decryptBody($last->body);
                $snippet = Str::limit($decrypted, 120);
            }

            // Unread heuristic: the participant's last_read_at predates the
            // most recent inbound message (sent by someone else).
            $participant = $thread->participants
                ->firstWhere('user_id', $userId);
            $unread = false;
            if ($last && $last->sender_id !== $userId) {
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

        return view('portals.patient.messages', ['threads' => $rows]);
    }

    /**
     * Thread view — message history + reply form.
     */
    public function show(string $thread): \Illuminate\View\View
    {
        $userId = $this->actorUserId();
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        if (!$this->permissions->canViewThread($userId, $threadModel->uuid)) {
            abort(403);
        }

        $threadModel->load(['messages' => fn ($q) => $q->orderBy('created_at')]);

        // Resolve the linked clinical object (if any) for a context card.
        $context = null;
        $patient = Auth::user()?->patient;
        if ($patient && $threadModel->context_type && $threadModel->context_id) {
            $model = $this->resolveContext($threadModel->context_type, $threadModel->context_id, $patient);
            if ($model) {
                $context = [
                    'type'  => $threadModel->context_type,
                    'label' => $this->contextLabel($threadModel->context_type, $model),
                ];
            }
        }

        $messages = $threadModel->messages->map(function ($msg) {
            return [
                'uuid'      => $msg->uuid,
                'body'      => $this->messaging->decryptBody($msg->body),
                'sender_id' => $msg->sender_id,
                'sent_at'   => $msg->created_at,
            ];
        });

        return view('portals.patient.messages-thread', [
            'thread'   => $threadModel,
            'messages' => $messages,
            'userId'   => $userId,
            'context'  => $context,
        ]);
    }

    /**
     * Reply — append a message to a thread the user participates in.
     */
    public function send(Request $request, string $thread): RedirectResponse
    {
        $userId = $this->actorUserId();
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        if (!$this->permissions->canViewThread($userId, $threadModel->uuid)) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $this->messaging->sendMessage($threadModel->uuid, $userId, $validated['body']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('portals.patient.messages.show', $threadModel->uuid)
                ->with('error', __('messaging.reply_failed'));
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
        $patient = Auth::user()?->patient;
        abort_if($patient === null, 403);

        return view('portals.patient.messages-compose', [
            'catalog'   => $this->contextCatalog($patient),
            'hasTeam'   => count($this->careTeamRecipientIds($patient)) > 0,
        ]);
    }

    /** POST — create a patient→care-team thread (optionally context-linked). */
    public function store(Request $request): RedirectResponse
    {
        $userId  = $this->actorUserId();
        $patient = Auth::user()?->patient;
        abort_if($patient === null, 403);

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

        try {
            // NOTE: message_threads.patient_id/facility_id are legacy bigint columns
            // and cannot hold the UUID PKs used by patients/facilities. The thread
            // is linked to the patient via participants (user_id) and to the
            // clinical record via the varchar context_type/context_id pair.
            $thread = $this->messaging->createThread($userId, 'patient', [
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

        return redirect()->route('portals.patient.messages.show', $thread->uuid)
            ->with('success', __('messaging.compose_sent'));
    }
}
