<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralCase extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'referring_facility_id',
        'referring_provider_id',
        'receiving_facility_id',
        'receiving_specialty',
        'receiving_provider_name',
        'urgency',
        'status',
        'reason',
        'clinical_summary',
        'included_record_types',
        'consent_grant_id',
        'expires_at',
        'accepted_at',
        'accepted_by_id',
        'rejected_at',
        'rejection_reason',
        'feedback',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by_id',
    ];

    protected $casts = [
        'included_record_types' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function referringFacility()
    {
        return $this->belongsTo(Facility::class, 'referring_facility_id');
    }

    public function receivingFacility()
    {
        return $this->belongsTo(Facility::class, 'receiving_facility_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function accessGrants()
    {
        return $this->hasMany(ReferralAccessGrant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['sent', 'accepted']) && !$this->isExpired();
    }
}
