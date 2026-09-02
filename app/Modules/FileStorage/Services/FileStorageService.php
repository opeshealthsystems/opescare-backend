<?php

namespace App\Modules\FileStorage\Services;

use App\Models\FileAsset;
use App\Models\MedicalAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
    ];

    private const MAX_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

    /**
     * Upload a file and create a FileAsset record.
     */
    public function upload(
        UploadedFile $file,
        string $facilityId,
        string $uploadedBy,
        bool $isPrivate = true,
        string $disk = 'local'
    ): FileAsset {
        // Guard: mime type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException('File type not permitted: ' . $file->getMimeType());
        }

        // Guard: file size
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException('File exceeds maximum allowed size of 20 MB.');
        }

        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory  = 'medical/' . $facilityId . '/' . now()->format('Y/m');
        $path       = $file->storeAs($directory, $storedName, $disk);
        $checksum   = hash('sha256', file_get_contents($file->getRealPath()));

        return FileAsset::create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'disk'          => $disk,
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
            'checksum'      => $checksum,
            'uploaded_by'   => $uploadedBy,
            'facility_id'   => $facilityId,
            'is_private'    => $isPrivate,
        ]);
    }

    /**
     * Attach a FileAsset to a clinical resource.
     */
    public function attach(
        FileAsset $asset,
        string $resourceType,
        string $resourceId,
        ?string $category,
        ?string $description,
        string $attachedBy
    ): MedicalAttachment {
        return MedicalAttachment::create([
            'file_asset_id' => $asset->id,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'category'      => $category,
            'description'   => $description,
            'attached_by'   => $attachedBy,
        ]);
    }

    /**
     * Upload and attach in one step.
     */
    public function uploadAndAttach(
        UploadedFile $file,
        string $resourceType,
        string $resourceId,
        string $facilityId,
        string $actorId,
        ?string $category = null,
        ?string $description = null
    ): MedicalAttachment {
        $asset = $this->upload($file, $facilityId, $actorId);
        return $this->attach($asset, $resourceType, $resourceId, $category, $description, $actorId);
    }

    /**
     * Delete a file asset and all its attachments.
     */
    public function delete(FileAsset $asset): void
    {
        Storage::disk($asset->disk)->delete($asset->path);
        $asset->attachments()->delete();
        $asset->delete();
    }

    /**
     * Get the full path for streaming/download.
     */
    public function localPath(FileAsset $asset): string
    {
        return Storage::disk($asset->disk)->path($asset->path);
    }

    /**
     * Allowed mime types (for validation hint in views).
     */
    public static function allowedMimes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    public static function maxSizeMb(): int
    {
        return self::MAX_SIZE_BYTES / (1024 * 1024);
    }
}
