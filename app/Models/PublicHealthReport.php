<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublicHealthReport extends Model
{
    use HasUuids;

    protected $table = 'public_health_reports';

    protected $fillable = [
        'report_type_id',
        'facility_id',
        'district_id',
        'region_id',
        'reporting_period_start',
        'reporting_period_end',
        'status',
        'sensitivity_level',
        'data_classification',
        'generated_by_system',
        'data_quality_score',
        'requires_review',
        'requires_correction',
        'payload_json',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'reporting_period_start' => 'datetime',
        'reporting_period_end' => 'datetime',
        'generated_by_system' => 'boolean',
        'data_quality_score' => 'integer',
        'requires_review' => 'boolean',
        'requires_correction' => 'boolean',
        'payload_json' => 'array'
    ];

    public function reportType()
    {
        return $this->belongsTo(ReportType::class, 'report_type_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function items()
    {
        return $this->hasMany(ReportItem::class, 'report_id');
    }

    public function qualityChecks()
    {
        return $this->hasMany(DataQualityCheck::class, 'report_id');
    }

    public function reviews()
    {
        return $this->hasMany(ReportReview::class, 'report_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(ReportStatusHistory::class, 'report_id');
    }

    public function versions()
    {
        return $this->hasMany(ReportVersion::class, 'report_id');
    }

    public function submissions()
    {
        return $this->hasMany(ReportSubmission::class, 'report_id');
    }
}
