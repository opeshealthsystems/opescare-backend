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
        'verification_code_hash',
        'verification_code_expires_at',
        'verification_attempts',
        'verification_send_count',
        'verification_sent_at',
        'phone_verified_at',
        'sms_status',
        'sms_phone_masked',
        'document_path',
        'document_mime',
        'document_original_name',
        'document_size',
        'created_facility_id',
        'activated_at',
    ];

    /** The code HMAC and the private file path never leave the server. */
    protected $hidden = ['verification_code_hash', 'document_path'];

    protected $casts = [
        'claim_status' => FacilityClaimStatus::class,
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'activated_at' => 'datetime',
        'verification_attempts' => 'integer',
        'verification_send_count' => 'integer',
        'document_size' => 'integer',
    ];

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasDocument(): bool
    {
        return $this->document_path !== null && $this->document_path !== '';
    }

    /** The tenant approval created (or joined) for this claim. */
    public function createdFacility()
    {
        return $this->belongsTo(Facility::class, 'created_facility_id');
    }

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
            FacilityClaimStatus::PendingVerification->value,
            FacilityClaimStatus::PendingReview->value,
            FacilityClaimStatus::Submitted->value,
            FacilityClaimStatus::UnderReview->value,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->where('claim_status', FacilityClaimStatus::Approved->value);
    }
}
