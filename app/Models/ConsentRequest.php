<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentRequest extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'requesting_facility_id',
        'requesting_user_id',
        'purpose',
        'requested_scope',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'requested_scope' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestingFacility()
    {
        return $this->belongsTo(Facility::class, 'requesting_facility_id');
    }

    public function requestingUser()
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    /**
     * The ConsentGrant this request produced once approved (if any). A request
     * only records the ask; the grant is what actually authorizes facility
     * access and is therefore the target of a patient-initiated revoke.
     */
    public function grant()
    {
        return $this->hasOne(ConsentGrant::class, 'consent_request_id')->latestOfMany();
    }
}
