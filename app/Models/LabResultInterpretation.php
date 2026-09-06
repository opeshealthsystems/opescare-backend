<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A clinician's statement about a laboratory result, written at the moment
 * they acknowledged it in the results inbox.
 *
 * Two audiences, two columns, never mixed:
 *
 *   - `patient_note`       plain language, FOR the patient — but only readable
 *                          by them once `shared_at` is set.
 *   - `clinician_comment`  private clinical commentary. No patient-facing
 *                          reader may ever select it.
 *
 * APPEND-ONLY. This is a clinical statement, and clinical statements on this
 * platform are immutable: a correction is a NEW row (the newest row wins on
 * every read), and nothing is ever deleted through the model. The guards live
 * here rather than in a service so that no caller can route around them.
 *
 * The acknowledgement it belongs to is the `notification_events` ledger row
 * MobileResultsInboxController writes; `acknowledgement_event_id` carries that
 * row's `uuid`. The relations to LabOrder / Patient / User are declared on
 * THIS model only — LabOrder is not this file's to edit.
 */
class LabResultInterpretation extends Model
{
    use HasUuids;

    /** The next steps the inbox offers after an acknowledgement. */
    public const NEXT_ACTIONS = ['none', 'prescribe', 'follow_up', 'message', 'repeat_test'];

    protected $fillable = [
        'lab_order_id',
        'patient_id',
        'facility_id',
        'author_user_id',
        'patient_note',
        'clinician_comment',
        'next_action',
        'acknowledgement_event_id',
        'shared_at',
        'is_demo',
    ];

    protected $casts = [
        'shared_at' => 'datetime',
        'is_demo'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException(
                'A lab result interpretation is append-only: write a new row instead of changing this one.'
            );
        });

        static::deleting(function (): void {
            throw new \LogicException(
                'A lab result interpretation is a clinical statement and cannot be deleted.'
            );
        });
    }

    /** Same refusal whether the caller uses delete() or trips the model event. */
    public function delete(): bool
    {
        throw new \LogicException(
            'A lab result interpretation is a clinical statement and cannot be deleted.'
        );
    }

    /** Rows the patient may read: shared, and carrying a note to read. */
    public function scopeSharedWithPatient(Builder $query): Builder
    {
        return $query->whereNotNull('shared_at')->whereNotNull('patient_note');
    }

    public function isShared(): bool
    {
        return $this->shared_at !== null;
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
