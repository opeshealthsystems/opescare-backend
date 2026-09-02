<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GoLiveChecklistItem — Module 13 (Go-Live Readiness)
 *
 * Individual line item in a facility go-live checklist.
 * Categories: clinical|billing|admin|compliance|technical|staffing
 */
class GoLiveChecklistItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'go_live_checklist_id',
        'category',             // clinical|billing|admin|compliance|technical|staffing
        'item_key',
        'item_label',
        'is_required',
        'is_completed',
        'completion_evidence',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'is_required'  => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(GoLiveChecklist::class, 'go_live_checklist_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function complete(string $completedBy, ?string $evidence = null): void
    {
        $this->update([
            'is_completed'         => true,
            'completed_by'         => $completedBy,
            'completed_at'         => now(),
            'completion_evidence'  => $evidence,
        ]);

        // Refresh checklist completion count
        $checklist = $this->checklist;
        if ($checklist) {
            $done = $checklist->items()->where('is_completed', true)->count();
            $checklist->update(['completed_items' => $done]);
        }
    }
}
