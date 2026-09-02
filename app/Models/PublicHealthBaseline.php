<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHealthBaseline extends Model
{
    protected $table = 'public_health_baselines';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'indicator_code',
        'period_type',
        'baseline_value',
        'calculated_at',
        'metadata_json'
    ];

    protected $casts = [
        'baseline_value' => 'decimal:2',
        'calculated_at' => 'datetime',
        'metadata_json' => 'array'
    ];
}
