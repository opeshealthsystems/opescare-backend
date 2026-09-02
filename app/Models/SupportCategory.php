<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SupportCategory — Module 10 (Support, Helpdesk & Incident Management)
 *
 * Categorises support tickets by context (patient/facility/developer/partner).
 * Drives routing, SLA policy selection, and privacy rules.
 */
class SupportCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'context',
        'default_priority',
        'requires_patient_context',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'requires_patient_context' => 'boolean',
        'is_active'                => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }
}
