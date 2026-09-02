<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IntegrationCertificationRun — Connect Suite / Developer Portal
 *
 * A single execution of an integration certification test suite for a
 * developer app. An app must pass certification before being approved for
 * production access.
 */
class IntegrationCertificationRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'integration_certification_id',
        'developer_app_id',
        'status',             // pending|running|passed|failed
        'tests_total',
        'tests_passed',
        'tests_failed',
        'results',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'tests_total'  => 'integer',
        'tests_passed' => 'integer',
        'tests_failed' => 'integer',
        'results'      => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function developerApp(): BelongsTo
    {
        return $this->belongsTo(DeveloperApp::class);
    }

    public function hasPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function passRate(): float
    {
        if ($this->tests_total === 0) {
            return 0.0;
        }
        return round(($this->tests_passed / $this->tests_total) * 100, 1);
    }

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }
}
