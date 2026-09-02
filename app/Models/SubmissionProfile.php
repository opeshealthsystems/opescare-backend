<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubmissionProfile extends Model
{
    use HasUuids;

    protected $table = 'public_health_submission_profiles';

    protected $fillable = [
        'name',
        'report_type_id',
        'destination_type',
        'endpoint_url',
        'auth_method',
        'payload_format',
        'mapping_rules_json',
        'active'
    ];

    protected $casts = [
        'mapping_rules_json' => 'array',
        'active' => 'boolean'
    ];

    public function reportType()
    {
        return $this->belongsTo(ReportType::class, 'report_type_id');
    }
}
