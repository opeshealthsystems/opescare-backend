<?php

namespace App\Modules\CareMap\Services;

use App\Enums\FacilityClaimStatus;
use App\Models\CareFacility;
use App\Models\FacilityClaim;
use App\Models\FacilityRegistry;
use App\Models\FacilityUpdateAudit;
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
     * It returns a claim in `submitted`. There is no argument, flag or code
     * path in this method that can return an approved one — see approveClaim.
     *
     * @param  array{claimant_name?:string,claimant_role?:string,claimant_email?:string,claimant_phone?:string,claim_reason?:string} $contact
     */
    public function submitDirectoryClaim(string $careFacilityId, string $userId, array $contact = []): FacilityClaim
    {
        $listing = CareFacility::findOrFail($careFacilityId);

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

        return FacilityClaim::create([
            'care_facility_id' => $listing->id,
            'facility_id'      => $listing->facility_id,   // may legitimately be null
            'claimant_user_id' => $userId,
            'claimant_name'    => $contact['claimant_name']  ?? null,
            'claimant_role'    => $contact['claimant_role']  ?? null,
            'claimant_email'   => $contact['claimant_email'] ?? null,
            'claimant_phone'   => $contact['claimant_phone'] ?? null,
            'claim_reason'     => $contact['claim_reason']   ?? null,
            'claim_status'     => FacilityClaimStatus::Submitted->value,
            'submitted_at'     => now(),
        ]);
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

        DB::transaction(function () use ($claim, $adminId) {
            $claim->update([
                'claim_status' => FacilityClaimStatus::Approved->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
            ]);

            if ($claim->care_facility_id) {
                // ── Listing claim flow (public) ───────────────────────────────
                $listing = CareFacility::find($claim->care_facility_id);

                if ($listing) {
                    $this->stampClaimed($listing, $claim->claimant_user_id, $adminId);
                }

                return;
            }

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
            // Find the care_facilities entry linked to the operational Facility
            // via care_facilities.facility_id (→ facilities.id)
            $careFacility = CareFacility::where('facility_id', $claim->facility_id)->first();

            if ($careFacility) {
                $this->stampClaimed($careFacility, $claim->claimant_user_id, $adminId);
            }
        });

        return $claim->fresh();
    }

    /**
     * Reject a claim request.
     */
    public function rejectClaim($claimId, $adminId, $notes = null)
    {
        $claim = FacilityClaim::findOrFail($claimId);

        $claim->update([
            'claim_status' => FacilityClaimStatus::Rejected->value,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

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
