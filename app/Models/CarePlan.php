<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarePlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'created_by',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'visit_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CarePlanGoal::class);
    }

    public function interventions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CarePlanIntervention::class);
    }
}
