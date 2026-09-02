<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PatientMergeAlias
 *
 * Stores retired Health IDs so that post-merge lookups on alias IDs are
 * transparently redirected to the canonical patient record.
 *
 * Resolution flow:
 *
 *   HealthIdResolutionController::resolve()
 *     ↓
 *   Patient::where('health_id', $id)->first()
 *     ↓ null
 *   PatientMergeAlias::resolveHealthId($id)    ← NEW
 *     ↓ canonical patient or null
 *
 * Relationships:
 *   - canonicalPatient()  BelongsTo Patient   (the surviving record)
 *   - mergedByUser()      BelongsTo User       (admin who approved)
 *
 * Helper:
 *   - static resolveHealthId(string $healthId): ?Patient
 *         Returns the canonical Patient for a given alias Health ID,
 *         or null if no alias exists.
 *
 * Reverse (patient → aliases):
 *   Add to Patient model:
 *       public function mergeAliases(): HasMany
 *       {
 *           return $this->hasMany(PatientMergeAlias::class, 'canonical_patient_id');
 *       }
 */
class PatientMergeAlias extends Model
{
    use HasUuids;

    protected $table    = 'patient_merge_aliases';
    protected $fillable = [
        'alias_health_id',
        'canonical_patient_id',
        'retired_patient_id',
        'merged_by_user_id',
        'merge_reason',
        'merge_direction',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The canonical (surviving) patient this alias resolves to.
     */
    public function canonicalPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'canonical_patient_id');
    }

    /**
     * The admin user who performed the merge.
     */
    public function mergedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'merged_by_user_id');
    }

    // ─── Static Helpers ───────────────────────────────────────────────────────

    /**
     * Resolve a Health ID to its canonical Patient.
     *
     * Used in HealthIdResolutionController after a direct Patient lookup returns null,
     * to transparently handle alias IDs from merged/retired records.
     *
     * Returns the canonical Patient (including its live health_id, relationships, etc.)
     * or null if no alias mapping exists.
     *
     * Example usage:
     *
     *     $patient = Patient::where('health_id', $id)->first()
     *         ?? PatientMergeAlias::resolveHealthId($id);
     */
    public static function resolveHealthId(string $healthId): ?Patient
    {
        $alias = static::where('alias_health_id', $healthId)
            ->with('canonicalPatient')
            ->first();

        return $alias?->canonicalPatient;
    }

    /**
     * Record a new merge alias.
     *
     * Creates the alias mapping that lets the retired Health ID resolve to the
     * canonical patient. Call this inside the DB::transaction() that performs
     * the actual patient merge to ensure atomicity.
     *
     * @param  string       $retiredHealthId       Health ID of the duplicate being merged away
     * @param  string       $canonicalPatientId    UUID of the surviving patient record
     * @param  string|null  $retiredPatientId      UUID of the duplicate patient (for audit)
     * @param  string|null  $mergedByUserId        Admin user UUID
     * @param  string|null  $reason                Merge justification
     */
    public static function recordMerge(
        string  $retiredHealthId,
        string  $canonicalPatientId,
        ?string $retiredPatientId = null,
        ?string $mergedByUserId   = null,
        ?string $reason           = null,
    ): static {
        return static::create([
            'alias_health_id'      => $retiredHealthId,
            'canonical_patient_id' => $canonicalPatientId,
            'retired_patient_id'   => $retiredPatientId,
            'merged_by_user_id'    => $mergedByUserId,
            'merge_reason'         => $reason,
            'merge_direction'      => 'forward',
        ]);
    }
}
