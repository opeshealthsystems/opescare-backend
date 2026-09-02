<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReportSubmission extends Model
{
    use HasUuids;

    protected $table = 'public_health_report_submissions';

    protected $fillable = [
        'report_id',
        'submission_profile_id',
        'submission_method',
        'payload_hash',
        'status',
        'external_reference',
        'response_code',
        'safe_response_summary',
        'submitted_by',
        'submitted_at',
        'accepted_at',
        'rejected_at',
        'retry_count'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'response_code' => 'integer',
        'retry_count' => 'integer'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function profile()
    {
        return $this->belongsTo(SubmissionProfile::class, 'submission_profile_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
