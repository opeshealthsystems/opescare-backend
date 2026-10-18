<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AttachmentAccessLog;
use App\Models\ImagingOrder;
use App\Models\RadiologyReport;
use App\Modules\FileStorage\Services\FileStorageService;
use App\Services\Lab\RadiologyReportService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The radiologist's report desk (routes/web.php, `capability:imaging_report`):
 * the queue of completed studies awaiting a report, and one report's page —
 * draft, finalize, amend (a new version), distribute to the patient.
 *
 * A surface only: every rule lives in RadiologyReportService. The facility is
 * read from PortalContextService, never from the request; an order at another
 * facility is a 404, never a 403. Every look at a patient's study is audited
 * through PortalContextService::auditPatientAccess.
 *
 * The PDF the patient downloads is rendered on demand (PatientDocumentService
 * IMAGING_REPORT) and never stored; the only stored bytes are an uploaded
 * attachment the radiologist chose to add.
 */
class ImagingReportController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly RadiologyReportService $reports,
    ) {
    }

    /** Completed studies with no final report, and finals not yet handed to the patient. */
    public function index(): View
    {
        $facilityId = $this->facilityId();

        $this->context->auditPatientAccess('imaging_report_queue_view', 'ImagingOrder', null, null, [
            'facility_id' => $facilityId,
        ]);

        $awaiting = $this->reports->awaitingReportForFacility($facilityId);
        $drafts   = $this->reports->getPendingForFacility($facilityId)
            ->keyBy('imaging_order_id');

        return view('portals.imaging.reports.index', [
            'awaiting'     => $awaiting,
            'drafts'       => $drafts,
            'undistributed' => $this->reports->awaitingDistributionForFacility($facilityId),
        ]);
    }

    public function edit(string $id): View
    {
        $order  = $this->orderOrFail($id);
        $report = $this->reports->currentForOrder($order);

        $this->audit('imaging_report_view', $order, $report);

        return view('portals.imaging.report', [
            'order'    => $order,
            'report'   => $report,
            'versions' => $this->reports->versionsForOrder($order),
            'patient'  => $order->patient,
        ]);
    }

    /** Save (or create) the draft; an uploaded PDF is attached to it. */
    public function save(Request $request, string $id): RedirectResponse
    {
        $order = $this->orderOrFail($id);
        $data  = $this->validatedText($request);

        try {
            $report = $this->reports->saveDraft($order, $this->actorId(), $data);
            $this->attachUpload($request, $report);
        } catch (RuntimeException $e) {
            return $this->back($order)->with('error', __('imaging_report.flash.not_editable'));
        }

        $this->audit('imaging_report_draft_saved', $order, $report);

        return $this->back($order)->with('success', __('imaging_report.flash.draft_saved'));
    }

    /** Save what is on the form, then sign it. */
    public function finalize(Request $request, string $id): RedirectResponse
    {
        $order = $this->orderOrFail($id);
        $data  = $this->validatedText($request);

        try {
            $report = $this->reports->saveDraft($order, $this->actorId(), $data);
            $this->attachUpload($request, $report);
            $report = $this->reports->finalize($report->id, $this->actorId());
        } catch (RuntimeException $e) {
            return $this->back($order)->with('error', __('imaging_report.flash.cannot_finalize'));
        }

        $this->audit('imaging_report_finalized', $order, $report);

        return $this->back($order)->with('success', __('imaging_report.flash.finalized'));
    }

    /** A new version with the corrected text; the previous one is kept. */
    public function amend(Request $request, string $id): RedirectResponse
    {
        $order   = $this->orderOrFail($id);
        $current = $this->reports->currentForOrder($order);

        abort_if($current === null, 404);

        $data = $this->validatedText($request) + $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $report = $this->reports->amend($current->id, $data['reason'], $data, $this->actorId());
            $this->attachUpload($request, $report);
        } catch (RuntimeException $e) {
            return $this->back($order)->with('error', __('imaging_report.flash.cannot_amend'));
        }

        $this->audit('imaging_report_amended', $order, $report, ['previous_report_id' => $current->id]);

        return $this->back($order)->with('success', __('imaging_report.flash.amended', ['n' => $report->version]));
    }

    /** Hand the current version to the patient: documents list + bell. */
    public function distribute(string $id): RedirectResponse
    {
        $order   = $this->orderOrFail($id);
        $current = $this->reports->currentForOrder($order);

        abort_if($current === null, 404);

        try {
            $report = $this->reports->distribute($current->id, [$this->actorId()]);
        } catch (RuntimeException $e) {
            return $this->back($order)->with('error', __('imaging_report.flash.cannot_distribute'));
        }

        $this->audit('imaging_report_distributed', $order, $report);

        return $this->back($order)->with('success', __('imaging_report.flash.distributed'));
    }

    /** The uploaded attachment of the current version, logged like any file read. */
    public function attachment(Request $request, FileStorageService $files, string $id): BinaryFileResponse
    {
        $order   = $this->orderOrFail($id);
        $current = $this->reports->currentForOrder($order);
        $attachment = $current?->attachment()->with('fileAsset')->first();

        abort_if($attachment === null || $attachment->fileAsset === null, 404);

        $asset = $attachment->fileAsset;
        $path  = $files->localPath($asset);

        abort_unless(is_file($path), 404);

        try {
            AttachmentAccessLog::record(
                fileAssetId:  $asset->id,
                accessedBy:   $this->actorId(),
                accessType:   'download',
                ipAddress:    $request->ip(),
                userAgent:    Str::limit((string) $request->userAgent(), 250, ''),
                accessReason: 'clinical',
                facilityId:   $this->facilityId(),
            );
        } catch (\Throwable) {
            abort(503, __('files.audit_unavailable'));
        }

        $this->audit('imaging_report_attachment_downloaded', $order, $current);

        return response()->file($path, [
            'Content-Type'        => $asset->mime_type,
            'Content-Disposition' => 'inline; filename="' . Str::slug(pathinfo($asset->original_name, PATHINFO_FILENAME)) . '.' . pathinfo($asset->path, PATHINFO_EXTENSION) . '"',
            'Cache-Control'       => 'no-store, private',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        abort_if(blank($facilityId), 403, __('imaging_report.no_facility'));

        return (string) $facilityId;
    }

    private function actorId(): string
    {
        return (string) ($this->context->actorId() ?? auth()->id());
    }

    /** This facility's order, or 404 — a row elsewhere is not "forbidden", it does not exist here. */
    private function orderOrFail(string $id): ImagingOrder
    {
        abort_unless(Str::isUuid($id), 404);

        $order = ImagingOrder::with(['patient', 'orderedBy'])
            ->where('facility_id', $this->facilityId())
            ->whereKey($id)
            ->first();

        abort_if($order === null, 404);

        return $order;
    }

    /** @return array{findings: string, impression: string, recommendation: ?string, clinical_indication: ?string} */
    private function validatedText(Request $request): array
    {
        $validated = $request->validate([
            'findings'            => ['required', 'string', 'min:3', 'max:20000'],
            'impression'          => ['required', 'string', 'min:3', 'max:5000'],
            'recommendation'      => ['nullable', 'string', 'max:5000'],
            'clinical_indication' => ['nullable', 'string', 'max:5000'],
            'attachment'          => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf'],
        ]);

        return [
            'findings'            => $validated['findings'],
            'impression'          => $validated['impression'],
            'recommendation'      => $validated['recommendation'] ?? null,
            'clinical_indication' => $validated['clinical_indication'] ?? null,
        ];
    }

    private function attachUpload(Request $request, RadiologyReport $report): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        $this->reports->attachFile($report, $request->file('attachment'), $this->actorId());
    }

    private function audit(string $action, ImagingOrder $order, ?RadiologyReport $report, array $extra = []): void
    {
        $this->context->auditPatientAccess(
            $action,
            $report ? 'RadiologyReport' : 'ImagingOrder',
            (string) ($report?->id ?? $order->id),
            (string) $order->patient_id,
            // audit_events has no free-form metadata column; the order id and
            // anything else travels in `after_state` (a JSON column).
            ['after_state' => ['imaging_order_id' => $order->id] + $extra],
        );
    }

    private function back(ImagingOrder $order): RedirectResponse
    {
        return redirect()->route('portals.imaging.report.edit', $order->id);
    }
}
