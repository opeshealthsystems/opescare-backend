<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisitTimeline — Module 41 (End-to-End Visit Flow)
 *
 * Immutable audit trail of events that occurred during a patient visit.
 * Records every significant transition (step started, step completed,
 * status changed, document attached, note added) with actor and context.
 *
 * This is append-only — never update or delete timeline entries.
 */
class VisitTimeline extends Model
{
    use HasUuids;

    /**
     * Canonical event types.
     */
    public const EVENT_TYPES = [
        'visit_started',
        'step_started',
        'step_completed',
        'step_skipped',
        'note_added',
        'status_changed',
        'document_attached',
        'prescription_created',
        'lab_ordered',
        'invoice_created',
        'payment_received',
        'visit_completed',
        'visit_cancelled',
    ];

    protected $fillable = [
        'visit_id',
        'visit_step_id',
        'event_type',
        'actor_id',
        'actor_type',
        'metadata',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'occurred_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function visitStep(): BelongsTo
    {
        return $this->belongsTo(VisitStep::class);
    }

    // ── Factory helper ────────────────────────────────────────────────────────

    /**
     * Record a new timeline event. Use this instead of create() directly
     * to ensure occurred_at is always set.
     */
    public static function record(
        string $visitId,
        string $eventType,
        string $actorId,
        string $actorType = 'user',
        ?string $description = null,
        ?array $metadata = null,
        ?string $visitStepId = null
    ): self {
        return static::create([
            'visit_id'      => $visitId,
            'visit_step_id' => $visitStepId,
            'event_type'    => $eventType,
            'actor_id'      => $actorId,
            'actor_type'    => $actorType,
            'description'   => $description,
            'metadata'      => $metadata,
            'occurred_at'   => now(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isClinicalEvent(): bool
    {
        return in_array($this->event_type, [
            'step_started',
            'step_completed',
            'note_added',
            'prescription_created',
            'lab_ordered',
        ]);
    }

    public function isFinancialEvent(): bool
    {
        return in_array($this->event_type, [
            'invoice_created',
            'payment_received',
        ]);
    }
}
