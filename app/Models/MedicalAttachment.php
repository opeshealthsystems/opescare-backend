<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicalAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id', 'resource_type', 'resource_id',
        'category', 'description', 'attached_by',
    ];

    public function fileAsset()
    {
        return $this->belongsTo(FileAsset::class);
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

    /**
     * Attachment categories available for selection.
     */
    public static function categories(): array
    {
        return [
            'lab_result'   => 'Lab Result',
            'imaging'      => 'Imaging / Radiology',
            'consent'      => 'Consent Form',
            'prescription' => 'Prescription',
            'referral'     => 'Referral Letter',
            'report'       => 'Clinical Report',
            'id_document'  => 'ID Document',
            'other'        => 'Other',
        ];
    }
}
