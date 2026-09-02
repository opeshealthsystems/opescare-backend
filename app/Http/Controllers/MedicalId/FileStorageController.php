<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FileAsset;
use App\Models\MedicalAttachment;
use App\Modules\FileStorage\Services\FileStorageService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * FileStorageController — scans, consent forms, lab PDFs and ID documents
 * attached to a facility's records.
 *
 * These are patient documents, and the download route used to say so out loud:
 * `FileAsset::findOrFail($id)` with the comment "In production: check ownership
 * or facility access here". Any signed-in staff member could stream any file in
 * the system by id, and `destroy()` could delete any attachment the same way.
 * The listing was scoped, but to `Facility::value('id') ?? 'demo-facility'` —
 * whichever facility Postgres happened to return first.
 *
 * Now: the facility comes from the session and fails closed, both `{id}` routes
 * resolve their row through the facility, the attachment list for a resource is
 * limited to files this facility owns, and every read or delete of a patient
 * document is audited through PortalContextService.
 */
class FileStorageController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    // ─── Context helpers ──────────────────────────────────────────

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * The old fallback string 'demo-facility' is gone: it is not a real
     * facility id, so it silently bucketed uploads into a facility that does
     * not exist. The single-facility fallback below holds only when there
     * genuinely is exactly one facility.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which facility this document belongs to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /**
     * The patient this resource id refers to, if it is a patient at all.
     *
     * `resource_id` is free text here (it can be a visit, an order, or a
     * health ID string), while audit_events.patient_id is a uuid column, so
     * anything that is not a uuid is recorded as no patient rather than
     * throwing the audit write away.
     */
    private function patientIdFor(string $resourceType, ?string $resourceId): ?string
    {
        return ($resourceType === 'patient' && $resourceId && Str::isUuid($resourceId))
            ? $resourceId
            : null;
    }

    // ─── Attachments index for a resource ─────────────────────────

    public function index(Request $request)
    {
        $facilityId   = $this->facilityId();
        $resourceType = $request->input('resource_type', 'patient');
        $resourceId   = $request->input('resource_id', '');

        // MedicalAttachment::forResource() takes a resource id straight off the
        // query string and has no facility column of its own — it reaches the
        // facility through its file asset. Without this join, typing another
        // hospital's patient id into the URL listed their documents.
        $attachments = $resourceId
            ? MedicalAttachment::with('fileAsset')
                ->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->whereHas('fileAsset', fn ($q) => $q->where('facility_id', $facilityId))
                ->latest()
                ->get()
            : collect();

        $assets = FileAsset::where('facility_id', $facilityId)
            ->latest()
            ->paginate(20);

        if ($resourceId) {
            $this->context->auditPatientAccess(
                actionType:   'file_attachment_list_view',
                resourceType: $resourceType === 'patient' ? 'Patient' : $resourceType,
                resourceId:   $resourceId,
                patientId:    $this->patientIdFor($resourceType, $resourceId),
            );
        }

        return view('portals.staff.files.index', [
            'attachments'  => $attachments,
            'assets'       => $assets,
            'resourceType' => $resourceType,
            'resourceId'   => $resourceId,
            'categories'   => MedicalAttachment::categories(),
        ]);
    }

    // ─── Upload form ───────────────────────────────────────────────

    public function create(Request $request)
    {
        return view('portals.staff.files.upload', [
            'resourceType' => $request->input('resource_type', 'patient'),
            'resourceId'   => $request->input('resource_id', ''),
            'categories'   => MedicalAttachment::categories(),
            'maxSizeMb'    => FileStorageService::maxSizeMb(),
        ]);
    }

    // ─── Process upload ────────────────────────────────────────────

    public function store(Request $request, FileStorageService $svc)
    {
        $request->validate([
            'file'          => 'required|file|max:20480', // 20 MB in KB
            'resource_type' => 'required|string|max:50',
            'resource_id'   => 'required|string|max:100',
            'category'      => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:300',
        ]);

        $facilityId = $this->facilityId();

        try {
            $attachment = $svc->uploadAndAttach(
                file: $request->file('file'),
                resourceType: $request->resource_type,
                resourceId:   $request->resource_id,
                facilityId:   $facilityId,
                actorId:      $this->demoActorId(),
                category:     $request->category,
                description:  $request->description,
            );

            $this->context->auditPatientAccess(
                actionType:   'file_attachment_uploaded',
                resourceType: $request->resource_type === 'patient' ? 'Patient' : $request->resource_type,
                resourceId:   $attachment->id,
                patientId:    $this->patientIdFor($request->resource_type, $request->resource_id),
            );

            return redirect()
                ->route('portals.staff.files.index', [
                    'resource_type' => $request->resource_type,
                    'resource_id'   => $request->resource_id,
                ])
                ->with('success', __('flash.file_uploaded_attached'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', __('flash.file_upload_failed', ['error' => $e->getMessage()]));
        }
    }

    // ─── Download / stream ─────────────────────────────────────────

    public function download(string $id, FileStorageService $svc)
    {
        // The ownership check the old comment asked for. A file is streamed
        // only to the facility that owns it.
        $asset = FileAsset::where('id', $this->assertUuid($id))
            ->where('facility_id', $this->facilityId())
            ->firstOrFail();

        $localPath = $svc->localPath($asset);

        if (!file_exists($localPath)) {
            abort(404, 'File not found on storage.');
        }

        $this->context->auditPatientAccess(
            actionType:   'file_asset_downloaded',
            resourceType: 'FileAsset',
            resourceId:   $asset->id,
        );

        return response()->download($localPath, $asset->original_name, [
            'Content-Type' => $asset->mime_type,
        ]);
    }

    // ─── Delete attachment + asset ─────────────────────────────────

    public function destroy(string $id, FileStorageService $svc)
    {
        $attachment = MedicalAttachment::with('fileAsset')
            ->where('id', $this->assertUuid($id))
            ->whereHas('fileAsset', fn ($q) => $q->where('facility_id', $this->facilityId()))
            ->firstOrFail();

        $resourceType = $attachment->resource_type;
        $resourceId   = $attachment->resource_id;

        try {
            // Only delete asset if no other attachments reference it
            $asset = $attachment->fileAsset;
            $attachment->delete();

            if ($asset && $asset->attachments()->count() === 0) {
                $svc->delete($asset);
            }

            $this->context->auditPatientAccess(
                actionType:   'file_attachment_removed',
                resourceType: $resourceType === 'patient' ? 'Patient' : $resourceType,
                resourceId:   $id,
                patientId:    $this->patientIdFor($resourceType, $resourceId),
            );

            return redirect()
                ->route('portals.staff.files.index', [
                    'resource_type' => $resourceType,
                    'resource_id'   => $resourceId,
                ])
                ->with('success', __('flash.attachment_removed'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.file_delete_failed', ['error' => $e->getMessage()]));
        }
    }
}
