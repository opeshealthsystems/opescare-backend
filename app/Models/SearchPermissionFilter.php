<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SearchPermissionFilter — Global Search (Module 38)
 *
 * Defines which resource types each role can search and which fields
 * are returned in search results. Also controls whether the search
 * itself must create an audit log entry.
 *
 * Security: every patient-record search MUST create a SearchLog entry.
 * No role may access patient identifiers via search without an audit trail.
 */
class SearchPermissionFilter extends Model
{
    use HasUuids;

    protected $fillable = [
        'role',
        'resource_type',
        'can_search',
        'requires_audit_log',
        'allowed_fields',
        'restrictions',
    ];

    protected $casts = [
        'can_search'         => 'boolean',
        'requires_audit_log' => 'boolean',
        'allowed_fields'     => 'array',
        'restrictions'       => 'array',
    ];

    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSearchable($query)
    {
        return $query->where('can_search', true);
    }

    public static function canSearch(string $role, string $resourceType): bool
    {
        return static::where('role', $role)
            ->where('resource_type', $resourceType)
            ->where('can_search', true)
            ->exists();
    }

    public static function requiresAudit(string $role, string $resourceType): bool
    {
        $filter = static::where('role', $role)->where('resource_type', $resourceType)->first();
        return $filter ? $filter->requires_audit_log : true; // default to required
    }
}
