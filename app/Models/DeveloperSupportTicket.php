<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeveloperSupportTicket — Connect Suite / Developer Portal
 *
 * Support tickets submitted by developers through the developer portal.
 * Separate from the clinical SupportTicket model.
 */
class DeveloperSupportTicket extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_account_id',
        'developer_app_id',
        'subject',
        'description',
        'category',     // integration|billing|bug|feature_request
        'status',       // open|in_progress|resolved|closed
        'priority',     // low|normal|high|critical
        'assigned_to',
    ];

    public function developerAccount(): BelongsTo
    {
        return $this->belongsTo(DeveloperAccount::class);
    }

    public function developerApp(): BelongsTo
    {
        return $this->belongsTo(DeveloperApp::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
