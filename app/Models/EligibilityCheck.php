<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EligibilityCheck extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_insurance_policy_id',
        'checked_by',
        'status',
        'response_notes',
        'source',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(PatientInsurancePolicy::class, 'patient_insurance_policy_id');
    }
}
