<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarePlanIntervention extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'care_plan_id',
        'intervention_type',
        'description',
        'frequency',
        'responsible_party',
        'status',
    ];

    public function carePlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }
}
