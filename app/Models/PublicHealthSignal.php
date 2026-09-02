<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublicHealthSignal extends Model
{
    use HasUuids;

    protected $table = 'public_health_signals';

    protected $fillable = [
        'signal_type',
        'status',
        'scope_type',
        'scope_id',
        'facility_id',
        'district_id',
        'region_id',
        'condition_code',
        'indicator_code',
        'baseline_value',
        'current_value',
        'increase_percentage',
        'confidence_level',
        'severity',
        'detected_at',
        'reviewed_at',
        'resolved_at',
        'metadata_json'
    ];

    protected $casts = [
        'baseline_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'increase_percentage' => 'decimal:2',
        'detected_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata_json' => 'array'
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function reviews()
    {
        return $this->hasMany(SignalReview::class, 'signal_id');
    }

    public function alerts()
    {
        return $this->hasMany(SignalAlert::class, 'signal_id');
    }
}
