<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityGoLiveReadiness extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'facility_go_live_readiness';

    protected $fillable = [
        'facility_id',
        'checklist_json',
        'status',
        'can_go_live',
        'approved_by',
        'approved_at',
        'approval_note',
    ];

    protected $casts = [
        'checklist_json' => 'array',
        'can_go_live' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
