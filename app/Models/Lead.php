<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A B2B lead captured from the public "Request a demo" funnel.
 */
class Lead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'organization_name',
        'organization_type',
        'message',
        'source',
        'status',
        'assigned_to',
        'notes',
    ];

    /** Valid organization types (matches the public demo-request form). */
    public const ORGANIZATION_TYPES = [
        'facility',
        'insurer',
        'lab',
        'pharmacy',
        'developer',
        'other',
    ];

    /** Valid pipeline statuses. */
    public const STATUSES = [
        'new',
        'contacted',
        'qualified',
        'won',
        'lost',
    ];
}
