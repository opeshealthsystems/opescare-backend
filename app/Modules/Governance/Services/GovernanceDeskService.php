<?php

namespace App\Modules\Governance\Services;

use App\Models\AllergyRecord;
use App\Models\AuditEvent;
use App\Models\ClinicalNote;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Models\Diagnosis;
use App\Models\RecordCorrectionDecision;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * GovernanceDeskService — the decision layer behind the patient-rights desk.
 *
 * WHAT ALREADY EXISTED. `DataExportService`, `CorrectionRequestService`,
 * `AccessLogService`, the `data_export_requests` / `correction_requests` /
 * `record_correction_decisions` tables and `AdminGovernanceController` were
 * all shipped. What did not exist was a route to any of it: a patient could
 * file a request under Cameroon Law No. 2010/012 and no human being on the
 * platform could act on it. This class wires the existing services together
 * and adds the three things a governance decision needs and none of them had:
 *
 *   1. A REASON on every decision, refusals included.
 *   2. An AMENDMENT that preserves the original — never a silent overwrite.
 *   3. The PATIENT BEING TOLD, synchronously.
 *
 * SYNCHRONOUS NOTIFICATION, DELIBERATELY. Finder runs no queue worker in
 * production. Anything dispatched to a queue here would never execute and the
 * patient would never learn the outcome, so this calls `Notify::toUser`
 * inline — it writes the `notification_events` row the in-app bell reads
 * within the request. The FCM push inside Notify is best-effort and already
 * swallows its own failures; the in-app row is the durable part.
 *
 * ACTOR IS A UUID. `audit_events.actor_id` is a uuid column. Every write here
 * passes the authenticated user's id or null — never a role name, an email or
 * a "system" literal, which has broken this codebase before.
 */
class GovernanceDeskService
{
    /**
     * Correction request resource types this desk can amend safely.
     *
     * Deliberately short, and deliberately an ALLOW-LIST. These three are the
     * types `CorrectionRequestService::resolveModelClass()` can resolve and
     * whose tables carry a `status` column that the read paths already honour
     * as a void marker. Anything else — a lab result, an immunisation, a
     * prescription, a free-text resource type a client invented — is REFUSED
     * on screen with the reason shown, because the alternative is a desk that
     * reports "approved" and changes nothing, which is worse than a no.
     */
    public const AMENDABLE_TYPES = ['clinical_note', 'diagnosis', 'allergy'];

    /** The void marker used platform-wide. Underscored, not hyphenated. */
    public const VOID_STATUS = 'entered_in_error';

    public function __construct(
        private readonly DataExportService $exportService,
        private readonly CorrectionRequestService $correctionService,
    ) {
    }

    // ── Data export requests ─────────────────────────────────────────────

    /**
     * Approve an export request. The patient may then download it once,
     * within the 24-hour window `DataExportService` stamps.
     *
     * @return array{ok:bool,reason:?string}
     */
    public function approveExport(
        DataExportRequest $export,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
    ): array {
        if ($export->status !== 'pending') {
            return ['ok' => false, 'reason' => 'governance_desk.flash_not_pending'];
        }

        if ($actorId === null) {
            return ['ok' => false, 'reason' => 'governance_desk.flash_actor_unresolved'];
        }

        DB::transaction(function () use ($export, $actorId, $reason) {
            // The existing service does the real work: status, approver,
            // expiry and its own access-log row. Not reimplemented here.
            $this->exportService->approveExport((string) $export->id, $actorId);
            $export->refresh();
            $export->forceFill(['decision_reason' => $reason])->save();
        });

        $this->audit('governance.export_approved', $export, $actorId, $actorRole, $facilityId, $ip, $reason, [
            'before' => ['status' => 'pending'],
            'after'  => ['status' => 'approved', 'expires_at' => optional($export->expires_at)->toIso8601String()],
        ]);

        $this->tellPatient($export->requested_by_user_id, 'data_export_approved', [
            'request_id' => (string) $export->id,
            'title'      => __('governance_desk.notify_export_approved_title'),
            'body'       => __('governance_desk.notify_export_approved_body'),
        ], $actorId, 'DataExportRequest', (string) $export->id);

        return ['ok' => true, 'reason' => null];
    }

    /**
     * Refuse an export request. The reason is mandatory at every layer that
     * can reach this method and is stored on the row AND in the audit trail.
     *
     * @return array{ok:bool,reason:?string}
     */
    public function refuseExport(
        DataExportRequest $export,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
    ): array {
        if ($export->status !== 'pending') {
            return ['ok' => false, 'reason' => 'governance_desk.flash_not_pending'];
        }

        $export->forceFill([
            'status'          => 'rejected',
            'rejected_by'     => $actorId,
            'rejected_at'     => now(),
            'decision_reason' => $reason,
        ])->save();

        $this->audit('governance.export_refused', $export, $actorId, $actorRole, $facilityId, $ip, $reason, [
            'before' => ['status' => 'pending'],
            'after'  => ['status' => 'rejected'],
        ]);

        $this->tellPatient($export->requested_by_user_id, 'data_export_refused', [
            'request_id' => (string) $export->id,
            'title'      => __('governance_desk.notify_export_refused_title'),
            'body'       => __('governance_desk.notify_export_refused_body', ['reason' => $reason]),
        ], $actorId, 'DataExportRequest', (string) $export->id);

        return ['ok' => true, 'reason' => null];
    }

    // ── Record correction requests ───────────────────────────────────────

    /**
     * Why this correction request cannot be granted, or null if it can.
     *
     * Called by the screen BEFORE the button is drawn, and again here before
     * anything is written, so a refusal is visible rather than discovered.
     */
    public function correctionRefusalReason(CorrectionRequest $correction): ?string
    {
        if ($correction->status !== 'pending') {
            return 'governance_desk.refusal_not_pending';
        }

        if (! in_array($correction->resource_type, self::AMENDABLE_TYPES, true)) {
            return 'governance_desk.refusal_type_unsupported';
        }

        if ($this->targetRecord($correction) === null) {
            return 'governance_desk.refusal_record_missing';
        }

        return null;
    }

    /**
     * Approve a correction.
     *
     * A CORRECTION IS NEVER A SILENT OVERWRITE. Nothing this method does
     * rewrites a recorded clinical value. The disputed row keeps every field
     * exactly as the clinician entered it and keeps the clinician's id; only
     * its `status` moves to `entered_in_error`, which is the void marker the
     * read paths across this codebase already filter on. The original value
     * therefore stays retrievable, which is the whole point — a patient's
     * right to correction is not a right to erase what a clinician recorded,
     * it is a right to have the record say that it was wrong.
     *
     * For a clinical note the amendment is made explicit: a NEW note is
     * created with `amends_note_id` pointing at the original, carrying the
     * patient's stated reason and the reviewer's decision. The lineage column
     * already existed on the model and nothing had ever written to it.
     *
     * @return array{ok:bool,reason:?string}
     */
    public function approveCorrection(
        CorrectionRequest $correction,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
    ): array {
        $refusal = $this->correctionRefusalReason($correction);
        if ($refusal !== null) {
            return ['ok' => false, 'reason' => $refusal];
        }

        if ($actorId === null) {
            return ['ok' => false, 'reason' => 'governance_desk.flash_actor_unresolved'];
        }

        $record = $this->targetRecord($correction);
        $before = ['status' => $record->status ?? null];
        $amendmentId = null;

        DB::transaction(function () use ($correction, $actorId, $reason, $record, &$amendmentId) {
            // Existing service: status, reviewer, reviewed_at, its own access
            // log, and the void marker on the target row.
            $this->correctionService->approveRequest((string) $correction->id, $actorId);
            $correction->refresh();

            if ($record instanceof ClinicalNote) {
                $amendmentId = $this->amendClinicalNote($record, $correction, $actorId, $reason);
            }

            RecordCorrectionDecision::create([
                'correction_request_id' => $correction->id,
                'decided_by'            => $actorId,
                'decision'              => 'approved',
                'decision_reason'       => $reason,
                'approved_changes'      => [
                    'resource_type' => $correction->resource_type,
                    'resource_id'   => $correction->resource_id,
                    'action'        => 'marked_' . self::VOID_STATUS,
                    'amendment_id'  => $amendmentId,
                    'original_kept' => true,
                ],
                'decided_at'            => now(),
            ]);
        });

        $this->auditCorrection('governance.correction_approved', $correction, $actorId, $actorRole, $facilityId, $ip, $reason, [
            'before' => $before,
            'after'  => ['status' => self::VOID_STATUS, 'amendment_id' => $amendmentId],
        ]);

        $this->tellPatient($correction->requested_by_user_id, 'record_correction_approved', [
            'request_id' => (string) $correction->id,
            'title'      => __('governance_desk.notify_correction_approved_title'),
            'body'       => __('governance_desk.notify_correction_approved_body'),
        ], $actorId, 'CorrectionRequest', (string) $correction->id);

        return ['ok' => true, 'reason' => null];
    }

    /**
     * Refuse a correction. A patient may be wrong about a clinical value, and
     * a system that can only approve corrections is not a governance system —
     * it is an erase button with a form in front of it. The reason is
     * mandatory and is what the patient is told.
     *
     * @return array{ok:bool,reason:?string}
     */
    public function refuseCorrection(
        CorrectionRequest $correction,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
    ): array {
        if ($correction->status !== 'pending') {
            return ['ok' => false, 'reason' => 'governance_desk.refusal_not_pending'];
        }

        if ($actorId === null) {
            return ['ok' => false, 'reason' => 'governance_desk.flash_actor_unresolved'];
        }

        DB::transaction(function () use ($correction, $actorId, $reason) {
            $this->correctionService->rejectRequest((string) $correction->id, $actorId);
            $correction->refresh();

            RecordCorrectionDecision::create([
                'correction_request_id' => $correction->id,
                'decided_by'            => $actorId,
                'decision'              => 'rejected',
                'decision_reason'       => $reason,
                'rejected_changes'      => [
                    'resource_type' => $correction->resource_type,
                    'resource_id'   => $correction->resource_id,
                    'reason'        => $reason,
                ],
                'decided_at'            => now(),
            ]);
        });

        $this->auditCorrection('governance.correction_refused', $correction, $actorId, $actorRole, $facilityId, $ip, $reason, [
            'before' => ['status' => 'pending'],
            'after'  => ['status' => 'rejected'],
        ]);

        $this->tellPatient($correction->requested_by_user_id, 'record_correction_refused', [
            'request_id' => (string) $correction->id,
            'title'      => __('governance_desk.notify_correction_refused_title'),
            'body'       => __('governance_desk.notify_correction_refused_body', ['reason' => $reason]),
        ], $actorId, 'CorrectionRequest', (string) $correction->id);

        return ['ok' => true, 'reason' => null];
    }

    // ── Reading ──────────────────────────────────────────────────────────

    /** The record a correction request points at, or null if it is gone. */
    public function targetRecord(CorrectionRequest $correction): mixed
    {
        if (! Str::isUuid((string) $correction->resource_id)) {
            return null;
        }

        $class = match ($correction->resource_type) {
            'clinical_note' => ClinicalNote::class,
            'diagnosis'     => Diagnosis::class,
            'allergy'       => AllergyRecord::class,
            default         => null,
        };

        return $class ? $class::query()->find($correction->resource_id) : null;
    }

    /** Every decision this desk has taken on one request. */
    public function history(string $resourceType, string $resourceId)
    {
        return AuditEvent::query()
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    // ── Internals ────────────────────────────────────────────────────────

    /**
     * Create the amendment note that supersedes a disputed one.
     *
     * The original is NOT edited beyond its void marker. The new note records
     * what the patient disputed and what the reviewer decided, and points back
     * at the original through `amends_note_id`, so both halves of the story
     * remain readable in order.
     */
    private function amendClinicalNote(
        ClinicalNote $original,
        CorrectionRequest $correction,
        string $actorId,
        string $reason,
    ): string {
        $amendment = ClinicalNote::create([
            'visit_id'                   => $original->visit_id,
            'provider_id'                => $actorId,
            'history_of_present_illness' => $original->history_of_present_illness,
            'examination_findings'       => $original->examination_findings,
            'treatment_plan'             => trim(implode("\n\n", array_filter([
                $original->treatment_plan,
                __('governance_desk.amendment_stamp', [
                    'date'     => now()->toDateTimeString(),
                    'disputed' => $correction->reason,
                    'decision' => $reason,
                ]),
            ]))),
            'status'                     => 'amended',
            'amends_note_id'             => $original->id,
            'signed_at'                  => now(),
        ]);

        return (string) $amendment->id;
    }

    /** Audit an export decision. */
    private function audit(
        string $action,
        DataExportRequest $export,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
        array $payload,
    ): void {
        AuditEvent::create([
            'actor_id'      => $this->uuidOrNull($actorId),
            'actor_role'    => $actorRole,
            'facility_id'   => $this->uuidOrNull($facilityId),
            'patient_id'    => $this->uuidOrNull($export->patient_id),
            'action_type'   => $action,
            'resource_type' => 'data_export_request',
            'resource_id'   => (string) $export->id,
            'source_system' => 'governance_desk',
            'ip_address'    => $ip,
            'reason'        => $reason,
            'before_state'  => $payload['before'] ?? null,
            'after_state'   => $payload['after'] ?? null,
            'created_at'    => now(),
        ]);
    }

    /** Audit a correction decision. */
    private function auditCorrection(
        string $action,
        CorrectionRequest $correction,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        string $reason,
        array $payload,
    ): void {
        AuditEvent::create([
            'actor_id'      => $this->uuidOrNull($actorId),
            'actor_role'    => $actorRole,
            'facility_id'   => $this->uuidOrNull($facilityId),
            'patient_id'    => $this->uuidOrNull($correction->patient_id),
            'action_type'   => $action,
            'resource_type' => 'correction_request',
            'resource_id'   => (string) $correction->id,
            'source_system' => 'governance_desk',
            'ip_address'    => $ip,
            'reason'        => $reason,
            'before_state'  => $payload['before'] ?? null,
            'after_state'   => $payload['after'] ?? null,
            'created_at'    => now(),
        ]);
    }

    /**
     * `audit_events.actor_id`, `.facility_id` and `.patient_id` are uuid
     * columns. Anything that is not a uuid becomes null rather than a
     * Postgres 22P02 surfacing as a 500 on a compliance write.
     */
    private function uuidOrNull(?string $value): ?string
    {
        return ($value !== null && Str::isUuid($value)) ? $value : null;
    }

    /**
     * Tell the patient. Synchronous — see the class docblock: Finder runs no
     * queue worker, so a queued notification would never be delivered.
     *
     * Best-effort by design: a notification failure must not roll back a
     * decision that has already been recorded and audited. If this throws,
     * the outcome is still on the request row and visible in the patient's
     * own privacy screen; what is lost is the push, not the answer.
     */
    private function tellPatient(
        ?string $userId,
        string $eventType,
        array $payload,
        ?string $actorId,
        string $resourceType,
        string $resourceId,
    ): void {
        if (! $userId) {
            return;
        }

        try {
            Notify::toUser(
                (string) $userId,
                $eventType,
                'account',
                $payload,
                'normal',
                $this->uuidOrNull($actorId),
                $resourceType,
                $resourceId,
            );
        } catch (\Throwable $e) {
            Log::warning('Governance desk: patient notification failed', [
                'event_type'  => $eventType,
                'resource_id' => $resourceId,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
