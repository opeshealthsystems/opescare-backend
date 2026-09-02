<?php

namespace App\Models;

use App\Enums\FacilityClaimStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FacilityClaim extends Model
{
    use HasUuids;

    protected $table = 'facility_claims';

    protected $fillable = [
        'facility_id',
        'care_facility_id',
        'registry_entry_id',
        'claimant_user_id',
        'claimant_name',
        'claimant_role',
        'claimant_email',
        'claimant_phone',
        'claim_status',
        'claim_reason',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'claim_status' => FacilityClaimStatus::class,
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** The operational tenant (facilities.id), when one exists. Often null. */
    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    /** The directory listing being claimed (care_facilities.id). */
    public function careFacility()
    {
        return $this->belongsTo(CareFacility::class, 'care_facility_id');
    }

    public function registryEntry()
    {
        return $this->belongsTo(FacilityRegistry::class, 'registry_entry_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Still in the moderation queue. */
    public function scopeOpen($query)
    {
        return $query->whereIn('claim_status', [
            FacilityClaimStatus::Submitted->value,
            FacilityClaimStatus::UnderReview->value,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->where('claim_status', FacilityClaimStatus::Approved->value);
    }
}
