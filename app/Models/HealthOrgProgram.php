<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthOrgProgram extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'description', 'program_type', 'status',
        'start_date', 'end_date', 'target_population', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function outreachEvents(): HasMany
    {
        return $this->hasMany(HealthOrgOutreachEvent::class, 'program_id');
    }
}
