<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reader comment on a file-based blog post. Pending until a platform admin
 * approves it; author_email is never shown publicly.
 */
class BlogComment extends Model
{
    use HasUuids;

    public const STATUSES = ['pending', 'approved', 'rejected', 'spam'];

    protected $fillable = [
        'post_slug', 'locale', 'parent_id', 'author_name', 'author_email',
        'body', 'status', 'ip_hash', 'user_agent_hash', 'user_id',
    ];

    protected $hidden = ['author_email', 'ip_hash', 'user_agent_hash'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
