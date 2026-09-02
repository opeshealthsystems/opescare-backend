<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AntenatalVisit extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'pregnancy_record_id', 'patient_id', 'facility_id', 'provider_id',
        'visit_date', 'gestational_age_weeks', 'gestational_age_days',
        'fundal_height_cm', 'fetal_heart_rate', 'presentation',
        'weight_kg', 'bp_systolic', 'bp_diastolic',
        'urine_protein', 'urine_glucose', 'oedema', 'notes',
    ];

    protected $casts = [
        'visit_date'            => 'date',
        'gestational_age_weeks' => 'integer',
        'gestational_age_days'  => 'integer',
        'fundal_height_cm'      => 'decimal:2',
        'fetal_heart_rate'      => 'integer',
        'weight_kg'             => 'decimal:2',
        'bp_systolic'           => 'integer',
        'bp_diastolic'          => 'integer',
    ];

    public function pregnancyRecord(): BelongsTo
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
