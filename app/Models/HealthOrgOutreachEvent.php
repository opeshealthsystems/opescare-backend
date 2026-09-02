<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthOrgOutreachEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_id', 'facility_id', 'title', 'location', 'scheduled_at',
        'status', 'target_population', 'people_reached', 'notes', 'created_by',
    ];

    protected $casts = [
        'scheduled_at'   => 'datetime',
        'people_reached' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(HealthOrgProgram::class, 'program_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
