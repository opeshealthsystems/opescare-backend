<?php
namespace App\Services\Lab;

use App\Models\ImagingOrder;
use App\Models\MedicalAttachment;
use App\Models\RadiologyReport;
use App\Modules\FileStorage\Services\FileStorageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * The radiology report lifecycle: draft -> final -> (amended, a NEW version)
 * -> distributed to the patient.
 *
 * VERSIONING. An amendment never edits a finalized row. It creates version
 * n+1 pointing at the row it replaces (`supersedes_report_id`) and stamps the
 * old one `superseded_at`. Readers (the patient's documents, the worklist,
 * the detail panel) see the CURRENT version only — the one with
 * `superseded_at IS NULL` — and the audit trail keeps every earlier text.
 *
 * DISTRIBUTION. `distributed_at` is the moment the patient may read it. The
 * PDF the patient downloads is rendered on demand by PatientDocumentService
 * (IMAGING_REPORT) and never stored; the ONLY stored bytes are a PDF the
 * radiologist chose to upload, which is a MedicalAttachment on the patient,
 * released at distribution time.
 */
class RadiologyReportService {
    public function __construct(private readonly ?FileStorageService $files = null)
    {
    }

    public function createDraft(array $data): RadiologyReport {
        return RadiologyReport::create(array_merge($data, [
            'status'       => 'draft',
            'distributed_to' => [],
        ]));
    }

    /** The version of the order's report a reader should see, if any. */
    public function currentForOrder(ImagingOrder $order): ?RadiologyReport
    {
        return RadiologyReport::query()
            ->where('imaging_order_id', $order->id)
            ->current()
            ->orderByDesc('version')
            ->first();
    }

    /** Every version, newest first — the audit trail the panel shows. */
    public function versionsForOrder(ImagingOrder $order): Collection
    {
        return RadiologyReport::query()
            ->where('imaging_order_id', $order->id)
            ->with('reportedBy')
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Save the draft for an order: create it, or update the existing draft.
     * A finalized report is NOT edited here — that is an amendment.
     *
     * @param  array{findings: string, impression: string, recommendation?: ?string, clinical_indication?: ?string}  $data
     */
    public function saveDraft(ImagingOrder $order, string $authorId, array $data): RadiologyReport
    {
        $current = $this->currentForOrder($order);

        if ($current !== null && $current->status !== 'draft') {
            throw new RuntimeException("Report {$current->id} is {$current->status}; amend it instead of editing.");
        }

        $fields = [
            'findings'            => (string) ($data['findings'] ?? ''),
            'impression'          => (string) ($data['impression'] ?? ''),
            'recommendation'      => $data['recommendation'] ?? null,
            'clinical_indication' => (string) ($data['clinical_indication'] ?? $order->clinical_indication ?? ''),
            'reported_by'         => $authorId,
        ];

        if ($current !== null) {
            $current->update($fields);

            return $current->fresh();
        }

        return $this->createDraft($fields + [
            'patient_id'       => $order->patient_id,
            'facility_id'      => $order->facility_id,
            'imaging_order_id' => $order->id,
            'ordered_by'       => $order->ordered_by,
            'modality'         => $this->modalityFor($order->modality),
            'body_part'        => (string) ($order->body_part ?: '—'),
            'study_date'       => $order->completed_at ?? $order->scheduled_at ?? $order->ordered_at ?? now(),
            'version'          => 1,
        ]);
    }

    /**
     * Store the radiologist's uploaded PDF against this report version. One
     * file per version: a second upload voids the first. The attachment hangs
     * off the PATIENT so the portal's released-files list can find it; it is
     * released only at distribution.
     */
    public function attachFile(RadiologyReport $report, UploadedFile $file, string $actorId): MedicalAttachment
    {
        $files = $this->files ?? app(FileStorageService::class);

        $previous = $report->attachment()->first();
        if ($previous !== null) {
            $files->void($previous, 'replaced by a newer upload', $actorId);
        }

        $attachment = $files->uploadAndAttach(
            $file,
            'patient',
            (string) $report->patient_id,
            (string) $report->facility_id,
            $actorId,
            MedicalAttachment::CATEGORY_RADIOLOGY_REPORT,
            trim(($report->modality ?? '') . ' ' . ($report->body_part ?? '')) ?: null,
        );

        $attachment->forceFill(['radiology_report_id' => $report->id])->save();

        return $attachment;
    }

    public function finalize(string $reportId, string $radiologistId): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (in_array($report->status, ['final','amended','corrected'])) {
            throw new RuntimeException("Report {$reportId} cannot be finalized from status '{$report->status}'.");
        }
        if (trim((string) $report->findings) === '' || trim((string) $report->impression) === '') {
            throw new RuntimeException("Report {$reportId} needs findings and an impression before it is finalized.");
        }
        $report->update([
            'status'      => 'final',
            'reported_by' => $radiologistId,
            'finalized_at'=> now(),
        ]);
        return $report->fresh();
    }

    /**
     * Amend a finalized report: a NEW version carrying the corrected text,
     * the old row kept and marked superseded. Returns the new version.
     *
     * The new version is `amended` and finalized at once (an amendment is a
     * signed statement, not a draft), but NOT distributed: the radiologist
     * releases it to the patient as a separate act, like the first version.
     */
    public function amend(string $reportId, string $reason, array $changes, ?string $authorId = null): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (!in_array($report->status, ['final','amended','corrected'])) {
            throw new RuntimeException("Only finalized reports can be amended. Current status: '{$report->status}'.");
        }
        if ($report->superseded_at !== null) {
            throw new RuntimeException("Report {$reportId} has already been superseded by a newer version.");
        }
        $allowed = ['findings','impression','recommendation','clinical_indication'];
        $safe    = array_intersect_key($changes, array_flip($allowed));
        // A field the form left empty keeps the signed text: findings, impression
        // and clinical_indication are NOT NULL columns, and "no change" is not
        // "blank". Only `recommendation` may be cleared on purpose.
        $safe    = array_filter($safe, fn ($v, $k) => $k === 'recommendation' || ($v !== null && trim((string) $v) !== ''), ARRAY_FILTER_USE_BOTH);

        return DB::transaction(function () use ($report, $safe, $reason, $authorId) {
            $copy = $report->replicate([
                'id', 'finalized_at', 'amended_at', 'amendment_reason', 'distributed_at', 'superseded_at',
                'supersedes_report_id', 'version', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->fill($safe);
            $copy->forceFill([
                'status'               => 'amended',
                'reported_by'          => $authorId ?? $report->reported_by,
                'finalized_at'         => now(),
                'amended_at'           => now(),
                'amendment_reason'     => $reason,
                'distributed_to'       => $report->distributed_to ?? [],
                'distributed_at'       => null,
                'version'              => ((int) $report->version) + 1,
                'supersedes_report_id' => $report->id,
                'superseded_at'        => null,
            ]);
            $copy->save();

            $report->forceFill(['superseded_at' => now()])->save();

            return $copy->fresh();
        });
    }

    public function distribute(string $reportId, array $userIds): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (!in_array($report->status, ['final','amended','corrected'])) {
            throw new RuntimeException("Only finalized reports can be distributed. Current status: '{$report->status}'.");
        }
        if ($report->superseded_at !== null) {
            throw new RuntimeException("Report {$reportId} is superseded; distribute the current version.");
        }
        $existing = $report->distributed_to ?? [];
        $merged   = array_values(array_unique(array_merge($existing, $userIds)));
        $report->update([
            'distributed_to' => $merged,
            'distributed_at' => now(),
        ]);

        // The uploaded PDF, if any, reaches the patient's files at the same moment.
        $attachment = $report->attachment()->first();
        if ($attachment !== null && $attachment->released_at === null) {
            $attachment->forceFill([
                'released_at' => now(),
                'released_by' => $userIds[0] ?? null,
            ])->save();
        }

        if (class_exists(\App\Events\RadiologyReportDistributed::class)) {
            Event::dispatch(new \App\Events\RadiologyReportDistributed($report, $userIds));
        }
        return $report->fresh();
    }

    public function getPendingForFacility(string $facilityId): Collection {
        return RadiologyReport::where('facility_id', $facilityId)
            ->whereIn('status', ['draft','preliminary'])
            ->with(['patient','reportedBy'])
            ->orderBy('study_date')
            ->get();
    }

    /**
     * Orders at this facility that are completed and whose current report is
     * not yet final (none, or a draft), oldest first — the radiologist's queue.
     */
    public function awaitingReportForFacility(string $facilityId): Collection
    {
        return ImagingOrder::query()
            ->where('facility_id', $facilityId)
            ->where('status', 'completed')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('radiology_reports')
                    ->whereColumn('radiology_reports.imaging_order_id', 'imaging_orders.id')
                    ->whereNull('radiology_reports.superseded_at')
                    ->whereNull('radiology_reports.deleted_at')
                    ->whereIn('radiology_reports.status', RadiologyReport::RELEASED_STATUSES);
            })
            ->with(['patient'])
            ->orderBy('completed_at')
            ->get();
    }

    /** Current reports at this facility that are final but not yet handed to the patient. */
    public function awaitingDistributionForFacility(string $facilityId): Collection
    {
        return RadiologyReport::query()
            ->where('facility_id', $facilityId)
            ->current()
            ->whereIn('status', RadiologyReport::RELEASED_STATUSES)
            ->whereNull('distributed_at')
            ->with(['patient', 'reportedBy', 'imagingOrder'])
            ->orderBy('finalized_at')
            ->get();
    }

    /** imaging_orders.modality -> radiology_reports.modality (a narrower list). */
    private function modalityFor(?string $modality): string
    {
        return match ((string) $modality) {
            'xray', 'ct', 'mri', 'ultrasound', 'echo', 'nuclear', 'pet' => (string) $modality,
            default => 'other',
        };
    }
}
