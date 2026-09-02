<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CertificationRequirement — a single checklist item in the certification catalog.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property string $category  fhir|security|data_quality|availability|consent
 * @property string $severity  required|recommended|optional
 * @property bool $is_active
 * @property int $sort_order
 */
class CertificationRequirement extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug', 'name', 'description', 'category', 'severity',
        'is_active', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('severity', 'required');
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            'required'    => 'badge--danger',
            'recommended' => 'badge--warning',
            default       => 'badge--info',
        };
    }
}
