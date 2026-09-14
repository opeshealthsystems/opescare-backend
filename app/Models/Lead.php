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
        'converted_facility_id',
        'converted_at',
        'converted_by',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    /** Already turned into a tenant (LeadConversionService)? Never converted twice. */
    public function isConverted(): bool
    {
        return $this->converted_facility_id !== null;
    }

    public function convertedFacility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class, 'converted_facility_id')->withoutGlobalScopes();
    }

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
        // Written only by LeadConversionService, never picked by hand.
        'converted',
    ];
}
