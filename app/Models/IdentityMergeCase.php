<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Inherited from the platform's Connect-side merge desk, which
 *   this fork does not carry. Nothing here writes or reads it. The Finder's
 *   identity pipeline is MpiCandidate -> PatientMergeService -> PatientMerge.
 *   Kept only so a shared migration keeps applying; do not build on it.
 */
class IdentityMergeCase extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'primary_patient_id',
        'secondary_patient_id',
        'status',
        'match_score',
        'reviewed_by',
        'review_reason',
    ];

    public function primaryPatient()
    {
        return $this->belongsTo(Patient::class, 'primary_patient_id');
    }

    public function secondaryPatient()
    {
        return $this->belongsTo(Patient::class, 'secondary_patient_id');
    }
}
