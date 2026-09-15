<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AttachmentAccessLog;
use App\Models\MedicalAttachment;
use App\Modules\FileStorage\Services\FileStorageService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * The patient's side of imported file attachments (docs/PLATFORM_IMPORTS.md,
 * group A): download one file a facility RELEASED to them.
 *
 * The link is a short-lived signed URL minted by
 * PatientDocumentService::releasedAttachments(); the route also runs the
 * patient-portal stack with `guardian.context`, so a guardian switched to a
 * dependent downloads the dependent's file. A file that is not this patient's,
 * not released, or voided is a 404. Every download writes an
 * AttachmentAccessLog row.
 */
class PatientFileController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    public function download(Request $request, FileStorageService $svc, string $id): BinaryFileResponse
    {
        abort_unless(Str::isUuid($id), 404);

        $patient = $request->attributes->get('viewing_patient') ?? $request->user()?->patient;
        abort_if(! $patient, 404);

        $attachment = MedicalAttachment::with('fileAsset')
            ->releasedToPatient()
            ->where('id', $id)
            ->where('resource_type', 'patient')
            ->where('resource_id', (string) $patient->id)
            ->firstOrFail();

        $asset     = $attachment->fileAsset;
        abort_if(! $asset, 404);
        $localPath = $svc->localPath($asset);
        abort_unless(is_file($localPath), 404);

        try {
            AttachmentAccessLog::record(
                fileAssetId:  $asset->id,
                accessedBy:   (string) $request->user()->id,
                accessType:   'download',
                ipAddress:    $request->ip(),
                userAgent:    Str::limit((string) $request->userAgent(), 250, ''),
                accessReason: 'patient',
                facilityId:   $asset->facility_id,
            );
        } catch (Throwable) {
            abort(503, __('files.audit_unavailable'));
        }

        $this->context->auditPatientAccess(
            actionType:   'patient_file_downloaded',
            resourceType: 'MedicalAttachment',
            resourceId:   $attachment->id,
            patientId:    $patient->id,
        );

        return response()->download($localPath, $asset->original_name, [
            'Content-Type'           => $asset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ]);
    }
}
