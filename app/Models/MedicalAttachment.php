<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group A).
 *
 * Finder additions: an attachment is never deleted — it is VOIDED (status,
 * reason, who, when) and its bytes stay; it reaches the patient only after a
 * RELEASE. Categories are translated in lang/{en,fr}/files.php.
 */
class MedicalAttachment extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_VOIDED = 'voided';

    public const CATEGORY_RADIOLOGY_REPORT = 'radiology_report';

    protected $fillable = [
        'file_asset_id', 'resource_type', 'resource_id',
        'category', 'description', 'attached_by',
        'status', 'void_reason', 'voided_by', 'voided_at',
        'released_at', 'released_by', 'radiology_report_id',
    ];

    protected $casts = [
        'voided_at'   => 'datetime',
        'released_at' => 'datetime',
    ];

    public function fileAsset()
    {
        return $this->belongsTo(FileAsset::class);
    }

    public function radiologyReport()
    {
        return $this->belongsTo(RadiologyReport::class);
    }

    /**
     * Polymorphic-style scope: get all attachments for a resource.
     */
    public static function forResource(string $type, string $id)
    {
        return static::with('fileAsset')
            ->where('resource_type', $type)
            ->where('resource_id', $id)
            ->latest()
            ->get();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** Released to the patient and not voided — what the patient portal lists. */
    public function scopeReleasedToPatient(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->whereNotNull('released_at');
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    public function isRadiologyReport(): bool
    {
        return $this->category === self::CATEGORY_RADIOLOGY_REPORT;
    }

    /**
     * Attachment categories available for selection.
     */
    public static function categories(): array
    {
        return [
            'lab_result'       => __('files.categories.lab_result'),
            'imaging'          => __('files.categories.imaging'),
            'radiology_report' => __('files.categories.radiology_report'),
            'consent'          => __('files.categories.consent'),
            'prescription'     => __('files.categories.prescription'),
            'referral'         => __('files.categories.referral'),
            'report'           => __('files.categories.report'),
            'id_document'      => __('files.categories.id_document'),
            'other'            => __('files.categories.other'),
        ];
    }
}
