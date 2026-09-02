<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupportAttachment — Module 10 (Support, Helpdesk & Incident Management)
 *
 * Files attached to support tickets or messages.
 * Access is restricted to ticket parties by default.
 *
 * Security: Support users must not access patient records without explicit
 * permission. Attachments follow the same rule — access_level controls who
 * can download. Patient clinical documents must NOT be attached here.
 */
class SupportAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'ticket_message_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'access_level',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function isAccessibleBy(string $role): bool
    {
        return match($this->access_level) {
            'ticket_parties' => in_array($role, ['patient', 'facility_user', 'developer', 'support_agent', 'admin']),
            'support_agents' => in_array($role, ['support_agent', 'admin']),
            'admin'          => $role === 'admin',
            default          => false,
        };
    }

    public function formattedFileSize(): string
    {
        $size = $this->file_size ?? 0;
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 1) . ' KB';
        return round($size / 1048576, 1) . ' MB';
    }
}
