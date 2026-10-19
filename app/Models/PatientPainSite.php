<?php

namespace App\Models;

use App\Enums\PainRegion;
use App\Enums\PainSiteSource;
use App\Enums\PainTrend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One reported pain site. Written by App\Services\Chart\PainSiteService only;
 * never deleted, resolved instead.
 */
class PatientPainSite extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'patient_pain_sites';

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'triage_record_id',
        'appointment_id',
        'facility_id',
        'region',
        'severity',
        'onset_at',
        'note',
        'trend',
        'recorded_by_user_id',
        'source',
        'resolved_at',
        'resolved_by_user_id',
        'is_demo',
    ];

    protected $casts = [
        'region'      => PainRegion::class,
        'source'      => PainSiteSource::class,
        'trend'       => PainTrend::class,
        'severity'    => 'integer',
        'onset_at'    => 'datetime',
        'resolved_at' => 'datetime',
        'is_demo'     => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
