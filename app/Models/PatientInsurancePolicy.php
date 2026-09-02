<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientInsurancePolicy extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_plan_id',
        'policy_number',
        'member_id',
        'group_number',
        'relationship_to_primary',
        'primary_member_name',
        'effective_date',
        'expiry_date',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function eligibilityChecks()
    {
        return $this->hasMany(EligibilityCheck::class);
    }

    public function latestEligibility()
    {
        return $this->hasOne(EligibilityCheck::class)->latestOfMany();
    }

    public function preauthorizations()
    {
        return $this->hasMany(PreauthorizationRequest::class);
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
