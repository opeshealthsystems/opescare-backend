<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ApiAbuseFlag — Security Operations / Connect Suite
 *
 * Flags raised when an API consumer (key, SDK, partner, or user) exceeds
 * rate limits or exhibits scraping/abuse behaviour.
 */
class ApiAbuseFlag extends Model
{
    use HasUuids;

    protected $fillable = [
        'api_consumer_type',  // key|sdk|partner|user
        'api_consumer_id',
        'flag_type',          // rate_limit_breach|scraping|unusual_volume|banned_endpoint
        'request_count',
        'time_window',
        'status',             // open|reviewed|blocked|dismissed
        'evidence',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'evidence'    => 'array',
        'reviewed_at' => 'datetime',
        'request_count' => 'integer',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function block(string $reviewedBy): void
    {
        $this->update(['status' => 'blocked', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now()]);
    }

    public function dismiss(string $reviewedBy): void
    {
        $this->update(['status' => 'dismissed', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now()]);
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
