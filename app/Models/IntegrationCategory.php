<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * IntegrationCategory — Marketplace / Approved Integrations Directory
 */
class IntegrationCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function listings(): HasMany { return $this->hasMany(IntegrationListing::class); }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
