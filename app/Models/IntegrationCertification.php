<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * IntegrationCertification — master record for an integration certification process.
 *
 * @property string $id
 * @property string $integration_name
 * @property string $integration_type  his|lis|erp|mobile|sdk|bridge|pharmacy|insurance
 * @property string $status  in_progress|passed|failed|expired|revoked
 * @property string|null $certification_level  bronze|silver|gold|platinum
 * @property \Carbon\Carbon|null $certified_at
 * @property \Carbon\Carbon|null $expires_at
 */
class IntegrationCertification extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'integration_name',
        'integration_type',
        'vendor_name',
        'vendor_contact',
        'version',
        'integration_client_id',
        'facility_id',
        'status',
        'certification_level',
        'scope_description',
        'submitted_at',
        'certified_at',
        'expires_at',
        'certified_by',
        'certification_notes',
        'created_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'certified_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function testRuns(): HasMany
    {
        return $this->hasMany(CertificationTestRun::class);
    }

    public function latestTestRun(): HasOne
    {
        return $this->hasOne(CertificationTestRun::class)->latestOfMany('started_at');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(CertificationBadge::class)->whereNull('revoked_at');
    }

    // ── Status Helpers ────────────────────────────────────────────────────────

    public function isPassed(): bool { return $this->status === 'passed'; }
    public function isFailed(): bool { return $this->status === 'failed'; }
    public function isInProgress(): bool { return $this->status === 'in_progress'; }
    public function isExpired(): bool { return $this->status === 'expired'; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'passed'      => 'badge--success',
            'failed'      => 'badge--danger',
            'expired'     => 'badge--warning',
            'revoked'     => 'badge--danger',
            default       => 'badge--info',
        };
    }

    public function levelBadgeClass(): string
    {
        return match ($this->certification_level) {
            'platinum' => 'badge--success',
            'gold'     => 'badge--warning',
            'silver'   => 'badge--info',
            default    => 'badge--outline',
        };
    }

    public function isExpiringSoon(): bool
    {
        return $this->expires_at && $this->expires_at->isBefore(now()->addDays(30));
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['in_progress', 'passed']);
    }
}
