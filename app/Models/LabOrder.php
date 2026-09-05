<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A laboratory order — the test a clinician asked for, for a named patient, at
 * a named facility, at a stated moment. A clinical event, and clinical events
 * are immutable here.
 *
 * It is never rewritten and never deleted. An order raised in error is
 * CANCELLED; a panel released in error is VOIDED or marked entered-in-error.
 * Neither is a hard delete, because deleting the order takes its results with
 * it — `lab_results.lab_order_id` is ON DELETE CASCADE — and a patient's
 * released numbers are not ours to destroy.
 *
 * The guards live in the model rather than in a service so that no caller —
 * portal, USSD, governance export, PDF service, mobile labs API, results
 * inbox, console or a future one — can route around them.
 *
 * NOT AFFECTED (deliberately)
 * ---------------------------
 *   - Postgres CASCADE from `patients` / `facilities`. AccountDeletionService::
 *     purgeExpiredRetention() ends a retention period with `$patient->delete()`
 *     and the FK cascade removes the labs at the database level without ever
 *     constructing a model. That is the ONE sanctioned erasure and it stays legal.
 *   - `updateQuietly()`. DemoLabResultsSeeder re-runs against its own
 *     `demo_seed_key` rows and fires no events on purpose. Quiet writes stay quiet.
 */
class LabOrder extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    /**
     * Fixed at the moment of ordering: for whom, where, on what encounter, by
     * whom, when — and WHAT WAS ORDERED. `test_name` / `test_code` are in this
     * list on purpose: relabelling an order after the fact retroactively changes
     * what every result hanging off it is a result OF, which is a silent rewrite
     * of the record dressed up as a typo fix.
     */
    public const IMMUTABLE_ATTRIBUTES = [
        'patient_id',
        'facility_id',
        'visit_id',
        'ordered_by',
        'ordered_at',
        'test_name',
        'test_code',
    ];

    /**
     * Dead ends. An order that was cancelled, voided or marked entered-in-error
     * admits no further transition — reopening one would silently un-cancel a
     * test a clinician stopped, or resurrect a panel the laboratory retracted.
     */
    public const TERMINAL_STATUSES = [
        'cancelled',
        'canceled',
        'voided',
        'entered_in_error',
        'rejected',
    ];

    /**
     * The order's results are out and in the patient's record. The vocabulary is
     * the same one PresentsLabResults::presentStatus() folds into "completed" —
     * the guard must recognise every spelling the readers do, or a rename slips
     * past it.
     */
    public const RELEASED_STATUSES = [
        'resulted',
        'completed',
        'final',
        'released',
        'verified',
    ];

    /**
     * Where a released order may still go. Retraction only: a panel that was
     * released can be taken back, and that fact is recorded — it cannot be
     * quietly returned to "pending" as though it had never been released.
     */
    public const RETRACTION_STATUSES = [
        'voided',
        'entered_in_error',
    ];

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'ordered_by',
        'test_name',
        'test_code',
        'urgency',
        'status',
        'clinical_indication',
        'notes',
        'ordered_at',
        'collected_at',
        'resulted_at',
    ];

    protected $casts = [
        'ordered_at'   => 'datetime',
        'collected_at' => 'datetime',
        'resulted_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $order): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($order->isDirty($attribute)) {
                    throw new \LogicException(
                        "Lab order is immutable: '{$attribute}' cannot be changed after it is placed. "
                        . 'Cancel this order and place a new one instead.'
                    );
                }
            }

            if (! $order->isDirty('status')) {
                return;
            }

            $previous = self::normaliseStatus($order->getOriginal('status'));
            $next     = self::normaliseStatus($order->status);

            if (in_array($previous, self::TERMINAL_STATUSES, true)) {
                throw new \LogicException(
                    "Lab order is already '{$previous}' and cannot transition to '{$next}'. "
                    . 'Place a new order instead.'
                );
            }

            // A released panel may be retracted, never un-released.
            if (in_array($previous, self::RELEASED_STATUSES, true)
                && ! in_array($next, self::RELEASED_STATUSES, true)
                && ! in_array($next, self::RETRACTION_STATUSES, true)
            ) {
                throw new \LogicException(
                    "Lab order is already '{$previous}' and cannot be walked back to '{$next}'. "
                    . 'Void it or mark it entered-in-error instead.'
                );
            }
        });

        static::deleting(function (self $order): void {
            throw new \LogicException(
                'A lab order is an immutable clinical event and cannot be deleted — '
                . 'deleting it would cascade away its released results. '
                . 'Cancel it, or void it if results were already released, instead.'
            );
        });
    }

    /**
     * Hard-deleting a clinical event is never legitimate. Overridden as well as
     * hooked so the refusal is identical whether the caller uses delete() or
     * trips the model event.
     */
    public function delete(): bool
    {
        throw new \LogicException(
            'A lab order is an immutable clinical event and cannot be deleted — '
            . 'deleting it would cascade away its released results. '
            . 'Cancel it, or void it if results were already released, instead.'
        );
    }

    /** Statuses are stored as free text; every comparison folds the same way. */
    private static function normaliseStatus(mixed $status): string
    {
        return strtolower(trim((string) $status));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class, 'lab_order_id');
    }

    public function isResulted(): bool
    {
        return $this->status === 'resulted';
    }

    /** Has this order reached a state that admits no further transition? */
    public function isTerminal(): bool
    {
        return in_array(self::normaliseStatus($this->status), self::TERMINAL_STATUSES, true);
    }

    /** Are its results in the patient's record? */
    public function isReleased(): bool
    {
        return in_array(self::normaliseStatus($this->status), self::RELEASED_STATUSES, true);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'resulted'   => 'success',
            'processing' => 'info',
            'collected'  => 'warning',
            'cancelled'  => 'default',
            default      => 'warning',
        };
    }
}
