<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AttachmentAccessLog;
use App\Models\MedicalAttachment;
use App\Models\Patient;
use App\Modules\FileStorage\Services\FileStorageService;
use App\Services\Chart\PatientChartAccess;
use App\Services\Lab\RadiologyReportService;
use App\Services\Portal\DeskFacilityContext;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * FileStorageController — scans, consent forms, lab PDFs, radiology reports
 * attached to ONE patient's record at a facility.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group A) and adapted
 * to the Finder's rules (routes/imports_files.php):
 *
 *  - Patient-scoped, never a free-text resource id: every action names a
 *    patient and passes the chart's own door (PatientChartAccess ->
 *    ClinicalRelationshipGate). No basis -> 404 and a `denied` access_logs row.
 *  - The facility comes from DeskFacilityContext only; a file owned by
 *    another facility is a 404, exactly like a malformed id.
 *  - Downloads go through a short-lived signed URL and every one writes an
 *    AttachmentAccessLog row. Nothing is ever public.
 *  - destroy() is gone: an attachment is VOIDED with a reason; bytes stay.
 *  - A file reaches the patient's documents only after "Release to patient".
 *    A radiology report release finalises and distributes a RadiologyReport
 *    through RadiologyReportService, whose distribution event notifies the
 *    patient (App\Listeners\NotifyPatientOfRadiologyReport).
 */
class FileStorageController extends Controller
{
    /** How long a staff download link lives. */
    public const LINK_MINUTES = 5;

    /** The modalities radiology_reports accepts. */
    public const MODALITIES = ['xray', 'ct', 'mri', 'ultrasound', 'echo', 'nuclear', 'pet', 'other'];

    public function __construct(private readonly PortalContextService $context) {}

    // ─── Context helpers ──────────────────────────────────────────

    private function actorId(): string
    {
        return (string) auth()->id();
    }

    /** DeskFacilityContext only — never a header, the body or a "first facility" fallback. */
    private function facilityId(): string
    {
        $facilityId = app(DeskFacilityContext::class)->resolve();

        abort_unless($facilityId, 403, __('files.no_facility_context'));

        return (string) $facilityId;
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /**
     * The chart's door. The patient must exist (following a merge) and this
     * clinician must hold a basis that covers documents; otherwise 404 with a
     * denial logged, so a screen never confirms that a record exists.
     */
    private function gate(string $patientId, string $facilityId): Patient
    {
        $patient = Str::isUuid($patientId) ? Patient::resolveId($patientId) : null;

        abort_if($patient === null, 404);

        $access = PatientChartAccess::evaluate($patient, $this->actorId(), $facilityId);

        if (! $access->covers('documents')) {
            $access->auditDenial('documents', 'MedicalAttachment');
            abort(404);
        }

        abort_unless($access->auditRead('documents', 'MedicalAttachment'), 503, __('files.audit_unavailable'));

        return $patient;
    }

    /**
     * An attachment this facility owns, on a patient this clinician may open.
     *
     * @return array{0: MedicalAttachment, 1: Patient}
     */
    private function attachmentFor(string $id, string $facilityId): array
    {
        $attachment = MedicalAttachment::with('fileAsset')
            ->where('id', $this->assertUuid($id))
            ->where('resource_type', 'patient')
            ->whereHas('fileAsset', fn ($q) => $q->where('facility_id', $facilityId))
            ->firstOrFail();

        return [$attachment, $this->gate((string) $attachment->resource_id, $facilityId)];
    }

    // ─── Attachments index for a patient ──────────────────────────

    public function index(string $patientId): View
    {
        $facilityId = $this->facilityId();
        $patient    = $this->gate($patientId, $facilityId);

        // Only files THIS facility attached; voided ones stay listed, marked.
        $attachments = MedicalAttachment::with('fileAsset')
            ->where('resource_type', 'patient')
            ->where('resource_id', $patient->id)
            ->whereHas('fileAsset', fn ($q) => $q->where('facility_id', $facilityId))
            ->latest()
            ->limit(200)
            ->get();

        $links = $attachments
            ->reject(fn (MedicalAttachment $a) => $a->isVoided())
            ->mapWithKeys(fn (MedicalAttachment $a) => [
                $a->id => URL::temporarySignedRoute('portals.files.download', now()->addMinutes(self::LINK_MINUTES), ['id' => $a->id]),
            ]);

        $this->context->auditPatientAccess(
            actionType:   'file_attachment_list_view',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return view('portals.staff.files.index', [
            'patient'     => $patient,
            'attachments' => $attachments,
            'links'       => $links,
            'categories'  => MedicalAttachment::categories(),
            'modalities'  => self::MODALITIES,
        ]);
    }

    // ─── Upload form ───────────────────────────────────────────────

    public function create(string $patientId): View
    {
        $patient = $this->gate($patientId, $this->facilityId());

        return view('portals.staff.files.upload', [
            'patient'    => $patient,
            'categories' => MedicalAttachment::categories(),
            'maxSizeMb'  => FileStorageService::maxSizeMb(),
        ]);
    }

    // ─── Process upload ────────────────────────────────────────────

    public function store(Request $request, FileStorageService $svc, string $patientId): RedirectResponse
    {
        $facilityId = $this->facilityId();
        $patient    = $this->gate($patientId, $facilityId);

        $request->validate([
            'file'        => 'required|file|max:' . (FileStorageService::maxSizeMb() * 1024),
            'category'    => 'required|string|in:' . implode(',', array_keys(MedicalAttachment::categories())),
            'description' => 'nullable|string|max:300',
        ]);

        try {
            $attachment = $svc->uploadAndAttach(
                file: $request->file('file'),
                resourceType: 'patient',
                resourceId:   $patient->id,
                facilityId:   $facilityId,
                actorId:      $this->actorId(),
                category:     $request->input('category'),
                description:  $request->input('description'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $this->context->auditPatientAccess(
            actionType:   'file_attachment_uploaded',
            resourceType: 'MedicalAttachment',
            resourceId:   $attachment->id,
            patientId:    $patient->id,
        );

        return redirect()
            ->route('portals.files.index', ['patientId' => $patient->id])
            ->with('success', __('files.flash.uploaded'));
    }

    // ─── Download / stream ─────────────────────────────────────────

    public function download(Request $request, FileStorageService $svc, string $id): BinaryFileResponse
    {
        $facilityId = $this->facilityId();
        [$attachment, $patient] = $this->attachmentFor($id, $facilityId);

        abort_if($attachment->isVoided(), 404);

        $asset     = $attachment->fileAsset;
        $localPath = $svc->localPath($asset);

        abort_unless(is_file($localPath), 404);

        // Every download is logged; no trail, no file.
        try {
            AttachmentAccessLog::record(
                fileAssetId:  $asset->id,
                accessedBy:   $this->actorId(),
                accessType:   'download',
                ipAddress:    $request->ip(),
                userAgent:    Str::limit((string) $request->userAgent(), 250, ''),
                accessReason: 'clinical',
                facilityId:   $facilityId,
            );
        } catch (Throwable) {
            abort(503, __('files.audit_unavailable'));
        }

        $this->context->auditPatientAccess(
            actionType:   'file_asset_downloaded',
            resourceType: 'FileAsset',
            resourceId:   $asset->id,
            patientId:    $patient->id,
        );

        return response()->download($localPath, $asset->original_name, [
            'Content-Type'           => $asset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ]);
    }

    // ─── Void (replaces the platform's destroy) ────────────────────

    public function void(Request $request, FileStorageService $svc, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();
        [$attachment, $patient] = $this->attachmentFor($id, $facilityId);

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        $svc->void($attachment, $validated['reason'], $this->actorId());

        $this->context->auditPatientAccess(
            actionType:   'file_attachment_voided',
            resourceType: 'MedicalAttachment',
            resourceId:   $attachment->id,
            patientId:    $patient->id,
        );

        return redirect()
            ->route('portals.files.index', ['patientId' => $patient->id])
            ->with('success', __('files.flash.voided'));
    }

    // ─── Release to the patient ───────────────────────────────────

    public function release(Request $request, RadiologyReportService $radiology, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();
        [$attachment, $patient] = $this->attachmentFor($id, $facilityId);

        abort_if($attachment->isVoided(), 409, __('files.err.voided'));

        if ($attachment->isReleased()) {
            return redirect()
                ->route('portals.files.index', ['patientId' => $patient->id])
                ->with('info', __('files.flash.already_released'));
        }

        $actorId = $this->actorId();

        if ($attachment->isRadiologyReport()) {
            $validated = $request->validate([
                'modality'   => 'required|in:' . implode(',', self::MODALITIES),
                'body_part'  => 'required|string|max:150',
                'study_date' => 'required|date|before_or_equal:today',
            ]);

            $reportId = DB::transaction(function () use ($radiology, $attachment, $patient, $facilityId, $actorId, $validated) {
                // The report IS the attached PDF — this desk does not write
                // findings. The row points at the file rather than invent text.
                $pointer = 'See attached report file ' . $attachment->file_asset_id;

                $report = $radiology->createDraft([
                    'patient_id'          => $patient->id,
                    'facility_id'         => $facilityId,
                    'ordered_by'          => $actorId,
                    'reported_by'         => $actorId,
                    'modality'            => $validated['modality'],
                    'body_part'           => $validated['body_part'],
                    'study_date'          => $validated['study_date'],
                    'clinical_indication' => $pointer,
                    'findings'            => $pointer,
                    'impression'          => $pointer,
                ]);

                $attachment->forceFill([
                    'released_at'         => now(),
                    'released_by'         => $actorId,
                    'radiology_report_id' => $report->id,
                ])->save();

                $radiology->finalize($report->id, $actorId);

                return $report->id;
            });

            // After the commit: distribution fires the patient notification,
            // and a failed bell must not roll the release back.
            $radiology->distribute($reportId, [$actorId]);
        } else {
            $attachment->forceFill([
                'released_at' => now(),
                'released_by' => $actorId,
            ])->save();
        }

        $this->context->auditPatientAccess(
            actionType:   'file_attachment_released',
            resourceType: 'MedicalAttachment',
            resourceId:   $attachment->id,
            patientId:    $patient->id,
        );

        return redirect()
            ->route('portals.files.index', ['patientId' => $patient->id])
            ->with('success', __('files.flash.released'));
    }
}
