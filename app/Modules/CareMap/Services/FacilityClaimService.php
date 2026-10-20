<?php

namespace App\Modules\CareMap\Services;

use App\Enums\FacilityClaimStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityRegistry;
use App\Models\FacilityUpdateAudit;
use App\Models\User;
use App\Models\AuditEvent;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Notifications\FacilityClaimUpdateNotification;
use App\Services\Marketing\LeadConversionService;
use App\Services\Portal\FacilityMembershipService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class FacilityClaimService
{
    /**
     * Submit a claim request.
     *
     * For registry claims, pass $registryEntryId (the facility_registry.id being claimed).
     * For CareMap-only claims, leave $registryEntryId null.
     *
     * @param  string      $facilityId      facilities.id (the operational Facility doing the claiming)
     * @param  string      $userId          The user submitting the claim
     * @param  string|null $reason          Optional reason text
     * @param  string|null $registryEntryId facility_registry.id being claimed (registry flow only)
     */
    public function submitClaim($facilityId, $userId, $reason = null, $registryEntryId = null)
    {
        // Prevent duplicate pending claims for the same facility + claimant
        $exists = FacilityClaim::where('facility_id', $facilityId)
            ->where('claimant_user_id', $userId)
            ->where('claim_status', FacilityClaimStatus::Submitted->value)
            ->exists();

        if ($exists) {
            throw new \Exception('FACILITY_CLAIM_ALREADY_EXISTS');
        }

        // For registry claims: also block if the registry entry is already claimed
        if ($registryEntryId) {
            $alreadyClaimed = FacilityRegistry::where('id', $registryEntryId)
                ->whereNotNull('claimed_facility_id')
                ->exists();

            if ($alreadyClaimed) {
                throw new \Exception('REGISTRY_ENTRY_ALREADY_CLAIMED');
            }
        }

        return FacilityClaim::create([
            'facility_id'       => $facilityId,
            'registry_entry_id' => $registryEntryId,
            'claimant_user_id'  => $userId,
            'claim_reason'      => $reason,
            'claim_status'      => FacilityClaimStatus::Submitted->value,
        ]);
    }

    /**
     * Submit a claim on a *directory listing* — the public flow.
     *
     * This is what actually happens on opescare.cloud: a person running a
     * facility finds their entry in the Care Map and says it is theirs. The
     * listing is a `care_facilities` row, which may or may not have an
     * operational `facilities` tenant behind it (1,395 of 1,863 do not), so the
     * claim is anchored on `care_facility_id` and carries the contact details a
     * reviewer needs.
     *
     * It returns a claim in `pending_verification` (code sent to the listing's
     * phone) or `pending_review` (code could not be delivered, flagged for the
     * admin to call). No code path in this method can return an approved one.
     *
     * @param  array{claimant_name?:string,claimant_role?:string,claimant_email?:string,claimant_phone?:string,claim_reason?:string} $contact
     */
    public function submitDirectoryClaim(string $careFacilityId, string $userId, array $contact = [], ?UploadedFile $document = null): FacilityClaim
    {
        $listing = CareFacility::findOrFail($careFacilityId);

        // Ownership proof part 2: no document, no claim (OB-1).
        if ($document === null) {
            throw new \Exception('DOCUMENT_REQUIRED');
        }
        if (FacilityClaimProofService::sniffMime($document) === null) {
            throw new \Exception('DOCUMENT_INVALID');
        }

        // A claimant with a live claim on this listing has nothing to submit.
        $mine = FacilityClaim::where('care_facility_id', $listing->id)
            ->where('claimant_user_id', $userId)
            ->whereIn('claim_status', FacilityClaimStatus::blocking())
            ->exists();

        if ($mine) {
            throw new \Exception('FACILITY_CLAIM_ALREADY_EXISTS');
        }

        // Someone else has already been approved for it. A second owner is a
        // dispute, not a form submission — it goes to support, not to a queue
        // that would silently hand over a hospital.
        if ($listing->isClaimed() && $listing->claimed_by_user_id !== $userId) {
            throw new \Exception('LISTING_ALREADY_CLAIMED');
        }

        $claim = DB::transaction(function () use ($listing, $userId, $contact, $document): FacilityClaim {
            $claim = FacilityClaim::create([
                'care_facility_id' => $listing->id,
                'facility_id'      => $listing->facility_id,   // may legitimately be null
                'claimant_user_id' => $userId,
                'claimant_name'    => $contact['claimant_name']  ?? null,
                'claimant_role'    => $contact['claimant_role']  ?? null,
                'claimant_email'   => $contact['claimant_email'] ?? null,
                'claimant_phone'   => $contact['claimant_phone'] ?? null,
                'claim_reason'     => $contact['claim_reason']   ?? null,
                'claim_status'     => FacilityClaimStatus::PendingVerification->value,
                'submitted_at'     => now(),
            ]);

            $this->proof()->storeDocument($claim, $document);

            return $claim;
        });

        // Ownership proof part 1: a code to the LISTING's phone. When it
        // cannot be delivered the claim moves to review, flagged.
        try {
            $this->proof()->sendCode($claim);
        } catch (\Throwable $e) {
            Log::warning('facility_claim_code_not_sent', ['claim_id' => $claim->id, 'reason' => $e->getMessage()]);
        }

        $this->notifyClaimant($claim->fresh(), FacilityClaimUpdateNotification::FILED);

        return $claim->fresh();
    }

    private function proof(): FacilityClaimProofService
    {
        return app(FacilityClaimProofService::class);
    }

    /**
     * The listing an authenticated user is allowed to edit — the ONLY way a
     * self-service edit may learn which facility it is about.
     *
     * It takes a user id and nothing else. No route parameter, no request body,
     * no session value, no "first facility" fallback reaches it. A clerk once
     * published another hospital's blood stock because the facility came from
     * `Facility::value('id')`; the shape that made that possible is a lookup
     * that can succeed without naming the actor. This one cannot.
     */
    public function approvedListingFor(?string $userId): ?CareFacility
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        $claim = FacilityClaim::query()
            ->where('claimant_user_id', $userId)
            ->where('claim_status', FacilityClaimStatus::Approved->value)
            ->whereNotNull('care_facility_id')
            ->orderByDesc('reviewed_at')
            ->first();

        if ($claim === null) {
            return null;
        }

        $listing = CareFacility::find($claim->care_facility_id);

        // Belt and braces: the listing must still name this user as its
        // claimant. Revoking access is then a single column write, and a stale
        // approved claim row can never resurrect it.
        if ($listing === null || $listing->claimed_by_user_id !== $userId) {
            return null;
        }

        return $listing;
    }

    /** Every claim this user has made, newest first — for their status page. */
    public function claimsFor(string $userId)
    {
        return FacilityClaim::with('careFacility')
            ->where('claimant_user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Approve a claim request. Only ever called by an administrator.
     *
     * Three flows:
     *
     * A) Listing claim (care_facility_id set) — the public flow:
     *    Stamps the listing with claimed_by_user_id + claimed_at + partner_id.
     *
     * B) Registry claim (registry_entry_id set):
     *    1. Stamps facility_registry with claimed_facility_id + claimed_at
     *    2. Creates or activates a care_facilities listing from the registry entry data
     *
     * C) CareMap claim (neither set):
     *    Finds the care_facilities listing linked to the operational Facility
     *    and stamps it as claimed.
     *
     * What it never does, in any flow, is write `verification_status`. A person
     * asserting they run a hospital is not verification of that hospital, and
     * the directory has verified nothing. Approval says "this person maintains
     * this listing" and the UI must show that as its own, weaker, state.
     */
    public function approveClaim($claimId, $adminId)
    {
        $claim = FacilityClaim::findOrFail($claimId);

        if ($claim->care_facility_id) {
            return $this->approveListingClaim($claim, $adminId);
        }

        // Registry / tenant-anchored claims (no listing id). These come from a
        // facility that already has a tenant; they keep their flow, but get
        // the same state guard.
        if (! in_array($claim->claim_status, [
            FacilityClaimStatus::Submitted, FacilityClaimStatus::UnderReview, FacilityClaimStatus::PendingReview,
        ], true)) {
            throw new \Exception('CLAIM_NOT_REVIEWABLE');
        }

        DB::transaction(function () use ($claim, $adminId) {
            $claim->update([
                'claim_status' => FacilityClaimStatus::Approved->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
            ]);

            if ($claim->registry_entry_id) {
                // ── Registry claim flow ───────────────────────────────────────
                $registryEntry = FacilityRegistry::findOrFail($claim->registry_entry_id);

                // Stamp the registry entry as claimed
                $registryEntry->update([
                    'claimed_facility_id' => $claim->facility_id,
                    'claimed_at'          => now(),
                    'status'              => 'verified',
                ]);

                // Auto-create or activate the care_facilities listing
                $this->upsertCareFacilityFromRegistry($registryEntry, $claim->facility_id, $claim->claimant_user_id);
                $listing = CareFacility::where('facility_id', $claim->facility_id)->first();

                if ($listing) {
                    $this->stampClaimed($listing, $claim->claimant_user_id, $adminId);
                }

                return;
            }

            // ── Original CareMap claim flow ───────────────────────────────────
            $careFacility = CareFacility::where('facility_id', $claim->facility_id)->first();

            if ($careFacility) {
                $this->stampClaimed($careFacility, $claim->claimant_user_id, $adminId);
            }
        });

        return $claim->fresh();
    }

    /**
     * Approve a public listing claim (owner decision 2026-10-20, OB-2 + PH-2).
     *
     * Guards, all re-checked under row locks so two admins clicking at once
     * serialise:
     *   - only `pending_review` (proof gathered) — never submitted (no proof),
     *     rejected, revoked or already approved: CLAIM_NOT_REVIEWABLE;
     *   - a document must be on file: CLAIM_HAS_NO_DOCUMENT;
     *   - the listing must not be claimed already, or carry another approved
     *     claim: LISTING_ALREADY_CLAIMED;
     *   - the listing must not already be joined to a tenant: LISTING_ALREADY_LINKED.
     *
     * Then, in ONE transaction: the claim approved; the listing stamped as
     * claimed; a tenant — the claimant's single existing posting when that
     * facility has no listing yet (no duplicate tenants), otherwise a NEW
     * facility of the listing's type — joined through
     * `care_facilities.facility_id`; and an INACTIVE manager-role assignment
     * for the claimant (pharmacy → pharmacy_manager, laboratory →
     * lab_manager, else facility_admin; LeadConversionService::managerRoleFor).
     * The assignment switches on only when the claimant opens the activation
     * link with two-step verification enrolled (activate()).
     *
     * @throws \Exception CLAIM_NOT_REVIEWABLE | CLAIM_HAS_NO_DOCUMENT | LISTING_ALREADY_CLAIMED | LISTING_ALREADY_LINKED | ROLE_UNKNOWN
     */
    private function approveListingClaim(FacilityClaim $claim, $adminId): FacilityClaim
    {
        $approved = DB::transaction(function () use ($claim, $adminId): FacilityClaim {
            /** @var FacilityClaim $locked */
            $locked = FacilityClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if (! $locked->claim_status->isReviewable()) {
                throw new \Exception('CLAIM_NOT_REVIEWABLE');
            }
            if (! $locked->hasDocument()) {
                throw new \Exception('CLAIM_HAS_NO_DOCUMENT');
            }

            /** @var CareFacility $listing */
            $listing = CareFacility::whereKey($locked->care_facility_id)->lockForUpdate()->firstOrFail();

            $otherApproved = FacilityClaim::where('care_facility_id', $listing->id)
                ->whereKeyNot($locked->id)
                ->where('claim_status', FacilityClaimStatus::Approved->value)
                ->exists();

            if ($listing->isClaimed() || $otherApproved) {
                throw new \Exception('LISTING_ALREADY_CLAIMED');
            }
            if ($listing->facility_id !== null) {
                throw new \Exception('LISTING_ALREADY_LINKED');
            }

            $claimant = User::findOrFail($locked->claimant_user_id);
            $type     = in_array($listing->facility_type, LeadConversionService::TYPES, true) ? $listing->facility_type : 'clinic';
            $role     = Role::where('name', LeadConversionService::managerRoleFor($type))->first();

            if (! $role) {
                throw new \Exception('ROLE_UNKNOWN');
            }

            $locked->update([
                'claim_status' => FacilityClaimStatus::Approved->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
            ]);

            // Joins the claimant's single existing posting when unambiguous.
            $this->stampClaimed($listing, $claimant->id, $adminId);
            $listing->refresh();

            if ($listing->facility_id === null) {
                $facility = Facility::create([
                    'name'   => $listing->facility_name,
                    'type'   => $type,
                    'status' => 'active',
                ]);

                $listing->update(['facility_id' => $facility->id]);

                FacilityUpdateAudit::create([
                    'facility_id'     => $listing->id,
                    'actor_id'        => $adminId,
                    'actor_type'      => 'admin',
                    'field_changed'   => 'facility_id',
                    'old_value'       => null,
                    'new_value'       => (string) $facility->id,
                    'source'          => 'facility_claim_approval_tenant_created',
                    'requires_review' => false,
                    'created_at'      => now(),
                ]);
            }

            $facilityId = (string) $listing->facility_id;

            FacilityRoleAssignment::firstOrCreate(
                ['user_id' => $claimant->id, 'facility_id' => $facilityId, 'role_id' => $role->id],
                ['is_active' => false, 'assigned_by' => $adminId, 'assigned_at' => now()],
            );

            $locked->update([
                'facility_id'         => $facilityId,
                'created_facility_id' => $facilityId,
            ]);

            AuditEvent::create([
                'actor_id'           => $adminId,
                'facility_id'        => $facilityId,
                'action_type'        => 'facility_claim_approved',
                'resource_type'      => 'FacilityClaim',
                'resource_id'        => $locked->id,
                'source_system'      => 'portal',
                'emergency_override' => false,
                'after_state'        => [
                    'listing_id'     => $listing->id,
                    'facility_id'    => $facilityId,
                    'role'           => $role->name,
                    'phone_verified' => $locked->isPhoneVerified(),
                    'sms_status'     => $locked->sms_status,
                ],
                'created_at'         => now(),
            ]);

            return $locked->fresh();
        });

        app(FacilityMembershipService::class)->forget(User::find($approved->claimant_user_id));

        $this->notifyClaimant($approved, FacilityClaimUpdateNotification::APPROVED, $this->activationUrlFor($approved));

        return $approved;
    }

    /** A shareable activation link: signed, 7 days, useless to anyone but the claimant. */
    public function activationUrlFor(FacilityClaim $claim): ?string
    {
        if ($claim->claim_status !== FacilityClaimStatus::Approved || $claim->created_facility_id === null) {
            return null;
        }

        return URL::temporarySignedRoute('portals.listing.claims.activate', now()->addDays(7), ['claim' => $claim->id]);
    }

    /**
     * The claimant switches on their manager role. Requires two-step
     * verification to be enrolled first (MFA on first staff sign-in).
     * Idempotent: a second call changes nothing.
     *
     * @throws \Exception CLAIM_NOT_ACTIVATABLE | MFA_REQUIRED
     */
    public function activate(FacilityClaim $claim, User $user): FacilityClaim
    {
        if ($claim->claimant_user_id !== $user->id
            || $claim->claim_status !== FacilityClaimStatus::Approved
            || $claim->created_facility_id === null) {
            throw new \Exception('CLAIM_NOT_ACTIVATABLE');
        }

        if ($claim->activated_at !== null) {
            return $claim;
        }

        if (! $user->hasTwoFactorEnabled()) {
            throw new \Exception('MFA_REQUIRED');
        }

        DB::transaction(function () use ($claim, $user): void {
            FacilityRoleAssignment::where('user_id', $user->id)
                ->where('facility_id', $claim->created_facility_id)
                ->update(['is_active' => true]);

            $roleId = FacilityRoleAssignment::where('user_id', $user->id)
                ->where('facility_id', $claim->created_facility_id)
                ->value('role_id');

            // A consumer account becomes a staff account. A user who already
            // holds a staff role keeps it; the facility assignment above is
            // what capability checks read at this facility.
            if ($roleId && in_array($user->roleName(), [null, 'patient', 'emergency_contact'], true)) {
                $user->role_id = $roleId;
            }
            if ($user->primary_facility_id === null) {
                $user->primary_facility_id = $claim->created_facility_id;
            }
            $user->save();

            $claim->forceFill(['activated_at' => now()])->save();
        });

        app(FacilityMembershipService::class)->forget($user);

        return $claim->fresh();
    }

    /**
     * Reject a claim request. Only an open claim can be rejected; an approved
     * one is withdrawn with revokeClaim().
     */
    public function rejectClaim($claimId, $adminId, $notes = null)
    {
        $claim = FacilityClaim::findOrFail($claimId);

        if (! $claim->claim_status->isOpen()) {
            throw new \Exception('CLAIM_NOT_REVIEWABLE');
        }

        $claim->update([
            'claim_status' => FacilityClaimStatus::Rejected->value,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

        $this->notifyClaimant($claim, FacilityClaimUpdateNotification::REJECTED, null, $notes);

        return $claim;
    }

    /**
     * Withdraw an approved claim. The listing keeps every edit already made —
     * they are attributed in facility_update_audits and remain true — but the
     * claimant stops being able to make new ones, immediately, because
     * approvedListingFor() re-checks the listing column on every request.
     */
    public function revokeClaim($claimId, $adminId, $notes = null)
    {
        $claim = FacilityClaim::findOrFail($claimId);

        if ($claim->claim_status !== FacilityClaimStatus::Approved) {
            throw new \Exception('CLAIM_NOT_REVOCABLE');
        }

        DB::transaction(function () use ($claim, $adminId, $notes) {
            $claim->update([
                'claim_status' => FacilityClaimStatus::Revoked->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);

            if ($claim->care_facility_id) {
                CareFacility::where('id', $claim->care_facility_id)
                    ->where('claimed_by_user_id', $claim->claimant_user_id)
                    ->update(['claimed_by_user_id' => null, 'claimed_at' => null]);
            }
        });

        return $claim->fresh();
    }

    /** Database + mail (+ SMS when a provider is live). Never throws. */
    private function notifyClaimant(FacilityClaim $claim, string $event, ?string $activationUrl = null, ?string $notes = null): void
    {
        try {
            $user = User::find($claim->claimant_user_id);
            if (! $user) {
                return;
            }

            $name = (string) (CareFacility::whereKey($claim->care_facility_id)->value('facility_name') ?? '');

            $user->notify(new FacilityClaimUpdateNotification($event, $claim->id, $name, $activationUrl, $notes));

            $gateway = app(ClaimSmsGateway::class);
            if ($claim->claimant_phone && $gateway->isLive()) {
                $gateway->deliver($claim->claimant_phone, __('caremap_claim.notify.' . $event . '_body', ['facility' => $name]));
            }
        } catch (\Throwable $e) {
            Log::warning('facility_claim_notify_failed', ['claim_id' => $claim->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Record on the listing that a named person now maintains it.
     *
     * `partner_id` is set as well because OsmFacilityImporter reads it as
     * "somebody maintains this listing" and stops overwriting the row — which
     * is exactly right once a claim is approved.
     */
    private function stampClaimed(CareFacility $listing, ?string $claimantId, $adminId): void
    {
        $before = $listing->claimed_by_user_id;

        $listing->update([
            'claimed_by_user_id' => $claimantId,
            'claimed_at'         => now(),
            'partner_id'         => $listing->partner_id ?? $claimantId,
        ]);

        $this->linkListingToClaimantFacility($listing, $claimantId, $adminId);

        FacilityUpdateAudit::create([
            'facility_id'     => $listing->id,
            'actor_id'        => $adminId,
            'actor_type'      => 'admin',
            'field_changed'   => 'claimed_by_user_id',
            'old_value'       => $before,
            'new_value'       => $claimantId,
            'source'          => 'facility_claim_approval',
            'requires_review' => false,
            'created_at'      => now(),
        ]);
    }

    /**
     * Join an approved listing to the tenant facility its claimant works for.
     *
     * ─────────────────────────────────────────────────────────────────────
     * WHY: approving a claim used to change nothing that anything could see
     * ─────────────────────────────────────────────────────────────────────
     *
     * `care_facilities.facility_id` is the ONLY join between the 2,700-row
     * public directory and the operational `facilities` a clinician actually
     * holds a posting at. Every question worth asking of the directory — does
     * this listing have doctors, can I book it, is it connected — is that join.
     *
     * The listing-claim flow never wrote it. `submitDirectoryClaim()` copies
     * `facility_id` off the listing being claimed ("may legitimately be null"
     * — and for a seeded directory row it always is), and approval stamped
     * `claimed_by_user_id` and stopped. So a hospital could claim its listing,
     * be approved, and stay invisible. On production today the one facility in
     * the entire register that has a doctor is Hopital Central de Yaounde, and
     * its directory row's `facility_id` is NULL — which is the whole reason
     * "Find a doctor" could only ever return pharmacies.
     *
     * ─────────────────────────────────────────────────────────────────────
     * WHY IT IS THIS NARROW
     * ─────────────────────────────────────────────────────────────────────
     *
     * A directory listing is a hospital's public identity. Moving one to a
     * different tenant hands that identity over, so this only ever fills a
     * NULL and never repoints a listing that is already joined, even when the
     * claimant plainly belongs elsewhere. A contested listing is a dispute for
     * a human — exactly as `submitDirectoryClaim()` already treats a second
     * claimant.
     *
     * Three more refusals, each of which would otherwise be a guess:
     *
     *  - `facilityIdsFor()` returns NULL for a platform-tier admin, meaning
     *    "unrestricted". An admin approving on someone's behalf has no
     *    facility of their own to lend, and reading that NULL as a target
     *    would link the listing to nothing, or to anything.
     *  - A claimant holding postings at several facilities has not told us
     *    which one the listing is; taking the first is the `Facility::value('id')`
     *    bug this service's own docblock was written about.
     *  - A facility that already has a listing does not get a second one.
     *    That is how duplicate rows in this table got here.
     *
     * In every refusal the claim still approves and the listing is still
     * stamped as claimed; only the join is withheld, and the audit row names
     * the reason and asks for review — so an admin can finish it by hand
     * rather than wonder why nothing happened.
     */
    /**
     * Join a listing to a facility because an ADMIN said so.
     *
     * The claim path (below) can only finish a link when a claim exists to
     * finish. Every listing that predates the claim flow — including, on
     * production, the only hospital in the register that has a doctor — has
     * no claim and never will, so without this there is no way to link one at
     * all: not a screen, not a command, nothing. The gap was invisible because
     * the symptom appears somewhere else entirely, in a patient's empty
     * "Find a doctor".
     *
     * The admin names both sides, so the "which of this person's postings did
     * they mean" question does not arise. The two refusals that are about the
     * data rather than the actor still do:
     *
     *   - a listing that is already joined is never repointed, because a
     *     directory listing is a hospital's public identity;
     *   - a facility does not get a second listing, because that is how the
     *     duplicate rows in this table got here.
     *
     * @throws \Exception LISTING_ALREADY_LINKED | FACILITY_ALREADY_LISTED
     */
    public function linkListingToFacility(string $listingId, string $facilityId, $adminId): CareFacility
    {
        $listing = CareFacility::findOrFail($listingId);

        if ($listing->facility_id !== null) {
            throw new \Exception('LISTING_ALREADY_LINKED');
        }

        // findOrFail so a bad id is a 404, not a silent link to nothing.
        $facility = Facility::findOrFail($facilityId);

        if (CareFacility::where('facility_id', $facility->id)->whereKeyNot($listing->id)->exists()) {
            throw new \Exception('FACILITY_ALREADY_LISTED');
        }

        DB::transaction(function () use ($listing, $facility, $adminId): void {
            $listing->update(['facility_id' => $facility->id]);

            FacilityUpdateAudit::create([
                'facility_id'     => $listing->id,
                'actor_id'        => $adminId,
                'actor_type'      => 'admin',
                'field_changed'   => 'facility_id',
                'old_value'       => null,
                'new_value'       => (string) $facility->id,
                'source'          => 'admin_directory_link',
                'requires_review' => false,
                'created_at'      => now(),
            ]);
        });

        return $listing->refresh();
    }

    /**
     * Unlinked listings that have an unambiguous partner waiting.
     *
     * Deliberately NOT a facility picker. A free search over 346 tenants on a
     * screen whose whole job is deciding which hospital is which invites the
     * mis-click this method exists to prevent, so the desk only offers pairs
     * the data already agrees on: same name, and a facility that has no
     * listing yet. Anything less obvious than that is a judgement the desk
     * cannot help with and should not pretend to.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function unlinkedListingsWithCandidate(int $limit = 100)
    {
        return collect(DB::select('
            SELECT cf.id            AS listing_id,
                   cf.facility_name AS listing_name,
                   cf.city          AS listing_city,
                   cf.facility_type AS listing_type,
                   f.id             AS facility_id,
                   f.name           AS facility_name,
                   f.type           AS facility_type_tenant
              FROM care_facilities cf
              JOIN facilities f
                ON lower(trim(f.name)) = lower(trim(cf.facility_name))
             WHERE cf.facility_id IS NULL
               AND NOT EXISTS (SELECT 1 FROM care_facilities x WHERE x.facility_id = f.id)
             ORDER BY cf.facility_name
             LIMIT ?', [$limit]));
    }

    private function linkListingToClaimantFacility(CareFacility $listing, ?string $claimantId, $adminId): void
    {
        if ($listing->facility_id !== null || $claimantId === null) {
            return;
        }

        $claimant = User::find($claimantId);

        if (! $claimant) {
            return;
        }

        // NULL here means "platform tier / unrestricted", not "every facility".
        $facilityIds = app(FacilityMembershipService::class)->facilityIdsFor($claimant);

        $reason = match (true) {
            $facilityIds === null     => 'claimant_is_platform_tier',
            count($facilityIds) === 0 => 'claimant_holds_no_posting',
            count($facilityIds) > 1   => 'claimant_holds_several_postings',
            default                   => null,
        };

        $target = $reason === null ? $facilityIds[0] : null;

        if ($target !== null && CareFacility::where('facility_id', $target)->whereKeyNot($listing->id)->exists()) {
            $reason = 'facility_already_has_a_listing';
            $target = null;
        }

        if ($target === null) {
            FacilityUpdateAudit::create([
                'facility_id'     => $listing->id,
                'actor_id'        => $adminId,
                'actor_type'      => 'admin',
                'field_changed'   => 'facility_id',
                'old_value'       => null,
                'new_value'       => null,
                'source'          => 'facility_claim_approval_unlinked:' . $reason,
                'requires_review' => true,
                'created_at'      => now(),
            ]);

            return;
        }

        $listing->update(['facility_id' => $target]);

        FacilityUpdateAudit::create([
            'facility_id'     => $listing->id,
            'actor_id'        => $adminId,
            'actor_type'      => 'admin',
            'field_changed'   => 'facility_id',
            'old_value'       => null,
            'new_value'       => $target,
            'source'          => 'facility_claim_approval',
            'requires_review' => false,
            'created_at'      => now(),
        ]);
    }

    /**
     * Create or activate a care_facilities listing from a facility_registry entry.
     * Called on registry claim approval. Lookup order:
     *
     *   1. Existing listing already linked to this operational Facility (facility_id match).
     *   2. Unlinked stub created by CareMapRegistryStubSeeder (name + city + 'CM', facility_id IS NULL).
     *   3. Create a brand-new listing from the registry entry data.
     *
     * This prevents duplicate listings when a stub was pre-seeded before the facility claimed itself.
     */
    private function upsertCareFacilityFromRegistry(
        FacilityRegistry $reg,
        string $facilityId,
        ?string $partnerId
    ): CareFacility {
        // 1. Already linked to this operational Facility?
        $existing = CareFacility::where('facility_id', $facilityId)->first();

        // 2. Unlinked stub from CareMapRegistryStubSeeder (facility_id IS NULL)?
        if (!$existing) {
            $existing = CareFacility::where('facility_name', $reg->name)
                ->where('city', $reg->city)
                ->where('country_code', 'CM')
                ->whereNull('facility_id')
                ->first();
        }

        if ($existing) {
            $existing->update([
                'facility_id'    => $facilityId,   // link stub → operational Facility
                'partner_id'     => $partnerId,
                'listing_status' => 'active',
            ]);

            return $existing;
        }

        // 3. No stub exists — create a new listing from the registry entry
        return CareFacility::create([
            'facility_id'            => $facilityId,
            'partner_id'             => $partnerId,
            'facility_name'          => $reg->name,
            'facility_type'          => $reg->type,
            'ownership_type'         => $reg->ownership,
            'country_code'           => 'CM',
            'region'                 => $reg->region,
            'city'                   => $reg->city ?? '',
            'address'                => $reg->address ?? (($reg->city ?? '') . ', Cameroon'),
            'latitude'               => $reg->gps_lat,
            'longitude'              => $reg->gps_lng,
            'phone_primary'          => $reg->phone ?? CareFacility::PHONE_PLACEHOLDER,
            'email'                  => $reg->email,
            'website'                => $reg->website,
            'listing_status'         => 'active',
            'last_profile_update_at' => now(),
        ]);
    }
}
