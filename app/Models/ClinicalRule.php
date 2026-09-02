<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'rule_type', 'rule_code', 'name', 'description', 'severity',
        'trigger_conditions', 'alert_message', 'recommendation',
        'is_active', 'is_overridable', 'created_by',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active'          => 'boolean',
        'is_overridable'     => 'boolean',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(ClinicalAlert::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
