<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SlaPolicy — Module 10 (Support, Helpdesk & Incident Management)
 *
 * Defines response and resolution time targets by ticket priority.
 * Drives SLA breach alerts and support performance reporting.
 */
class SlaPolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'applies_to',
        'priority',
        'first_response_minutes',
        'resolution_minutes',
        'business_hours_only',
        'is_active',
    ];

    protected $casts = [
        'business_hours_only' => 'boolean',
        'is_active'           => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function firstResponseHours(): float
    {
        return round($this->first_response_minutes / 60, 1);
    }

    public function resolutionHours(): float
    {
        return round($this->resolution_minutes / 60, 1);
    }
}
