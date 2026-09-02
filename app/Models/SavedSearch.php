<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SavedSearch — Module 14 (Global Search)
 *
 * Allows users to save frequently used search filter sets for quick reuse.
 */
class SavedSearch extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'name', 'search_target', 'filters',
        'use_count', 'last_used_at',
    ];

    protected $casts = [
        'filters'      => 'array',
        'last_used_at' => 'datetime',
    ];

    public function touch(string $column = ''): bool
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);
        return true;
    }
}
