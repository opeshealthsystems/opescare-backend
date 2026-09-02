<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarePlanGoal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'care_plan_id',
        'goal_text',
        'target_date',
        'status',
        'achieved_at',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
        'achieved_at' => 'datetime',
    ];

    public function carePlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }
}
