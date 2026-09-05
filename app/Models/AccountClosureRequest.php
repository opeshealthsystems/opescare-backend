<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to close and erase an OpesCare account.
 *
 * Shipped 2026-05-27 as an admin-reviewed workflow (`reviewed_by`,
 * `review_note`, approve/reject) and reachable only from the legal admin
 * portal. The 2026-09-05 migration added the columns that make it the record
 * behind a SELF-SERVICE, reversible, in-app deletion — the flow Apple 5.1.1(v)
 * and Google Play's account-deletion policy require of an app that creates
 * accounts. Both readers coexist: the admin listing still works, and nothing
 * in the patient flow writes `approved` or `rejected`.
 *
 * The row is the record of a promise. It stores the deadline the patient was
 * shown, the disclosure version they confirmed against, what erasure actually
 * did, and when the retained clinical record becomes eligible for destruction.
 * It is never deleted — a cancelled request is marked cancelled and kept, so
 * "I stopped it" and "you never asked" stay distinguishable.
 *
 * Lifecycle of a patient-initiated request:
 *
 *      pending  ──cancel──▶  cancelled          (patient changed their mind)
 *         │
 *      grace period elapses
 *         │
 *         ▼
 *      completed  ──retention elapses──▶  purged
 */
class AccountClosureRequest extends Model
{
    use HasUuids;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PURGED    = 'purged';

    /**
     * Bumped whenever the itemised "erased / de-identified / kept" disclosure
     * in lang/{en,fr}/account_deletion.php changes MEANING — not when a comma
     * moves. A stored request keeps the version it was confirmed against, so
     * an old request can always be read back against the text that was on
     * screen when the patient tapped confirm.
     */
    public const DISCLOSURE_VERSION = '1.0';

    protected $fillable = [
        'patient_id', 'reason', 'status',
        'data_delete_requested', 'data_export_requested',
        'reviewed_by', 'review_note', 'reviewed_at', 'completed_at',
        // Self-service grace-period flow (2026-09-05).
        'executes_after', 'grace_period_days',
        'cancelled_at', 'cancelled_reason',
        'executed_at', 'execution_summary',
        'retention_until', 'purged_at',
        'disclosure_version', 'reauth_method',
        'requested_via', 'requested_ip', 'requested_user_agent',
    ];

    protected $casts = [
        'data_delete_requested' => 'boolean',
        'data_export_requested' => 'boolean',
        'reviewed_at'           => 'datetime',
        'completed_at'          => 'datetime',
        'executes_after'        => 'datetime',
        'cancelled_at'          => 'datetime',
        'executed_at'           => 'datetime',
        'purged_at'             => 'datetime',
        'retention_until'       => 'date',
        'execution_summary'     => 'array',
        'grace_period_days'     => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * The grace period has run out — erasure is owed.
     *
     * Deliberately `<=` and not `<`: a request whose deadline passed while
     * nothing was sweeping is overdue, not skipped.
     */
    public function isDue(?CarbonInterface $now = null): bool
    {
        return $this->isPending()
            && $this->executes_after !== null
            && $this->executes_after->lessThanOrEqualTo($now ?? now());
    }

    /** Whole days left before erasure runs; 0 once the deadline has passed. */
    public function daysRemaining(?CarbonInterface $now = null): int
    {
        if (! $this->isPending() || $this->executes_after === null) {
            return 0;
        }

        $now = $now ?? now();

        return $this->executes_after->greaterThan($now)
            ? (int) ceil($now->diffInHours($this->executes_after) / 24)
            : 0;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'approved'  => 'info',
            'completed' => 'success',
            'rejected', 'cancelled' => 'danger',
            'purged'    => 'default',
            default     => 'default',
        };
    }
}
