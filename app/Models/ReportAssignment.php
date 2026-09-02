<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportAssignment extends Model
{
    protected $table = 'public_health_report_assignments';

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'assigned_to',
        'assigned_by',
        'assignment_status',
        'assigned_at',
        'completed_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
