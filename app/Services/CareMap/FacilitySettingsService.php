<?php

namespace App\Services\CareMap;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Modules\CareMap\Services\FacilityListingEditService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "My facility" — the one service behind every settings page where the person
 * who answers for a facility sets up the facility itself: identity, position
 * on the map, opening hours, contacts. The pharmacy page (/pharmacy/settings)
 * and the page every other facility type shares (/facility/settings) are two
 * thin controllers over this one class, so the rules below hold for a hospital
 * exactly as they hold for a pharmacy.
 *
 * ── Two records, one facility ──────────────────────────────────────────────
 * The session tenant is a `facilities` row (what the staff log in to). What
 * patients see is the `care_facilities` listing linked to it by
 * `care_facilities.facility_id`. Only 468 of 482 tenants have that link and
 * this service never creates or links a listing: an unlinked tenant is
 * explained to, and any write is refused with 404 (there is no listing to
 * write to). Linking is an admin decision (admin.care-map.review.listings.link).
 *
 * ── Who ──────────────────────────────────────────────────────────────────
 * `capability:service_catalogue` on the routes; the facility comes from
 * PortalContextService only. No id in any URL or body is ever read.
 *
 * ── Name, address, coordinates change what the directory says ────────────
 * Decision, on reading the schema: `listing_status` has no "pending review"
 * value (active/draft/closed/suspended/archived) and taking a facility out of
 * `active` would remove it from every search the moment its manager fixed a
 * typo — the opposite of what this page is for. `verification_status` does
 * have the right vocabulary (unverified / self_reported / license_verified /
 * government_verified), and every row in the directory is `unverified` today
 * (FacilityClaimService never writes it). So:
 *
 *   - name, licence, address, city, region, latitude, longitude are written
 *     to the listing directly and stay visible;
 *   - if the listing had been verified (license_verified /
 *     government_verified), a change to name/licence/address/coordinates
 *     drops it to `self_reported` — the schema's own word for "the facility
 *     said so, nobody has checked" — so a renamed facility is never shown as
 *     verified on the facility's say-so;
 *   - every such change is audited in `facility_update_audits` with
 *     `requires_review = true` (contact and hours edits keep the existing
 *     `false`), which is the review queue an admin can read.
 *
 * `facilities.name` and `facilities.license_number` FOLLOW the listing: they
 * are the same institution, and the tenant name is what the staff see on
 * every portal page. Address and coordinates exist only on the listing.
 */
class FacilitySettingsService
{
    /** verification_status values that mean somebody other than the facility checked. */
    public const VERIFIED_STATES = ['license_verified', 'government_verified'];

    /**
     * Listing issues, with the SAME string values as
     * PharmacyStockReportService::ISSUE_* so the shared settings partials read
     * one vocabulary whichever controller filled `$issues`.
     */
    public const ISSUE_UNLINKED       = 'unlinked';
    public const ISSUE_NO_COORDINATES = 'no_coordinates';
    public const ISSUE_NOT_LISTED     = 'not_listed';

    public function __construct(
        private readonly PortalContextService $context,
        private readonly FacilityListingEditService $editor,
        private readonly ListingHoursService $hours,
    ) {
    }

    // ── Resolution ──────────────────────────────────────────────────────────

    /**
     * The session tenant. 403 when the account has no facility context — there
     * is no safe default for "whose facility is this".
     */
    public function facility(): Facility
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_ops.no_facility_context'));
        }

        // The session facility is already authorised context. Facility carries
        // the demo-partition scope (IsDemoRecord); on the demo-mode host a real
        // tenant is is_demo=false and findOrFail() 404ed the whole page for it.
        // Lifting that one scope for an id we already trust is not a leak.
        return Facility::withoutGlobalScope('isolate_demo')->findOrFail($facilityId);
    }

    public function listingFor(Facility $facility): ?CareFacility
    {
        return CareFacility::where('facility_id', $facility->id)->first();
    }

    /** A write with no linked listing has nothing to write to: 404, not a create. */
    public function listingOrFail(Facility $facility): CareFacility
    {
        $listing = $this->listingFor($facility);

        abort_if($listing === null, 404, __('facility_settings.not_linked_title'));

        return $listing;
    }

    /** @return list<string> */
    public function listingIssues(?CareFacility $listing): array
    {
        if (! $listing) {
            return [self::ISSUE_UNLINKED];
        }

        $issues = [];

        if ($listing->listing_status !== 'active') {
            $issues[] = self::ISSUE_NOT_LISTED;
        }

        if ($listing->latitude === null || $listing->longitude === null) {
            $issues[] = self::ISSUE_NO_COORDINATES;
        }

        return $issues;
    }

    /**
     * The facility type the page speaks as: the public listing's word when
     * linked, else the tenant's. `lab` is the tenant spelling of `laboratory`.
     */
    public function typeOf(Facility $facility, ?CareFacility $listing): string
    {
        $type = $listing?->facility_type ?: $facility->type;

        return match ($type) {
            'lab'   => 'laboratory',
            null, '' => 'other',
            default => (string) $type,
        };
    }

    // ── Validation rules (one place, two controllers) ───────────────────────

    /** @return array<string,array<int,string>> */
    public static function identityRules(): array
    {
        return [
            // No angle brackets in fields the public profile prints into
            // JSON-LD and page titles (security scan 2026-09-15, item 2).
            'facility_name'   => ['required', 'string', 'max:150', 'not_regex:/[<>]/'],
            'license_number'  => ['nullable', 'string', 'max:80'],
            'phone_primary'   => ['nullable', 'string', 'max:40'],
            'phone_secondary' => ['nullable', 'string', 'max:40'],
            'email'           => ['nullable', 'email', 'max:150'],
            'website'         => ['nullable', 'url', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string,array<int,string>> */
    public static function locationRules(): array
    {
        return [
            'address'   => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'city'      => ['required', 'string', 'max:120', 'not_regex:/[<>]/'],
            'region'    => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    // ── Writes ──────────────────────────────────────────────────────────────

    /**
     * Identity (reviewed) + contacts (not reviewed). Returns the number of
     * fields that actually changed.
     *
     * @param  array<string,mixed> $validated  output of identityRules()
     */
    public function updateIdentity(Facility $facility, CareFacility $listing, array $validated, string $actorId): int
    {
        $reviewed = $this->writeReviewed($listing, $facility, [
            'facility_name'  => $validated['facility_name'],
            'license_number' => $validated['license_number'] ?? null,
        ], $actorId);

        $contact = $this->editor->updateContact($listing, $validated, $actorId);

        return count($reviewed) + count($contact);
    }

    /**
     * Address + map position (all reviewed). Returns the number of fields that
     * actually changed.
     *
     * @param  array<string,mixed> $validated  output of locationRules()
     */
    public function updateLocation(Facility $facility, CareFacility $listing, array $validated, string $actorId): int
    {
        $hasCoords = isset($validated['latitude']) && $validated['latitude'] !== ''
            && isset($validated['longitude']) && $validated['longitude'] !== '';

        return count($this->writeReviewed($listing, $facility, [
            'address'   => $validated['address'],
            'city'      => $validated['city'],
            'region'    => $validated['region'] ?? null,
            'latitude'  => $hasCoords ? round((float) $validated['latitude'], 8) : null,
            'longitude' => $hasCoords ? round((float) $validated['longitude'], 8) : null,
        ], $actorId));
    }

    /** Opening hours; returns the number of days stored. Validates like `$request->validate()`. */
    public function updateHours(Request $request, CareFacility $listing, string $actorId): int
    {
        return $this->hours->updateFromRequest($request, $listing, $actorId);
    }

    /**
     * Write the fields that alter the public card, audit each one with
     * `requires_review = true`, drop a verified listing to `self_reported`,
     * and keep the tenant's name/licence in step.
     *
     * @param  array<string,mixed> $fields
     * @return list<string> the fields that actually changed
     */
    private function writeReviewed(CareFacility $listing, Facility $facility, array $fields, string $actorId): array
    {
        $changes = [];
        $audits  = [];

        foreach ($fields as $field => $new) {
            $old = $listing->{$field};

            if (is_string($new)) {
                $new = trim($new) === '' ? null : trim($new);
            }

            $same = in_array($field, ['latitude', 'longitude'], true)
                ? ($old === null && $new === null) || ($old !== null && $new !== null && abs((float) $old - (float) $new) < 1e-7)
                : (string) $old === (string) $new;

            if ($same) {
                continue;
            }

            $changes[$field] = $new;
            $audits[] = $this->audit($listing, $actorId, $field, $old, $new);
        }

        if ($changes === []) {
            return [];
        }

        if (in_array($listing->verification_status, self::VERIFIED_STATES, true)) {
            $audits[] = $this->audit($listing, $actorId, 'verification_status', $listing->verification_status, 'self_reported');
            $changes['verification_status'] = 'self_reported';
        }

        DB::transaction(function () use ($listing, $facility, $changes, $audits) {
            $listing->forceFill($changes + ['last_profile_update_at' => now()])->save();
            DB::table('facility_update_audits')->insert($audits);

            // The tenant follows the listing on the two fields both tables hold.
            $tenant = [];
            if (array_key_exists('facility_name', $changes)) {
                $tenant['name'] = $changes['facility_name'];
            }
            if (array_key_exists('license_number', $changes)) {
                $tenant['license_number'] = $changes['license_number'];
            }

            if ($tenant !== []) {
                $facility->forceFill($tenant)->save();
            }
        });

        return array_keys(array_diff_key($changes, ['verification_status' => 1]));
    }

    /** @return array<string,mixed> one facility_update_audits row */
    private function audit(CareFacility $listing, string $actorId, string $field, mixed $old, mixed $new): array
    {
        return [
            'id'              => (string) Str::uuid(),
            'facility_id'     => $listing->id,
            'actor_id'        => $actorId,
            'actor_type'      => 'facility',
            'field_changed'   => $field,
            'old_value'       => $old === null ? null : (string) $old,
            'new_value'       => $new === null ? null : (string) $new,
            'source'          => FacilityListingEditService::SOURCE,
            'requires_review' => true,
            'created_at'      => now(),
        ];
    }
}
