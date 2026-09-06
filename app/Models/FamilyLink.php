<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A guardian's standing access to a dependent's record.
 *
 * `guardian_user_id` is the person acting (a users row — audit trails are
 * attributed to them); `dependent_patient_id` is the record being acted on.
 *
 * DIRECTION OF `relationship`: it is the GUARDIAN's role toward the dependent —
 * "I am their parent / grandparent / child / caregiver". A parent managing a
 * child stores `parent`; an adult daughter managing her mother stores `child`.
 * The web form ("Your relationship") has always used this direction; the
 * mobile register form used to default to `child` meaning the opposite, which
 * is why both readings exist in old rows. New writes follow this rule.
 *
 * VOCABULARY OF `access_level`: canonical values are `full` and `read_only`.
 * Older mobile invites wrote `view_only` (→ read_only) and `guardian` (→ full);
 * the accessor maps them so every reader sees the canonical pair and a legacy
 * value can never be mistaken for write access.
 */
class FamilyLink extends Model
{
    use HasFactory, HasUuids;

    public const ACCESS_FULL      = 'full';
    public const ACCESS_READ_ONLY = 'read_only';

    /** @var list<string> */
    public const ACCESS_LEVELS = [self::ACCESS_FULL, self::ACCESS_READ_ONLY];

    /** Legacy spellings still found in old rows, mapped to the canonical pair. */
    public const LEGACY_ACCESS_LEVELS = [
        'view_only' => self::ACCESS_READ_ONLY,
        'guardian'  => self::ACCESS_FULL,
    ];

    public const STATUS_PENDING_INVITE = 'pending_invite';
    public const STATUS_ACTIVE         = 'active';
    public const STATUS_REVOKED        = 'revoked';
    public const STATUS_EXPIRED        = 'expired';
    public const STATUS_CANCELLED      = 'cancelled';
    public const STATUS_DECLINED       = 'declined';

    /**
     * Statuses after which the same guardian/dependent pair may be linked
     * again. The partial unique index on (guardian_user_id, dependent_patient_id)
     * excludes exactly these.
     *
     * @var list<string>
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_REVOKED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
        self::STATUS_DECLINED,
    ];

    public const CREATED_BY_SELF_REGISTERED  = 'self_registered';
    public const CREATED_BY_GUARDIAN_INVITED = 'guardian_invited';
    public const CREATED_BY_INVITE_ACCEPTED  = 'invite_accepted';

    /** The guardian's role toward the dependent (see class docblock). */
    public const RELATIONSHIPS = [
        'parent', 'grandparent', 'child', 'spouse', 'sibling', 'caregiver', 'legal_guardian', 'other',
    ];

    protected $fillable = [
        'guardian_user_id',
        'dependent_patient_id',
        'relationship',
        'access_level',
        'status',
        'created_by',
        'invite_token',
        'invite_expires_at',
        'notification_prefs',
        'age_transition_notified_at',
        'age_transition_expires_at',
        'dependent_consented_at',
    ];

    protected $casts = [
        'notification_prefs'         => 'array',
        'invite_expires_at'          => 'datetime',
        'age_transition_notified_at' => 'datetime',
        'age_transition_expires_at'  => 'datetime',
        'dependent_consented_at'     => 'datetime',
    ];

    public function guardianUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function dependentPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class, 'dependent_patient_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePendingInvite(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_INVITE);
    }

    /** Pending invitations that can still be accepted. */
    public function scopeOpenInvite(Builder $query): Builder
    {
        return $query->pendingInvite()->where(function (Builder $q) {
            $q->whereNull('invite_expires_at')->orWhere('invite_expires_at', '>', now());
        });
    }

    // ── access_level vocabulary ──────────────────────────────────────────────

    /** Canonical spelling for any stored or submitted access level; unknown → read_only (fails closed). */
    public static function normalizeAccessLevel(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        if (in_array($value, self::ACCESS_LEVELS, true)) {
            return $value;
        }

        return self::LEGACY_ACCESS_LEVELS[$value] ?? self::ACCESS_READ_ONLY;
    }

    /** Every reader sees `full` or `read_only`, whatever an old row says. */
    public function getAccessLevelAttribute(?string $value): string
    {
        return self::normalizeAccessLevel($value);
    }

    public function setAccessLevelAttribute(?string $value): void
    {
        $this->attributes['access_level'] = self::normalizeAccessLevel($value);
    }

    public function isReadOnly(): bool
    {
        return $this->access_level === self::ACCESS_READ_ONLY;
    }

    public function isFullAccess(): bool
    {
        return $this->access_level === self::ACCESS_FULL;
    }

    // ── state ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpiredByAge(): bool
    {
        return $this->age_transition_expires_at !== null
            && $this->age_transition_expires_at->isPast();
    }

    /** Active, and not suspended by the dependent's 18th-birthday grace period. */
    public function grantsAccess(): bool
    {
        return $this->isActive() && ! $this->isExpiredByAge();
    }

    public function notificationPrefFor(string $eventKey, string $channel): bool
    {
        $defaults = [
            'lab_result'      => ['portal' => true, 'email' => true,  'sms' => false],
            'appointment'     => ['portal' => true, 'email' => true,  'sms' => false],
            'consent_request' => ['portal' => true, 'email' => true,  'sms' => true],
            'age_transition'  => ['portal' => true, 'email' => true,  'sms' => true],
        ];
        $prefs = $this->notification_prefs[$eventKey] ?? $defaults[$eventKey] ?? [];
        return (bool) ($prefs[$channel] ?? ($defaults[$eventKey][$channel] ?? false));
    }
}
