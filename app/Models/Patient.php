<?php

namespace App\Models;

use App\Enums\IdentityStatus;
use App\Enums\VerificationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Enums\AuditEventType;

class Patient extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'health_id',
        'cnamgs_id',
        'national_id_number',
        'country_code',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'is_dob_estimated',
        'sex',
        'blood_group',
        'phone_number',
        'email',
        'address',
        'emergency_contact',
        'identity_status',
        'verification_status',
        'verified_by_facility_id',
        'verified_at',
        'pin_hash',
        'privacy_preferences',
        'cnamgs_verified_at',
        'national_id_type',
        'push_token',
        'push_platform',
        'facility_id',
    ];

    // is_demo is intentionally excluded from $fillable.
    // Demo status is managed exclusively via forceFill() or direct DB assignment in migrations/seeders.
    // This prevents attackers from mass-assigning demo mode to real patient records via API.

    // PII ENCRYPTION NOTE:
    // - date_of_birth → encrypted cast (AES-256-CBC via APP_KEY); getDateOfBirthAttribute
    //   accessor restores Carbon behaviour so callers using ->toDateString(), ->year etc. work.
    // - phone_number → encrypted via setPhoneNumberAttribute mutator (NOT cast, to also
    //   maintain the phone_number_hash lookup column); findByPhone() enables DB lookup.
    // - address → encrypted cast (AES-256-CBC via APP_KEY)
    // - pin_hash → bcrypt hash, NOT encrypted cast (already a one-way hash, not recoverable)
    // - health_id → NOT encrypted (it is a searchable lookup key, not sensitive in isolation)
    protected $casts = [
        // date_of_birth and address are NOT listed here — their encryption is handled
        // entirely by dedicated get/set accessor+mutator pairs below, matching the
        // phone_number pattern.  Listing them in $casts causes Eloquent to call the
        // encrypted cast before old-style accessors (Laravel 13 priority order), which
        // means a DecryptException from a key-mismatch crashes before our try/catch runs.
        // Removing the cast lets our accessor/mutator own the full encrypt→decrypt cycle.
        'is_dob_estimated'    => 'boolean',
        'emergency_contact'   => 'array',
        'privacy_preferences' => 'array',
        'verified_at'         => 'datetime',
        'cnamgs_verified_at'  => 'datetime',
        'expires_at'          => 'datetime',
        'renewal_required_at' => 'datetime',
        'merged_at'           => 'datetime',
        // Enum casts — values are stored as strings in the DB; the cast makes the
        // PHP attribute a typed BackedEnum so code can compare with VerificationStatus::Verified
        // rather than the string literal 'verified', preventing typo bugs.
        // Laravel's enum cast calls ->value when writing, ->from() when reading,
        // so existing string rows are automatically coerced on first access.
        'verification_status' => VerificationStatus::class,
        'identity_status'     => IdentityStatus::class,
    ];

    /**
     * Return date_of_birth as a Carbon instance.
     *
     * The 'encrypted' cast stores the value as an encrypted blob, but Eloquent calls
     * old-style accessors before applying casts.  We therefore manually decrypt here
     * and then parse to Carbon, so all callers using ->toDateString(), ->year,
     * ->diffInYears() etc. continue to work without modification.
     */
    public function getDateOfBirthAttribute(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;

        // If the value looks like an encrypted payload (base64-JSON), decrypt it.
        if (str_starts_with($raw, 'eyJ')) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable $e) {
                // Decryption failed — most likely an APP_KEY rotation without re-encrypting
                // existing rows.  Log a warning so operations can detect and remediate.
                // Return null rather than exposing ciphertext or a garbage parse result.
                Log::warning('patient_dob_decrypt_failed', [
                    'patient_id' => $this->attributes['id'] ?? 'unknown',
                    'reason'     => 'DecryptException — possible APP_KEY rotation',
                ]);
                return null;
            }
        }

        // Validate the date before parsing to prevent Carbon accepting invalid dates
        // (e.g. "2025-13-45") and silently rolling them forward.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) || !checkdate(
            (int) substr($raw, 5, 2),
            (int) substr($raw, 8, 2),
            (int) substr($raw, 0, 4)
        )) {
            Log::warning('patient_dob_invalid_format', [
                'patient_id' => $this->attributes['id'] ?? 'unknown',
            ]);
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
    }

    /**
     * Decrypt phone_number on read (manually encrypted via setPhoneNumberAttribute).
     */
    public function getPhoneNumberAttribute(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;
        if (str_starts_with($raw, 'eyJ')) {
            try {
                return Crypt::decryptString($raw);
            } catch (\Throwable) {
                // Decryption failed — APP_KEY rotation or corruption.
                // Do NOT return the ciphertext as plaintext; return null.
                Log::warning('patient_phone_decrypt_failed', [
                    'patient_id' => $this->attributes['id'] ?? 'unknown',
                    'reason'     => 'DecryptException — possible APP_KEY rotation',
                ]);
                return null;
            }
        }

        return $raw;
    }

    /**
     * Decrypt address on read.
     *
     * The 'encrypted' cast throws DecryptException when the ciphertext was produced
     * with a different APP_KEY (e.g. a newly generated key in dev, or a key rotation
     * without re-encrypting old rows). Guard identically to phone_number: return the
     * raw value when decryption fails so the page renders rather than crashing.
     */
    public function getAddressAttribute(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;
        if (str_starts_with($raw, 'eyJ')) {
            try {
                return Crypt::decryptString($raw);
            } catch (\Throwable) {
                // Ciphertext unreadable with current APP_KEY — log and return null.
                Log::warning('patient_address_decrypt_failed', [
                    'patient_id' => $this->attributes['id'] ?? 'unknown',
                    'reason'     => 'DecryptException — possible APP_KEY rotation',
                ]);
                return null;
            }
        }

        return $raw;
    }

    /**
     * Encrypt address on write.
     */
    public function setAddressAttribute(?string $value): void
    {
        $this->attributes['address'] = $value !== null ? Crypt::encryptString($value) : null;
    }

    /**
     * Return date_of_birth as a Carbon instance, handling encrypted storage.
     * Removed from $casts so this accessor fires before any encryption attempt.
     */
    public function setDateOfBirthAttribute(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['date_of_birth'] = null;
            return;
        }
        // Accept Carbon, DateTime, or date string — normalize to Y-m-d then encrypt
        $date = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
        $this->attributes['date_of_birth'] = Crypt::encryptString($date->format('Y-m-d'));
    }

    /**
     * When phone_number is set, automatically maintain phone_number_hash for DB lookups.
     * phone_number itself is encrypted (cannot be queried), so the hash enables O(1) lookup
     * by phone number (e.g. mobile auth login) without exposing the plaintext.
     *
     * Hash algorithm: HMAC-SHA256 keyed with APP_KEY (hex-encoded, 64 chars).
     */
    public function setPhoneNumberAttribute(?string $value): void
    {
        $this->attributes['phone_number'] = $value !== null
            ? Crypt::encryptString($value)
            : null;

        $this->attributes['phone_number_hash'] = $value !== null
            ? hash_hmac('sha256', $value, config('app.key'))
            : null;
    }

    /**
     * Compute an HMAC-SHA256 hash of a phone number for DB lookup.
     * Must match the algorithm used in setPhoneNumberAttribute().
     */
    public static function phoneHash(string $phone): string
    {
        return hash_hmac('sha256', $phone, config('app.key'));
    }

    /**
     * Find a patient by phone number using the keyed hash (efficient, encrypted-safe lookup).
     */
    /**
     * Does a human with this identity already hold a Health ID?
     *
     * ─────────────────────────────────────────────────────────────────────
     * WHY THIS IS ONE FUNCTION, AND WHY IT IGNORES THE DEMO SCOPE
     * ─────────────────────────────────────────────────────────────────────
     *
     * Four places create a patient — mobile registration, web signup, the two
     * family screens, and profile completion. Only registration checked for a
     * duplicate at all, and its check had three ways to miss:
     *
     *  1. IT RAN INSIDE THE DEMO PARTITION. `IsDemoRecord` splits this table
     *     in two and every plain `Patient::where()` sees only its own half.
     *     "Does this person already exist?" is not a question about the half
     *     of the register this request may browse — it is a question about
     *     the register. Asked scoped, a person on the other side of the
     *     partition returns zero rows, which is indistinguishable from "no
     *     duplicate". A silent miss on an identity check mints a second
     *     Health ID for a human who already had one.
     *
     *  2. IT COMPARED THE RAW REQUEST STRING TO A NORMALISED DATE.
     *     `'dob' => 'required|date'` accepts `1990-01-01`, `01/01/1990`,
     *     `1990-01-01T00:00:00.000Z` and `January 1, 1990` — all the same
     *     day, none of them string-equal to `->toDateString()`. The stored
     *     value is normalised on the way in, so the comparison has to
     *     normalise on the way out too, or three of those four formats walk
     *     straight past the guard.
     *
     *  3. IT MATCHED NAMES EXACTLY. Postgres `=` is case- and
     *     whitespace-sensitive, so "jude", "Jude " and "Jude" are three
     *     different people to it and one person to everyone else.
     *
     * `date_of_birth` is encrypted with a random IV per value, so it cannot
     * be compared in SQL at all: narrow by name in the database, decrypt the
     * handful of same-name candidates in PHP. Names are the only axis coarse
     * enough to filter on and cheap enough to decrypt behind.
     *
     * Returning the match rather than a boolean lets a caller decide between
     * refusing, linking, or sending the person to Health ID recovery. This
     * NEVER merges anything: per the platform contract, an uncertain match is
     * a human's decision.
     *
     * @param  string|null  $dob    any format Laravel's `date` rule accepts
     * @param  string|null  $phone  raw; hashed here, never compared in clear
     */
    public static function findExistingIdentity(
        ?string $firstName,
        ?string $lastName,
        ?string $dob = null,
        ?string $phone = null,
        ?string $nationalId = null,
    ): ?static {
        // A national ID belongs to one person: an exact, normalised, scope-blind
        // hit is a match on its own. Profile completion collects one and never
        // checked it.
        if ($nationalId !== null && trim($nationalId) !== '') {
            $byNationalId = static::withoutGlobalScope('isolate_demo')
                ->whereRaw('upper(btrim(national_id_number)) = ?', [strtoupper(trim($nationalId))])
                ->first();

            if ($byNationalId) {
                return static::followMerge($byNationalId) ?? $byNationalId;
            }
        }

        // A phone number is the strongest signal here — it is a keyed hash, so
        // this is an exact lookup and needs no name at all.
        if ($phone !== null && trim($phone) !== '') {
            $byPhone = static::withoutGlobalScope('isolate_demo')
                ->where('phone_number_hash', static::phoneHash($phone))
                ->first();

            if ($byPhone) {
                // A hit on a merged-away record IS a hit — the person exists,
                // under the survivor's Health ID.
                return static::followMerge($byPhone) ?? $byPhone;
            }
        }

        if ($firstName === null || $lastName === null || $dob === null) {
            return null;
        }

        try {
            $target = Carbon::parse($dob)->toDateString();
        } catch (\Throwable) {
            // Unparseable input is the validator's problem, not this guard's.
            return null;
        }

        $hit = static::withoutGlobalScope('isolate_demo')
            ->whereRaw('lower(btrim(first_name)) = ?', [mb_strtolower(trim($firstName))])
            ->whereRaw('lower(btrim(last_name)) = ?', [mb_strtolower(trim($lastName))])
            ->get()
            ->first(function (self $candidate) use ($target) {
                try {
                    return $candidate->date_of_birth?->toDateString() === $target;
                } catch (\Throwable) {
                    // A row we cannot decrypt cannot be ruled a match; say so by
                    // skipping it rather than by throwing the registration away.
                    return false;
                }
            });

        return $hit ? (static::followMerge($hit) ?? $hit) : null;
    }

    /**
     * The patient a Health ID opens — following a merge if there was one.
     *
     * Every place a clinician types or scans a Health ID used to do
     * `Patient::where('health_id', …)->first()`, which is right until the day
     * two records for one person are merged. From that day the retired ID —
     * the one printed on the card in the patient's pocket — returns nothing,
     * and "not found" on a Health ID is indistinguishable from a typo.
     *
     * Resolution order:
     *   1. a live record with this Health ID;
     *   2. a record that HAD this Health ID and was merged — follow the
     *      pointer to its survivor (and the survivor's, if it was merged too);
     *   3. an active alias — the retired ID as recorded at merge time.
     *
     * Case-folding here is normalisation of one whole identifier, not a fuzzy
     * match: the comparison stays an equality on the full Health ID. The scope
     * the caller is under still applies — this resolves an identifier, it
     * does not widen who may be seen.
     */
    public static function resolveHealthId(string $healthId): ?static
    {
        $key = strtoupper(trim($healthId));

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        $patient = static::where('health_id', $key)->first();

        if ($patient === null) {
            $alias = HealthIdAlias::where('alias_value', $key)->where('status', 'active')->first();

            if ($alias === null) {
                return null;
            }

            $patient = static::whereKey($alias->patient_id)->first();

            if ($patient === null) {
                return null;
            }

            Log::info(AuditEventType::HealthIdAliasResolved->value, [
                'alias'       => $key,
                'resolved_to' => $patient->health_id,
            ]);
        }

        return static::followMerge($patient);
    }

    /**
     * The patient a UUID opens — following a merge if there was one.
     *
     * A stale deep link, a QR payload, or a bookmark may carry the id of a
     * record that has since been merged away. Opening the retired shell shows
     * a patient with no history and nothing to say why.
     */
    public static function resolveId(string $id): ?static
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $patient = static::whereKey($id)->first();

        return $patient ? static::followMerge($patient) : null;
    }

    /**
     * A merged record hands off to its survivor. Bounded: a chain longer
     * than this is corrupt, and returning the wrong patient is worse than
     * returning none.
     */
    protected static function followMerge(self $patient): ?static
    {
        for ($hop = 0; $hop < 5 && $patient->isMerged(); $hop++) {
            $next = static::whereKey($patient->merged_into_patient_id)->first();

            if ($next === null) {
                return null;
            }

            Log::info(AuditEventType::HealthIdAliasResolved->value, [
                'alias'       => $patient->health_id,
                'resolved_to' => $next->health_id,
                'via'         => 'merged_into',
            ]);

            $patient = $next;
        }

        return $patient->isMerged() ? null : $patient;
    }

    /** Retired by a merge; its rows and its Health ID now belong to another record. */
    public function isMerged(): bool
    {
        $s = $this->identity_status;
        $v = $s instanceof IdentityStatus ? $s->value : (string) $s;

        return $v === IdentityStatus::Merged->value;
    }

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_patient_id')->withoutGlobalScope('isolate_demo');
    }

    public static function findByPhone(string $phone): ?static
    {
        return static::where('phone_number_hash', static::phoneHash($phone))->first();
    }

    public function identifiers()
    {
        return $this->hasMany(PatientIdentifier::class);
    }

    public function allergies()
    {
        return $this->hasMany(AllergyRecord::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function vitals()
    {
        return $this->hasMany(TriageVitalSign::class)->orderByDesc('created_at');
    }

    public function labResults()
    {
        return $this->hasMany(LabResult::class)->orderByDesc('created_at');
    }

    public function immunizations()
    {
        return $this->hasMany(ImmunizationRecord::class);
    }

    public function carePlans()
    {
        return $this->hasMany(CarePlan::class);
    }

    public function surveys()
    {
        return $this->hasMany(PatientSurvey::class);
    }

    /**
     * Retired Health IDs that point to this patient (merge aliases).
     * A canonical patient can have many aliases from merged duplicates.
     */
    public function mergeAliases()
    {
        return $this->hasMany(PatientMergeAlias::class, 'canonical_patient_id');
    }
}
