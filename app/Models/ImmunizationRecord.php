<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImmunizationRecord extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'administered_by_id',
        'encounter_id',
        'vaccine_code',
        'vaccine_system',
        'vaccine_name',
        'lot_number',
        'manufacturer',
        'administered_at',
        'dose_number',
        'dose_sequence',
        'route',
        'site',
        'dose_quantity',
        'dose_unit',
        'expiry_date',
        'status',
        'not_done_reason',
        'verification_status',
        'is_historical',
        'source_document_id',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'expiry_date' => 'date',
        'dose_quantity' => 'decimal:2',
        'is_historical' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by_id');
    }

    public function adverseReactions()
    {
        return $this->hasMany(AdverseReactionNote::class, 'immunization_record_id');
    }
}
