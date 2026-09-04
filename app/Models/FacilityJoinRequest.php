<?php

namespace App\Models;

use App\Enums\FacilityJoinRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One clinician asking ONE facility to add them to its team.
 *
 * The privilege boundary lives here, next to the one in FacilityStaffInvite,
 * and says the same thing from the other direction:
 *
 *   THE REQUESTER NEVER NAMES THEIR OWN ROLE.
 *
 * `requested_role` is free text the requester types ("senior midwife, night
 * shift"). It is displayed to the reviewer and read by nobody else — there is
 * no code path anywhere that turns it into a Role. The role that is granted is
 * `granted_role_id`, written only by an approving facility administrator and
 * only from FacilityStaffInvite::INVITABLE_ROLES, which is the same allow-list
 * that governs invitations. A facility admin therefore cannot hand out a role
 * they do not have, and a requester cannot hand themselves one at all.
 */
class FacilityJoinRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * How long a declined request keeps the same person from asking the same
     * facility again.
     *
     * A decline that can be re-submitted a second later is not a decline, it is
     * a rate limit of zero. A permanent block is worse — people fix their
     * paperwork and reapply. Seven days is the compromise, and it is enforced
     * on the requester's side only; the facility can always issue an invite.
     */
    public const DECLINE_COOLDOWN_DAYS = 7;

    /** The reviewer sees this; nothing acts on it. */
    public const MAX_REQUESTED_ROLE_LENGTH = 120;

    protected $fillable = [
        'facility_id', 'user_id', 'requested_role', 'message', 'status',
        'granted_role_id', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected $casts = [
        'status'     => FacilityJoinRequestStatus::class,
        'decided_at' => 'datetime',
    ];

    // ── Lifecycle predicates ─────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === FacilityJoinRequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === FacilityJoinRequestStatus::Approved;
    }

    public function isDeclined(): bool
    {
        return $this->status === FacilityJoinRequestStatus::Declined;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === FacilityJoinRequestStatus::Withdrawn;
    }

    /** Still inside the post-decline cooling-off window? */
    public function isInDeclineCooldown(): bool
    {
        return $this->isDeclined()
            && $this->decided_at !== null
            && $this->decided_at->addDays(self::DECLINE_COOLDOWN_DAYS)->isFuture();
    }

    /** When the requester may ask this facility again, or null. */
    public function cooldownEndsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->isInDeclineCooldown()
            ? $this->decided_at->copy()->addDays(self::DECLINE_COOLDOWN_DAYS)
            : null;
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', FacilityJoinRequestStatus::Pending->value);
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The role the FACILITY granted — never anything the requester chose. */
    public function grantedRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'granted_role_id');
    }

    public function decider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
