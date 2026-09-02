<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReportingRule extends Model
{
    use HasUuids;

    protected $table = 'public_health_reporting_rules';

    protected $fillable = [
        'report_type_id',
        'trigger_source',
        'trigger_condition',
        'aggregation_level',
        'requires_review',
        'allows_auto_submit',
        'requires_patient_identity',
        'two_person_approval_required',
        'effective_from',
        'effective_to',
        'status'
    ];

    protected $casts = [
        'requires_review' => 'boolean',
        'allows_auto_submit' => 'boolean',
        'requires_patient_identity' => 'boolean',
        'two_person_approval_required' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime'
    ];

    public function reportType()
    {
        return $this->belongsTo(ReportType::class, 'report_type_id');
    }
}
