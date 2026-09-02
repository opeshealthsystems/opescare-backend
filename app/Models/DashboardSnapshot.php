<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardSnapshot extends Model
{
    protected $table = 'public_health_dashboard_snapshots';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'period_start',
        'period_end',
        'metrics_json'
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metrics_json' => 'array'
    ];
}
