<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * GoLiveAudit — Facility Go-Live Readiness
 *
 * Append-only audit log for every go-live workflow action.
 * Must NEVER be updated or deleted.
 */
class GoLiveAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'go_live_checklist_id',
        'action',   // checklist_item_completed|blocker_raised|blocker_resolved|approved|rejected
        'performed_by',
        'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public static function record(string $facilityId, string $action, array $payload = []): self
    {
        return static::create([
            'facility_id'           => $facilityId,
            'action'                => $action,
            'payload'               => $payload,
            'performed_by'          => $payload['performed_by'] ?? null,
            'go_live_checklist_id'  => $payload['go_live_checklist_id'] ?? null,
        ]);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('GoLiveAudit records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('GoLiveAudit records are append-only.');
    }
}
