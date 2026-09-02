<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityRegistry extends Model
{
    use HasUuids;

    protected $table = 'facility_registry';

    protected $fillable = [
        'name', 'name_fr', 'type', 'ownership', 'region',
        'division', 'city', 'address', 'gps_lat', 'gps_lng',
        'phone', 'email', 'website', 'ministry_code',
        'accreditation_level', 'bed_capacity', 'services',
        'source', 'source_url', 'status',
        'claimed_facility_id', 'claimed_at',
    ];

    protected $casts = [
        'services'     => 'array',
        'gps_lat'      => 'decimal:7',
        'gps_lng'      => 'decimal:7',
        'bed_capacity' => 'integer',
        'claimed_at'   => 'datetime',
    ];

    public function claimedFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'claimed_facility_id');
    }

    public function scopeUnclaimed($query)
    {
        return $query->whereNull('claimed_facility_id');
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'closed');
    }
}
