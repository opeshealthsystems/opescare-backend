<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttachmentAudit — File Storage & Medical Attachments
 *
 * Append-only audit log for all file asset lifecycle events.
 * Must NEVER be updated or deleted.
 */
class AttachmentAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id',
        'action',       // uploaded|classified|attached|downloaded|archived|deleted
        'performed_by',
        'ip_address',
        'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public function fileAsset(): BelongsTo
    {
        return $this->belongsTo(FileAsset::class);
    }

    public static function record(string $fileAssetId, string $action, array $extra = []): self
    {
        return static::create(array_merge(['file_asset_id' => $fileAssetId, 'action' => $action], $extra));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('AttachmentAudit records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('AttachmentAudit records are append-only.');
    }
}
