<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityCheck extends Model
{
    protected $table = 'public_health_data_quality_checks';

    protected $fillable = [
        'report_id',
        'check_code',
        'check_name',
        'status',
        'severity',
        'message',
        'field_reference'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }
}
