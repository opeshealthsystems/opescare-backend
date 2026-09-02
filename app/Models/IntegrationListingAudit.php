<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * IntegrationListingAudit — Marketplace
 * Append-only audit log for marketplace listing status changes.
 */
class IntegrationListingAudit extends Model
{
    use HasUuids;

    protected $fillable = ['integration_listing_id', 'action', 'performed_by', 'payload'];
    protected $casts = ['payload' => 'array'];

    public static function record(string $listingId, string $action, array $extra = []): self
    {
        return static::create(array_merge(['integration_listing_id' => $listingId, 'action' => $action], $extra));
    }

    public function update(array $a = [], array $o = []): bool { throw new \LogicException('Append-only.'); }
    public function delete(): ?bool { throw new \LogicException('Append-only.'); }
}
