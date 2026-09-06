<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A patient's request for a laboratory test.
 *
 * A request is a clinical event — it records what a patient asked their care
 * team for, and when — so it follows the same rules as App\Models\Prescription:
 * it is never rewritten and never deleted. A change of mind is a transition to
 * `cancelled`; a clinician's decision is `approved` / `declined` with a reason.
 * A mistaken request stays on the record as a cancelled one, because "the
 * patient never asked" and "the patient asked and withdrew it" are different
 * facts and the second must not be able to impersonate the first.
 *
 * The guards live in the model rather than in a service so that no caller —
 * mobile API, portal, console or a future one — can route around them.
 *
 * This is NOT a lab order. It carries no clinical authority: `lab_order_id`
 * stays null until a clinician raises a real App\Models\LabOrder from it.
 */
class LabTestRequest extends Model
{
    use HasUuids;

    /**
     * Fixed at the moment of asking. Changing any of them would silently
     * rewrite what was requested, by whom, or when — which is the whole point
     * of keeping the record.
     */
    public const IMMUTABLE_ATTRIBUTES = [
        'patient_id',
        'test_code',
        'test_name',
        'loinc_code',
        'priority',
        'clinical_notes',
        'submitted_at',
    ];

    /** Statuses from which no further transition is allowed. */
    public const TERMINAL_STATUSES = [
        'approved',
        'declined',
        'cancelled',
    ];

    /** The full status vocabulary, mirrored by LabTestRequestStatus in the app. */
    public const STATUSES = [
        'submitted',
        'approved',
        'declined',
        'cancelled',
    ];

    /** How urgently the patient is asking. Advisory — a clinician sets the order's real urgency. */
    public const PRIORITIES = ['routine', 'urgent', 'stat'];

    protected $fillable = [
        'patient_id',
        'facility_id',
        'test_code',
        'test_name',
        'loinc_code',
        'priority',
        'status',
        'clinical_notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'lab_order_id',
        'is_demo',
        'demo_seed_key',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'is_demo'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if ($request->submitted_at === null) {
                $request->submitted_at = now();
            }

            if (blank($request->status)) {
                $request->status = 'submitted';
            }
        });

        static::updating(function (self $request): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($request->isDirty($attribute)) {
                    throw new \LogicException(
                        "Lab test request is immutable: '{$attribute}' cannot be changed after it is submitted. "
                        . 'Cancel this request and submit a new one instead.'
                    );
                }
            }

            // A decided request may not be walked back into an open one — that
            // would silently un-decline a test a clinician refused, or reopen a
            // request the patient withdrew.
            if ($request->isDirty('status')) {
                $previous = (string) $request->getOriginal('status');
                if (in_array($previous, self::TERMINAL_STATUSES, true)) {
                    throw new \LogicException(
                        "Lab test request is already '{$previous}' and cannot transition to "
                        . "'{$request->status}'. Submit a new request instead."
                    );
                }
            }
        });

        static::deleting(function (self $request): void {
            throw new \LogicException(
                'A lab test request is an immutable clinical event and cannot be deleted. '
                . 'Cancel it instead.'
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
            'A lab test request is an immutable clinical event and cannot be deleted. '
            . 'Cancel it instead.'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** The order a clinician raised from this request, once they have. */
    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    /** The professional who approved or declined it. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isDecided(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
