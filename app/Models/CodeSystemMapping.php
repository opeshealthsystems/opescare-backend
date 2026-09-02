<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CodeSystemMapping — maps OpesCare local codes to standard terminologies.
 *
 * Standards supported: loinc, icd10, atc, snomed, cpt
 *
 * @property string $id
 * @property string $local_code
 * @property string|null $local_name
 * @property string|null $local_unit
 * @property string $resource_type  LabTest|Diagnosis|Medication|Observation
 * @property string $standard_system  loinc|icd10|atc|snomed|cpt
 * @property string $standard_code
 * @property string|null $standard_display
 * @property string|null $standard_version
 * @property string $mapping_confidence  manual|exact|broader|narrower|approximate
 * @property float|null $confidence_score
 * @property string $status  pending|approved|rejected|deprecated
 * @property string|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $facility_id
 */
class CodeSystemMapping extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'local_code',
        'local_name',
        'local_unit',
        'resource_type',
        'standard_system',
        'standard_code',
        'standard_display',
        'standard_version',
        'mapping_confidence',
        'confidence_score',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'facility_id',
        'created_by',
    ];

    protected $casts = [
        'approved_at'      => 'datetime',
        'confidence_score' => 'decimal:2',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForSystem($query, string $system)
    {
        return $query->where('standard_system', $system);
    }

    public function scopeForResourceType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopePlatformWide($query)
    {
        return $query->whereNull('facility_id');
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where(function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId)->orWhereNull('facility_id');
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function deprecate(): void
    {
        $this->update(['status' => 'deprecated']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved'   => 'badge--success',
            'rejected'   => 'badge--danger',
            'deprecated' => 'badge--warning',
            default      => 'badge--info',
        };
    }

    // ── Lookup Helpers ────────────────────────────────────────────────────────

    /**
     * Find the approved LOINC code for a local lab test code.
     */
    public static function loincFor(string $localCode, ?string $facilityId = null): ?self
    {
        return self::approved()
            ->forSystem('loinc')
            ->where('local_code', $localCode)
            ->when($facilityId, fn($q) => $q->forFacility($facilityId))
            ->first();
    }

    /**
     * Find the approved ICD-10 code for a local diagnosis code.
     */
    public static function icd10For(string $localCode, ?string $facilityId = null): ?self
    {
        return self::approved()
            ->forSystem('icd10')
            ->where('local_code', $localCode)
            ->when($facilityId, fn($q) => $q->forFacility($facilityId))
            ->first();
    }

    /**
     * Find the approved ATC code for a local drug code.
     */
    public static function atcFor(string $localCode, ?string $facilityId = null): ?self
    {
        return self::approved()
            ->forSystem('atc')
            ->where('local_code', $localCode)
            ->when($facilityId, fn($q) => $q->forFacility($facilityId))
            ->first();
    }
}
