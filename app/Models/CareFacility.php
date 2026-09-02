<?php

namespace App\Models;

use App\Services\FacilityCodeGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CareFacility extends Model
{
    use HasUuids;

    protected $table = 'care_facilities';

    /**
     * Auto-generate a facility_code (OP-[REGION]-FID-[XXXX]) on creation.
     *
     * Three-layer uniqueness guarantee:
     *
     *  Layer 1+2 — FacilityCodeGenerator::generate() checks the DB inside a
     *              PostgreSQL advisory lock so concurrent requests cannot both
     *              claim the same code.
     *
     *  Layer 3   — If a race condition still somehow produces a duplicate
     *              (e.g. a direct DB insert bypassing Eloquent), the DB UNIQUE
     *              constraint throws UniqueConstraintViolationException. We catch
     *              it here, regenerate, and retry the save — the facility record
     *              is never lost due to a code collision.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->facility_code)) {
                $model->facility_code = FacilityCodeGenerator::generate(
                    $model->region ?? 'XX'
                );
            }
        });
    }

    /**
     * Override save() to catch facility_code unique constraint violations and
     * transparently regenerate the code before retrying — Layer 3 safety net.
     *
     * @param  array<string,mixed> $options
     */
    public function save(array $options = []): bool
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return parent::save($options);
            } catch (UniqueConstraintViolationException $e) {
                // Only retry if the violation is on facility_code.
                // All other unique violations (e.g. license_number) should propagate.
                if (! str_contains($e->getMessage(), 'facility_code')) {
                    throw $e;
                }

                if ($attempt === $maxAttempts) {
                    Log::error('CareFacility: could not assign a unique facility_code after retries', [
                        'region'   => $this->region,
                        'attempts' => $maxAttempts,
                    ]);
                    throw $e;
                }

                Log::warning('CareFacility: facility_code collision on save — regenerating', [
                    'attempt'         => $attempt,
                    'colliding_code'  => $this->facility_code,
                    'region'          => $this->region,
                ]);

                // Regenerate and retry
                $this->facility_code = FacilityCodeGenerator::generate(
                    $this->region ?? 'XX'
                );
            }
        }

        return false; // unreachable but satisfies return type
    }

    protected $fillable = [
        'facility_code',
        'partner_id',
        'organization_id',
        'facility_id',
        'facility_name',
        'facility_type',
        'ownership_type',
        'license_number',
        'license_status',
        'verification_status',
        'listing_status',
        'country_code',
        'region',
        'city',
        'address',
        'latitude',
        'longitude',
        'geocoding_accuracy',
        'phone_primary',
        'phone_secondary',
        'email',
        'website',
        'emergency_contact',
        'description',
        'logo_path',
        'cover_image_path',
        'integration_status',
        'last_verified_at',
        'last_profile_update_at',
        'last_availability_update_at',
        'claimed_by_user_id',
        'claimed_at',
        // Import provenance. Without these, a listing created from an approved
        // import review carries no source_ref, and the next importer run — whose
        // partial UNIQUE index on source_ref is the only thing stopping a second
        // copy — would insert the same facility again.
        'source_system',
        'source_ref',
        'source_attribution',
        'source_synced_at',
    ];

    /**
     * The registry extract left this literal string in a NOT NULL column when
     * it had no number. It is on 1,571 of 1,863 rows. It is not a phone number
     * anyone can dial, so nothing may render it and no edit form may present it
     * to a claimant as though it were theirs.
     */
    public const PHONE_PLACEHOLDER = 'N/A';

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'claimed_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_profile_update_at' => 'datetime',
        'last_availability_update_at' => 'datetime',
        // Import provenance. source_synced_at is read by CareFacilityResource,
        // which calls ->toISOString() on it — without the cast that is a
        // fatal on a plain string.
        'source_synced_at' => 'datetime',
    ];

    public function services()
    {
        return $this->hasMany(CareFacilityService::class, 'facility_id');
    }

    public function hours()
    {
        return $this->hasMany(CareFacilityHour::class, 'facility_id');
    }

    public function insurances()
    {
        return $this->hasMany(CareFacilityInsurance::class, 'facility_id');
    }

    public function pharmacyStock()
    {
        return $this->hasMany(PharmacyStockAvailability::class, 'facility_id');
    }

    public function labTests()
    {
        return $this->hasMany(LabTestAvailability::class, 'facility_id');
    }

    public function bloodAvailability()
    {
        return $this->hasMany(BloodAvailability::class, 'facility_id');
    }

    public function claims()
    {
        return $this->hasMany(FacilityClaim::class, 'facility_id');
    }

    /** Claims made against this *listing* (as opposed to the operational tenant). */
    public function listingClaims()
    {
        return $this->hasMany(FacilityClaim::class, 'care_facility_id');
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    /**
     * The phone number, or null. `'N/A'` is a placeholder, not a number —
     * see PHONE_PLACEHOLDER. Every caller that shows a phone to a human should
     * go through here rather than reading the column.
     */
    public function dialablePhone(): ?string
    {
        return self::realValue($this->phone_primary);
    }

    /** Null out placeholder/blank values so a form shows an empty field. */
    public static function realValue(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || strcasecmp($value, self::PHONE_PLACEHOLDER) === 0) {
            return null;
        }

        return $value;
    }

    /**
     * Somebody has been approved to maintain this listing.
     *
     * Deliberately independent of `verification_status`: a claim is a person
     * saying "this is mine", and that is not the same fact as OpesCare having
     * verified the institution. Nothing in this directory is verified yet.
     */
    public function isClaimed(): bool
    {
        return $this->claimed_by_user_id !== null;
    }

    public function reports()
    {
        return $this->hasMany(FacilityReport::class, 'facility_id');
    }

    public function audits()
    {
        return $this->hasMany(FacilityUpdateAudit::class, 'facility_id');
    }
}
