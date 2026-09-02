<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A postnatal care visit.
 *
 * Mirrors AntenatalVisit deliberately, including HasFacilityScope — postnatal
 * records are patient-identifiable clinical data and must be facility-isolated
 * exactly like their antenatal counterparts.
 */
class PostnatalVisit extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id', 'facility_id', 'provider_id',
        'visit_date', 'days_postpartum',
        'bp_systolic', 'bp_diastolic', 'weight_kg',
        'lochia', 'wound_healing', 'breastfeeding_status',
        'infant_weight_grams', 'notes', 'next_visit_plan',
    ];

    protected $casts = [
        'visit_date'          => 'date',
        'days_postpartum'     => 'integer',
        'bp_systolic'         => 'integer',
        'bp_diastolic'        => 'integer',
        'weight_kg'           => 'decimal:2',
        'infant_weight_grams' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
