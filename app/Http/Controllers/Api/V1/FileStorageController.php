<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FileAsset;
use App\Modules\FileStorage\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * FileStorageController — Medical File Upload & Attachment API.
 *
 * Handles secure file uploads for clinical resources (visits, notes, patients).
 * Files are stored under medical/{facility_id}/YYYY/MM/ with UUID filenames.
 *
 * SECURITY RULES:
 * - Allowed MIME types: PDF, JPEG, PNG, GIF, WEBP, DOC, DOCX, XLS, XLSX, TXT, CSV
 * - Maximum file size: 20 MB
 * - facility_id always from middleware attributes
 * - File download streams via local path — never exposes internal storage paths
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  POST  /v1/files/upload                      — upload file, returns FileAsset
 *  POST  /v1/files/upload-and-attach           — upload + attach to a resource in one step
 *  POST  /v1/files/{asset}/attach              — attach existing asset to a resource
 *  GET   /v1/files/{asset}                     — get file metadata
 *  GET   /v1/files/{asset}/download            — stream file for download
 *  DELETE /v1/files/{asset}                    — delete file and all attachments
 */
class FileStorageController extends Controller
{
    public function __construct(private readonly FileStorageService $service) {}

    /**
     * Upload a file.
     * Multipart: file (required), uploaded_by (required), is_private? (default true)
     * facility_id from middleware attributes.
     */
    public function upload(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $request->validate([
            'file'        => ['required', 'file', 'max:20480'], // 20 MB in KB
            'uploaded_by' => ['required', 'uuid'],
            'is_private'  => ['nullable', 'boolean'],
        ]);

        try {
            $asset = $this->service->upload(
                $request->file('file'),
                $facilityId,
                $request->input('uploaded_by'),
                $request->boolean('is_private', true)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.file_uploaded'),
            'data'    => $this->serializeAsset($asset),
        ], 201);
    }

    /**
     * Upload and attach to a clinical resource in one step.
     * Multipart: file, uploaded_by, resource_type, resource_id, category?, description?
     */
    public function uploadAndAttach(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $validated = $request->validate([
            'file'          => ['required', 'file', 'max:20480'],
            'uploaded_by'   => ['required', 'uuid'],
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id'   => ['required', 'uuid'],
            'category'      => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $attachment = $this->service->uploadAndAttach(
                $request->file('file'),
                $validated['resource_type'],
                $validated['resource_id'],
                $facilityId,
                $validated['uploaded_by'],
                $validated['category'] ?? null,
                $validated['description'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.file_uploaded_attached'),
            'data'    => $attachment->load('fileAsset'),
        ], 201);
    }

    /**
     * Attach an already-uploaded FileAsset to a clinical resource.
     * Body: { resource_type, resource_id, attached_by, category?, description? }
     */
    public function attach(FileAsset $asset, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id'   => ['required', 'uuid'],
            'attached_by'   => ['required', 'uuid'],
            'category'      => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        $attachment = $this->service->attach(
            $asset,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['category'] ?? null,
            $validated['description'] ?? null,
            $validated['attached_by']
        );

        return response()->json([
            'message' => __('api.file_attached'),
            'data'    => $attachment,
        ], 201);
    }

    /**
     * Get file asset metadata.
     */
    public function show(FileAsset $asset): JsonResponse
    {
        return response()->json(['data' => $this->serializeAsset($asset)]);
    }

    /**
     * Stream file for download.
     * Returns the file contents with appropriate Content-Type header.
     * Never exposes the internal storage path.
     */
    public function download(FileAsset $asset): Response
    {
        $path = $this->service->localPath($asset);

        if (!file_exists($path)) {
            abort(404, 'File not found on disk.');
        }

        return response()->file($path, [
            'Content-Type'        => $asset->mime_type,
            'Content-Disposition' => 'attachment; filename="' . addslashes($asset->original_name) . '"',
        ]);
    }

    /**
     * Delete a file asset and all its medical attachments.
     * This is irreversible — storage file and DB records are both removed.
     */
    public function delete(FileAsset $asset): JsonResponse
    {
        $this->service->delete($asset);
        return response()->json(['message' => __('api.file_deleted')]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function serializeAsset(FileAsset $asset): array
    {
        return [
            'id'            => $asset->id,
            'original_name' => $asset->original_name,
            'mime_type'     => $asset->mime_type,
            'size_bytes'    => $asset->size_bytes,
            'is_private'    => $asset->is_private,
            'facility_id'   => $asset->facility_id,
            'uploaded_by'   => $asset->uploaded_by,
            'created_at'    => $asset->created_at?->toISOString(),
            // Never expose stored_name, disk, or path
        ];
    }
}
