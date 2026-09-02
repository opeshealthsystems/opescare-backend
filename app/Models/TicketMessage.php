<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_id',
        'body_redacted',
        'pii_redaction_summary',
        'internal',
    ];

    protected $casts = [
        'pii_redaction_summary' => 'array',
        'internal' => 'boolean',
    ];
}
