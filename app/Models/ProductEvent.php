<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductEvent — raw product analytics event record.
 *
 * @property string $id
 * @property string $event_name
 * @property string $event_category
 * @property string|null $facility_id
 * @property string|null $actor_id
 * @property string|null $actor_role
 * @property string|null $patient_id
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property array|null $properties
 * @property string $source_system
 * @property \Carbon\Carbon $occurred_at
 */
class ProductEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_name',
        'event_category',
        'facility_id',
        'actor_id',
        'actor_role',
        'patient_id',
        'resource_type',
        'resource_id',
        'properties',
        'source_system',
        'occurred_at',
    ];

    protected $casts = [
        'properties'  => 'array',
        'occurred_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeForEvent($query, string $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeInPeriod($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    // ── Factory Helper ────────────────────────────────────────────────────────

    /**
     * Record a product event without blocking the caller.
     */
    public static function record(
        string $eventName,
        string $category,
        ?string $facilityId = null,
        ?string $actorId = null,
        ?string $actorRole = null,
        ?string $patientId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $properties = [],
        string $sourceSystem = 'opescare',
    ): self {
        return self::create([
            'event_name'    => $eventName,
            'event_category'=> $category,
            'facility_id'   => $facilityId,
            'actor_id'      => $actorId,
            'actor_role'    => $actorRole,
            'patient_id'    => $patientId,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'properties'    => $properties ?: null,
            'source_system' => $sourceSystem,
            'occurred_at'   => now(),
        ]);
    }
}
