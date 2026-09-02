<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportVersion extends Model
{
    protected $table = 'public_health_report_versions';

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'version_number',
        'payload_json',
        'change_reason',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
