<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * LanguageSetting — Module 12 (Master Admin Control Center)
 *
 * Supported platform languages. English and French are required
 * and cannot be disabled. Bilingual support is a core constraint.
 */
class LanguageSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'name', 'native_name', 'direction',
        'is_active', 'is_default', 'is_required', 'display_order',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_default'  => 'boolean',
        'is_required' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name');
    }

    public function canBeDisabled(): bool
    {
        return !$this->is_required;
    }

    public function isRTL(): bool
    {
        return $this->direction === 'rtl';
    }
}
