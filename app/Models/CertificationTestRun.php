<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CertificationTestRun — one execution of the certification test suite.
 *
 * @property string $id
 * @property string $integration_certification_id
 * @property string $status  pending|running|passed|failed|cancelled
 * @property int $total_requirements
 * @property int $passed_count
 * @property int $failed_count
 * @property int $skipped_count
 * @property array|null $results_json
 */
class CertificationTestRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'integration_certification_id',
        'run_label',
        'status',
        'total_requirements',
        'passed_count',
        'failed_count',
        'skipped_count',
        'results_json',
        'run_notes',
        'run_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'results_json' => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function certification(): BelongsTo
    {
        return $this->belongsTo(IntegrationCertification::class, 'integration_certification_id');
    }

    public function passRate(): float
    {
        if ($this->total_requirements === 0) {
            return 0.0;
        }
        return round(($this->passed_count / $this->total_requirements) * 100, 1);
    }

    public function isPassed(): bool { return $this->status === 'passed'; }
    public function isFailed(): bool { return $this->status === 'failed'; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'passed'    => 'badge--success',
            'failed'    => 'badge--danger',
            'running'   => 'badge--info',
            'cancelled' => 'badge--warning',
            default     => 'badge--outline',
        };
    }

    /**
     * Build a summary test run from a requirements list.
     * Used when manually recording results (not automated runner).
     */
    public static function createFromResults(
        string $certificationId,
        array $requirementResults,
        string $runBy,
        ?string $label = null,
    ): self {
        $passed  = collect($requirementResults)->where('result', 'passed')->count();
        $failed  = collect($requirementResults)->where('result', 'failed')->count();
        $skipped = collect($requirementResults)->where('result', 'skipped')->count();
        $total   = count($requirementResults);
        $status  = $failed === 0 ? 'passed' : 'failed';

        return self::create([
            'integration_certification_id' => $certificationId,
            'run_label'          => $label,
            'status'             => $status,
            'total_requirements' => $total,
            'passed_count'       => $passed,
            'failed_count'       => $failed,
            'skipped_count'      => $skipped,
            'results_json'       => $requirementResults,
            'run_by'             => $runBy,
            'started_at'         => now(),
            'completed_at'       => now(),
        ]);
    }
}
