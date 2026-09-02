<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockAudit — Module 17 (Inventory & Supply Chain)
 *
 * Records a formal stock count/audit event at a facility or location.
 * Discrepancies found during audits feed into StockAdjustment records.
 */
class StockAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'stock_location_id', 'audit_type', 'status',
        'initiated_by', 'scheduled_at', 'started_at', 'completed_at',
        'items_counted', 'discrepancies_found', 'notes',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function start(): void
    {
        $this->update(['status' => 'in_progress', 'started_at' => $this->started_at ?? now()]);
    }

    public function complete(int $itemsCounted, int $discrepancies, ?string $notes = null): void
    {
        $this->update([
            'status'               => 'completed',
            'items_counted'        => $itemsCounted,
            'discrepancies_found'  => $discrepancies,
            'notes'                => $notes ?? $this->notes,
            'completed_at'         => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'planned'     => 'badge badge--neutral',
            'in_progress' => 'badge badge--info',
            'completed'   => 'badge badge--success',
            'cancelled'   => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
