<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An invitation for one person to join ONE facility in ONE role.
 *
 * The privilege boundary lives here rather than in a controller, because two
 * different controllers touch invites — the facility portal issues them and the
 * public /invite/{token} page redeems them — and a boundary that is restated in
 * two places is a boundary that will eventually disagree with itself.
 *
 * INVITABLE_ROLES is an ALLOW-LIST, deliberately not a blocklist. A blocklist
 * fails open: every role added to RolesSeeder later would silently become
 * mintable by any clinic administrator. Everything absent from this list —
 * super_admin, platform_admin, legal_admin, every compliance/security officer,
 * every insurance, developer and partner role, and the facility-administration
 * tier itself — is not issuable from the facility portal at all.
 */
class FacilityStaffInvite extends Model
{
    use HasFactory, HasUuids;

    /**
     * Roles a facility administrator may invite someone into.
     *
     * Every name here is (a) seeded by RolesSeeder and (b) a member of
     * EnsurePortalAccess::PORTAL_ROLES['portals/staff'], so an accepted invite
     * always lands on an account that can actually reach a portal.
     *
     * Note the omissions: no *_admin role, so a facility admin cannot clone
     * their own privilege level; no multi_doctor, because that role is
     * explicitly cross-facility.
     */
    public const INVITABLE_ROLES = [
        // Clinical providers
        'doctor', 'specialist', 'consultant', 'resident', 'visiting_doctor',
        // Nursing & midwifery
        'nurse', 'triage_nurse', 'ward_nurse', 'midwife', 'nurse_supervisor',
        // Front desk & records
        'receptionist', 'front_desk', 'records_officer',
        // Laboratory
        'labtech', 'lab_scientist', 'lab_manager', 'sample_collection',
        // Pharmacy
        'pharmacist', 'pharmacy_technician', 'pharmacy_manager',
        // Billing
        'cashier', 'billing_officer',
    ];

    /** How long a fresh invite stays redeemable. */
    public const TTL_DAYS = 7;

    protected $fillable = [
        'facility_id', 'role_id', 'email', 'name', 'token_hash',
        'invited_by', 'expires_at', 'accepted_at', 'accepted_user_id',
        'revoked_at', 'revoked_by',
    ];

    /** The hash is a credential verifier — it must never reach a view or a JSON payload. */
    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    // ── Token handling ───────────────────────────────────────────────────

    /** Generate a raw invite token. Returned once, to the issuer, and never stored. */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /** Deterministic hash used for lookup and for storage. */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /** Find an invite by its raw token, or null. Never accepts a hash as input. */
    public static function findByToken(string $rawToken): ?self
    {
        if ($rawToken === '') {
            return null;
        }

        return static::query()->where('token_hash', self::hashToken($rawToken))->first();
    }

    // ── Lifecycle predicates ─────────────────────────────────────────────

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Redeemable right now? */
    public function isUsable(): bool
    {
        return ! $this->isAccepted() && ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Which of the three failure states applies — 'used', 'revoked', 'expired'
     * or null when the invite is fine. The order matters: an invite that was
     * accepted and has since expired should read as used, not expired.
     */
    public function failureReason(): ?string
    {
        return match (true) {
            $this->isAccepted() => 'used',
            $this->isRevoked()  => 'revoked',
            $this->isExpired()  => 'expired',
            default             => null,
        };
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
