<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrossFacilityRecordRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'requesting_facility_id', 'source_facility_id',
        'requested_by', 'approved_by', 'purpose', 'record_types', 'status',
        'consent_obtained', 'approved_at', 'fulfilled_at', 'expires_at', 'rejection_reason',
    ];

    protected $casts = [
        'record_types'     => 'array',
        'consent_obtained' => 'boolean',
        'approved_at'      => 'datetime',
        'fulfilled_at'     => 'datetime',
        'expires_at'       => 'datetime',
    ];

    public function patient()            { return $this->belongsTo(Patient::class); }
    public function requestingFacility() { return $this->belongsTo(Facility::class, 'requesting_facility_id'); }
    public function sourceFacility()     { return $this->belongsTo(Facility::class, 'source_facility_id'); }
    public function requestedBy()        { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy()         { return $this->belongsTo(User::class, 'approved_by'); }
}
