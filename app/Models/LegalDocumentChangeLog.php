<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LegalDocumentChangeLog — Legal & Compliance
 *
 * Append-only audit trail for every change to a LegalDocument.
 * Required for regulatory evidence that legal documents were managed properly.
 */
class LegalDocumentChangeLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'legal_document_id',
        'legal_document_version_id',
        'change_type',       // created|updated|published|archived|translated
        'changed_by',
        'change_summary',
        'diff',
    ];

    protected $casts = ['diff' => 'array'];

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function legalDocumentVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class);
    }

    public static function record(string $docId, string $changeType, string $changedBy, array $extra = []): self
    {
        return static::create(array_merge([
            'legal_document_id' => $docId,
            'change_type'       => $changeType,
            'changed_by'        => $changedBy,
        ], $extra));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('LegalDocumentChangeLog records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('LegalDocumentChangeLog records are append-only.');
    }
}
