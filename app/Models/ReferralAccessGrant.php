<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralAccessGrant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'referral_case_id',
        'patient_id',
        'granted_to_facility_id',
        'granted_by_id',
        'token',
        'allowed_scopes',
        'status',
        'expires_at',
        'first_accessed_at',
        'last_accessed_at',
        'access_count',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'expires_at' => 'datetime',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public function referralCase()
    {
        return $this->belongsTo(ReferralCase::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function grantedToFacility()
    {
        return $this->belongsTo(Facility::class, 'granted_to_facility_id');
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }
}
