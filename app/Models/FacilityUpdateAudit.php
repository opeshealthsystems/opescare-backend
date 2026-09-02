<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FacilityUpdateAudit extends Model
{
    use HasUuids;

    protected $table = 'facility_update_audits';

    public $timestamps = false; // Custom schema handles created_at only

    protected $fillable = [
        'facility_id',
        'actor_id',
        'actor_type',
        'field_changed',
        'old_value',
        'new_value',
        'source',
        'requires_review',
        'approved_by',
        'approved_at',
        'created_at',
    ];

    protected $casts = [
        'requires_review' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
