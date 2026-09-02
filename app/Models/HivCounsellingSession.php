<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HivCounsellingSession extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'counsellor_id',
        'session_type',
        'session_date',
        'test_result',
        'cd4_count',
        'viral_load',
        'on_art',
        'art_regimen',
        'risk_factors',
        'counselling_notes',
        'follow_up_date',
        'consent_obtained',
    ];

    protected $casts = [
        'session_date'    => 'date',
        'follow_up_date'  => 'date',
        'on_art'          => 'boolean',
        'consent_obtained' => 'boolean',
        'risk_factors'    => 'array',
        'cd4_count'       => 'integer',
        'viral_load'      => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }
}
