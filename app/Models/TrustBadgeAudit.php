<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * TrustBadgeAudit — Trust & Compliance Layer
 * Append-only audit log for TrustBadge assignment lifecycle events.
 */
class TrustBadgeAudit extends Model
{
    use HasUuids;

    protected $fillable = ['trust_badge_assignment_id', 'action', 'performed_by', 'payload'];

    protected $casts = ['payload' => 'array'];

    public static function record(string $assignmentId, string $action, array $extra = []): self
    {
        return static::create(array_merge(['trust_badge_assignment_id' => $assignmentId, 'action' => $action], $extra));
    }

    public function update(array $a = [], array $o = []): bool { throw new \LogicException('Append-only.'); }
    public function delete(): ?bool { throw new \LogicException('Append-only.'); }
}
