<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    protected $table = 'public_health_report_items';

    protected $fillable = [
        'report_id',
        'indicator_code',
        'indicator_name',
        'value',
        'age_group',
        'sex',
        'disease_code',
        'metadata_json'
    ];

    protected $casts = [
        'value' => 'integer',
        'metadata_json' => 'array'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }
}
