<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DeveloperOrganization — Connect Suite / Developer Portal
 *
 * An organization that a DeveloperAccount belongs to on the developer portal.
 * Organizations can hold multiple apps and credentials.
 */
class DeveloperOrganization extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_account_id',
        'name',
        'website',
        'status',                    // active|suspended|pending
        'production_approved',
    ];

    protected $casts = [
        'production_approved' => 'boolean',
    ];

    public function developerAccount(): BelongsTo
    {
        return $this->belongsTo(DeveloperAccount::class);
    }

    public function apps(): HasMany
    {
        return $this->hasMany(DeveloperApp::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
