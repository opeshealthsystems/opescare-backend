<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStatusHistory extends Model
{
    protected $table = 'public_health_report_status_history';

    public $timestamps = false; // Manually tracking changed_at timestamp

    protected $fillable = [
        'report_id',
        'old_status',
        'new_status',
        'changed_by',
        'reason',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
