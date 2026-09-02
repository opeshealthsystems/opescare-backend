<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PreauthorizationRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_insurance_policy_id',
        'invoice_id',
        'facility_id',
        'requested_by',
        'service_description',
        'clinical_justification',
        'estimated_amount',
        'status',
        'submitted_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(PatientInsurancePolicy::class, 'patient_insurance_policy_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function decisions()
    {
        return $this->hasMany(PreauthorizationDecision::class);
    }

    public function latestDecision()
    {
        return $this->hasOne(PreauthorizationDecision::class)->latestOfMany();
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'under_review', 'more_information_required']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
