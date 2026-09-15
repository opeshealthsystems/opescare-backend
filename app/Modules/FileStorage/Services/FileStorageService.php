<?php

namespace App\Modules\FileStorage\Services;

use App\Models\AttachmentAudit;
use App\Models\FileAsset;
use App\Models\MedicalAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group A), adapted to
 * the Finder's rules:
 *
 *  - PDF, JPEG and PNG only, identified from the BYTES (magic numbers), never
 *    from the multipart header or the extension; 10 MB ceiling.
 *  - The private `local` disk only (storage/app/private), like
 *    App\Services\CareMap\FacilityPhotoService. Nothing is ever public.
 *  - The platform's delete() (bytes + rows gone) is replaced by void(): the
 *    attachment is marked voided with a reason and the bytes stay.
 */
class FileStorageService
{
    /** Detected type → the extension the file is stored under. */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

    private const DISK = 'local';

    /**
     * Upload a file and create a FileAsset record.
     */
    public function upload(
        UploadedFile $file,
        string $facilityId,
        string $uploadedBy,
        bool $isPrivate = true,
        string $disk = self::DISK
    ): FileAsset {
        // Guard: file size
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException(__('files.err.too_large', ['max' => self::maxSizeMb()]));
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        $mime  = self::detectMime($bytes);

        // Guard: type, read out of the bytes
        if ($mime === null) {
            throw new \InvalidArgumentException(__('files.err.type'));
        }

        // The Finder keeps patient files on the private disk, whatever the caller asked.
        $disk = self::DISK;

        $storedName = Str::uuid() . '.' . self::ALLOWED_MIME_TYPES[$mime];
        $directory  = 'medical/' . $facilityId . '/' . now()->format('Y/m');
        $path       = $directory . '/' . $storedName;
        Storage::disk($disk)->put($path, $bytes);
        $checksum   = hash('sha256', $bytes);

        $asset = FileAsset::create([
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'stored_name'   => $storedName,
            'disk'          => $disk,
            'path'          => $path,
            'mime_type'     => $mime,
            'size_bytes'    => strlen($bytes),
            'checksum'      => $checksum,
            'uploaded_by'   => $uploadedBy,
            'facility_id'   => $facilityId,
            'is_private'    => true,
        ]);

        rescue(fn () => AttachmentAudit::record($asset->id, 'uploaded', [
            'performed_by' => Str::isUuid($uploadedBy) ? $uploadedBy : null,
            'ip_address'   => request()?->ip(),
            'payload'      => ['mime' => $mime, 'size_bytes' => strlen($bytes)],
        ]), null, false);

        return $asset;
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
            'status'        => MedicalAttachment::STATUS_ACTIVE,
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
     * Void an attachment. Replaces the platform's delete(): the row is marked,
     * the reason kept, and the bytes stay on disk. Voiding twice is a no-op.
     */
    public function void(MedicalAttachment $attachment, string $reason, string $actorId): MedicalAttachment
    {
        if ($attachment->isVoided()) {
            return $attachment;
        }

        $attachment->forceFill([
            'status'      => MedicalAttachment::STATUS_VOIDED,
            'void_reason' => $reason,
            'voided_by'   => Str::isUuid($actorId) ? $actorId : null,
            'voided_at'   => now(),
        ])->save();

        rescue(fn () => AttachmentAudit::record($attachment->file_asset_id, 'voided', [
            'performed_by' => Str::isUuid($actorId) ? $actorId : null,
            'ip_address'   => request()?->ip(),
            'payload'      => ['attachment_id' => $attachment->id, 'reason' => $reason],
        ]), null, false);

        return $attachment->refresh();
    }

    /**
     * Get the full path for streaming/download.
     */
    public function localPath(FileAsset $asset): string
    {
        return Storage::disk($asset->disk)->path($asset->path);
    }

    /** PDF, JPEG or PNG from the leading bytes; null for anything else. */
    public static function detectMime(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, '%PDF-')             => 'application/pdf',
            str_starts_with($bytes, "\xFF\xD8\xFF")      => 'image/jpeg',
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n") => 'image/png',
            default                                      => null,
        };
    }

    /**
     * Allowed mime types (for validation hint in views).
     */
    public static function allowedMimes(): array
    {
        return array_keys(self::ALLOWED_MIME_TYPES);
    }

    public static function maxSizeMb(): int
    {
        return intdiv(self::MAX_SIZE_BYTES, 1024 * 1024);
    }
}
