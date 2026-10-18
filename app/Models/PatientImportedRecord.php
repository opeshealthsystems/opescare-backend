<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item a patient imported from another provider.
 *
 * Always provenance 'patient_import' and verification 'unverified': the
 * columns have defaults and the model refuses to save anything else, so no
 * caller can promote a patient-provided item to clinician-verified.
 */
class PatientImportedRecord extends Model
{
    use HasUuids;

    public const PROVENANCE = 'patient_import';
    public const UNVERIFIED = 'unverified';
    public const DOCUMENT   = 'Document';

    protected $guarded = ['id'];

    protected $casts = [
        'payload'      => 'encrypted:array',
        'effective_at' => 'datetime',
        'file_size'    => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $record) {
            $record->provenance          = self::PROVENANCE;
            $record->verification_status = self::UNVERIFIED;
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
