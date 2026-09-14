<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\CareMap\FacilityListingController;
use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Modules\CareMap\Services\FacilityListingEditService;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Services\CareMap\ListingHoursService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "My pharmacy" — the one page where the person who answers for a pharmacy
 * sets up the facility itself: identity, position on the map, opening hours,
 * contacts.
 *
 * ── Two records, one pharmacy ──────────────────────────────────────────────
 * The session tenant is a `facilities` row (what the staff log in to). What
 * patients see is the `care_facilities` listing linked to it by
 * `care_facilities.facility_id`. Only 468 of 482 tenants have that link and
 * this controller never creates or links a listing: an unlinked tenant sees
 * an explanation and a pointer to the claim flow, and any write is refused
 * with 404 (there is no listing to write to). Linking is an admin decision
 * (admin.care-map.review.listings.link).
 *
 * ── Who ──────────────────────────────────────────────────────────────────
 * `capability:service_catalogue` on the routes; the facility comes from
 * PortalContextService only. No id in any URL or body is ever read.
 *
 * ── Name, address, coordinates change what the directory says ────────────
 * Decision, on reading the schema: `listing_status` has no "pending review"
 * value (active/draft/closed/suspended/archived) and taking a pharmacy out of
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
class PharmacySettingsController extends Controller
{
    /** verification_status values that mean somebody other than the facility checked. */
    private const VERIFIED_STATES = ['license_verified', 'government_verified'];

    public function __construct(
        private readonly PortalContextService $context,
        private readonly FacilityListingEditService $editor,
        private readonly ListingHoursService $hours,
        private readonly PharmacyStockReportService $report,
    ) {
    }

    public function index(): View
    {
        $facility = $this->facility();
        $listing  = $this->listingFor($facility);
        $listing?->load('hours');

        return view('portals.pharmacy.settings.index', [
            'facility'  => $facility,
            'listing'   => $listing,
            'issues'    => $this->report->listingIssues($listing),
            'hours'     => $listing ? $listing->hours->keyBy('day_of_week') : collect(),
            'dayLabels' => FacilityListingController::dayLabels(),
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $facility = $this->facility();
        $listing  = $this->listingOrFail($facility);

        $validated = $request->validate([
            'facility_name'   => ['required', 'string', 'max:150'],
            'license_number'  => ['nullable', 'string', 'max:80'],
            'phone_primary'   => ['nullable', 'string', 'max:40'],
            'phone_secondary' => ['nullable', 'string', 'max:40'],
            'email'           => ['nullable', 'email', 'max:150'],
            'website'         => ['nullable', 'url', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
        ]);

        $reviewed = $this->writeReviewed($listing, $facility, [
            'facility_name'  => $validated['facility_name'],
            'license_number' => $validated['license_number'] ?? null,
        ]);

        $contact = $this->editor->updateContact($listing, $validated, Auth::id());

        $count = count($reviewed) + count($contact);

        return redirect()
            ->route('portals.pharmacy.settings.index')
            ->with('success', $count === 0
                ? __('pharmacy_settings.flash_no_changes')
                : __('pharmacy_settings.flash_saved', ['count' => $count]));
    }

    public function updateLocation(Request $request): RedirectResponse
    {
        $facility = $this->facility();
        $listing  = $this->listingOrFail($facility);

        $validated = $request->validate([
            'address'   => ['required', 'string', 'max:255'],
            'city'      => ['required', 'string', 'max:120'],
            'region'    => ['nullable', 'string', 'max:120'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        $hasCoords = isset($validated['latitude']) && $validated['latitude'] !== ''
            && isset($validated['longitude']) && $validated['longitude'] !== '';

        $reviewed = $this->writeReviewed($listing, $facility, [
            'address'   => $validated['address'],
            'city'      => $validated['city'],
            'region'    => $validated['region'] ?? null,
            'latitude'  => $hasCoords ? round((float) $validated['latitude'], 8) : null,
            'longitude' => $hasCoords ? round((float) $validated['longitude'], 8) : null,
        ]);

        return redirect()
            ->route('portals.pharmacy.settings.index')
            ->with('success', $reviewed === []
                ? __('pharmacy_settings.flash_no_changes')
                : __('pharmacy_settings.flash_saved', ['count' => count($reviewed)]));
    }

    public function updateHours(Request $request): RedirectResponse
    {
        $listing = $this->listingOrFail($this->facility());

        $count = $this->hours->updateFromRequest($request, $listing, Auth::id());

        return redirect()
            ->route('portals.pharmacy.settings.index')
            ->with('success', __('pharmacy_settings.flash_hours_saved', ['count' => $count]));
    }

    // ── Resolution ──────────────────────────────────────────────────────────

    private function facility(): Facility
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_ops.no_facility_context'));
        }

        return Facility::findOrFail($facilityId);
    }

    private function listingFor(Facility $facility): ?CareFacility
    {
        return CareFacility::where('facility_id', $facility->id)->first();
    }

    /** A write with no linked listing has nothing to write to: 404, not a create. */
    private function listingOrFail(Facility $facility): CareFacility
    {
        $listing = $this->listingFor($facility);

        abort_if($listing === null, 404, __('pharmacy_settings.not_linked_title'));

        return $listing;
    }

    // ── Writes that change what the directory says ──────────────────────────

    /**
     * Write the fields that alter the public card, audit each one with
     * `requires_review = true`, drop a verified listing to `self_reported`,
     * and keep the tenant's name/licence in step.
     *
     * @param  array<string,mixed> $fields
     * @return list<string> the fields that actually changed
     */
    private function writeReviewed(CareFacility $listing, Facility $facility, array $fields): array
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
            $audits[] = [
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => Auth::id(),
                'actor_type'      => 'facility',
                'field_changed'   => $field,
                'old_value'       => $old === null ? null : (string) $old,
                'new_value'       => $new === null ? null : (string) $new,
                'source'          => FacilityListingEditService::SOURCE,
                'requires_review' => true,
                'created_at'      => now(),
            ];
        }

        if ($changes === []) {
            return [];
        }

        if (in_array($listing->verification_status, self::VERIFIED_STATES, true)) {
            $audits[] = [
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => Auth::id(),
                'actor_type'      => 'facility',
                'field_changed'   => 'verification_status',
                'old_value'       => $listing->verification_status,
                'new_value'       => 'self_reported',
                'source'          => FacilityListingEditService::SOURCE,
                'requires_review' => true,
                'created_at'      => now(),
            ];
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
}
