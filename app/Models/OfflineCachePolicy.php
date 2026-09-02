<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * OfflineCachePolicy — Offline Sync (Module 24)
 *
 * Defines which resource types may be cached offline for a given
 * scope (facility, role, or organization), and the maximum record count
 * and TTL for each.
 *
 * Security constraint (NON-NEGOTIABLE):
 * - Full patient EMR must NEVER be allowed to cache by default.
 * - max_records = 0 means caching is disabled for that scope/resource combo.
 * - Sensitive fields (notes, diagnoses, medications) must be in excluded_fields
 *   unless explicitly whitelisted by a governance review.
 */
class OfflineCachePolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'scope_type',       // facility|role|organization
        'scope_id',
        'resource_type',    // Patient|Appointment|QueueTicket|etc
        'is_cacheable',
        'max_records',      // 0 = disabled
        'ttl_minutes',
        'excluded_fields',
        'policy_note',
    ];

    protected $casts = [
        'is_cacheable'    => 'boolean',
        'max_records'     => 'integer',
        'ttl_minutes'     => 'integer',
        'excluded_fields' => 'array',
    ];

    public function isEnabled(): bool
    {
        return $this->is_cacheable && $this->max_records > 0;
    }

    public function scopeForScope($query, string $scopeType, string $scopeId)
    {
        return $query->where('scope_type', $scopeType)->where('scope_id', $scopeId);
    }

    public static function policyFor(string $scopeType, string $scopeId, string $resourceType): ?self
    {
        return static::forScope($scopeType, $scopeId)->where('resource_type', $resourceType)->first();
    }
}
