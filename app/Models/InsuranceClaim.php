<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    use HasUuids, HasFactory;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_insurance_policy_id',
        'invoice_id',
        'preauthorization_request_id',
        'facility_id',
        'claim_number',
        'status',
        'claimed_amount',
        'approved_amount',
        'paid_amount',
        'submitted_by',
        'submitted_at',
        'decided_at',
        'submission_notes',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(PatientInsurancePolicy::class, 'patient_insurance_policy_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function preauthorization()
    {
        return $this->belongsTo(PreauthorizationRequest::class, 'preauthorization_request_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function items()
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function documents()
    {
        return $this->hasMany(ClaimDocument::class);
    }

    public function decisions()
    {
        return $this->hasMany(ClaimDecision::class);
    }

    public function latestDecision()
    {
        return $this->hasOne(ClaimDecision::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(ClaimPayment::class);
    }

    public function messages()
    {
        return $this->hasMany(ClaimMessage::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'more_information_required']);
    }

    public function canReceiveDecision(): bool
    {
        return in_array($this->status, ['submitted', 'under_review', 'more_information_required']);
    }

    public function canReceivePayment(): bool
    {
        return in_array($this->status, ['approved', 'partially_approved', 'partially_paid']);
    }

    /** Generate a unique claim number */
    public static function generateClaimNumber(): string
    {
        do {
            $number = 'CLM-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Y');
        } while (self::where('claim_number', $number)->exists());

        return $number;
    }
}
