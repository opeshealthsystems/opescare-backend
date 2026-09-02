<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttachmentAccessLog — File Storage & Medical Attachments (Module 39)
 *
 * Append-only audit log for every access event on a FileAsset
 * (view, download, preview, share).
 *
 * Security constraints:
 * - Records must NEVER be updated or deleted after creation.
 * - Every file download/view MUST create a record here.
 * - Used for GDPR access-request responses and security investigations.
 */
class AttachmentAccessLog extends Model
{
    use HasUuids;

    /**
     * Guard against accidental mass-assignment to prevent tampering.
     */
    protected $fillable = [
        'file_asset_id',
        'accessed_by',
        'access_type',     // view|download|preview|share
        'ip_address',
        'user_agent',
        'access_reason',   // clinical|insurance|admin|audit
        'facility_id',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function fileAsset(): BelongsTo
    {
        return $this->belongsTo(FileAsset::class);
    }

    // ── Append-only factory ────────────────────────────────────────────────────

    /**
     * Record an access event.  This is the ONLY way to create a log entry —
     * never call new AttachmentAccessLog() directly.
     */
    public static function record(
        string $fileAssetId,
        string $accessedBy,
        string $accessType,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $accessReason = null,
        ?string $facilityId = null
    ): self {
        return static::create([
            'file_asset_id' => $fileAssetId,
            'accessed_by'   => $accessedBy,
            'access_type'   => $accessType,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent,
            'access_reason' => $accessReason,
            'facility_id'   => $facilityId,
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForAsset($query, string $fileAssetId)
    {
        return $query->where('file_asset_id', $fileAssetId);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('accessed_by', $userId);
    }

    public function scopeDownloads($query)
    {
        return $query->where('access_type', 'download');
    }

    // ── Prevent mutation ──────────────────────────────────────────────────────

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('AttachmentAccessLog records are append-only and must not be updated.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('AttachmentAccessLog records are append-only and must not be deleted.');
    }
}
