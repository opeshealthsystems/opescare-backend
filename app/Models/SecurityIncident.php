<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'incident_type',
        'severity',
        'status',
        'summary',
        'detected_at',
        'contained_at',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'contained_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
