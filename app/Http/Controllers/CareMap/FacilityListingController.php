<?php

namespace App\Http\Controllers\CareMap;

use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Modules\CareMap\Services\FacilityListingEditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Self-service editing of a directory listing by its approved claimant.
 *
 * ── How the facility is resolved ────────────────────────────────────────────
 *
 * From the approved claim on the authenticated user, and from nothing else.
 * Note that not one route in this controller takes a facility id: there is no
 * `{facility}` to tamper with, no `facility_id` read from the body, no session
 * key, no "first facility" fallback. A request either belongs to a user with an
 * approved claim — in which case `listing()` finds exactly the one listing that
 * claim names — or it 403s.
 *
 * This is deliberate. A clerk was once able to publish another hospital's blood
 * stock because the facility came from `Facility::value('id')`; any lookup that
 * can succeed without naming the actor has that bug latent in it. Sub-resource
 * ids that must appear in a URL (a service line) are re-scoped to the resolved
 * listing before they are touched, so an id from another facility resolves to
 * nothing rather than to somebody else's row.
 */
class FacilityListingController extends Controller
{
    public function __construct(
        private FacilityClaimService $claims,
        private FacilityListingEditService $editor,
    ) {
    }

    /**
     * The listing this request is allowed to act on. The only source of truth
     * for that question in the whole controller.
     */
    private function listing(): CareFacility
    {
        $listing = $this->claims->approvedListingFor(Auth::id());

        abort_if($listing === null, 403, 'No approved facility claim for this account.');

        return $listing;
    }

    public function edit(): View
    {
        $listing = $this->claims->approvedListingFor(Auth::id());

        // No approved claim is not an error — it is the normal state for
        // someone who has just found this page. Show them how to get one.
        if ($listing === null) {
            return view('care_map.listing.none', [
                'claims' => $this->claims->claimsFor(Auth::id()),
            ]);
        }

        $listing->load(['services', 'hours']);

        return view('care_map.listing.edit', [
            'listing'  => $listing,
            'services' => $listing->services->sortBy('service_category')->values(),
            'hours'    => $listing->hours->keyBy('day_of_week'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $listing = $this->listing();

        $validated = $request->validate([
            'phone_primary'   => ['nullable', 'string', 'max:40'],
            'phone_secondary' => ['nullable', 'string', 'max:40'],
            'email'           => ['nullable', 'email', 'max:150'],
            'website'         => ['nullable', 'url', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
        ]);

        $changed = $this->editor->updateContact($listing, $validated, Auth::id());

        return redirect()
            ->route('portals.listing.edit')
            ->with('success', $changed === []
                ? __('caremap_claim.flash_no_changes')
                : __('caremap_claim.flash_profile_updated', ['count' => count($changed)]));
    }

    public function storeService(Request $request): RedirectResponse
    {
        $listing = $this->listing();

        $validated = $request->validate([
            'service_name'           => ['required', 'string', 'max:150'],
            'service_category'       => ['required', 'string', 'max:60'],
            'specialty'              => ['nullable', 'string', 'max:120'],
            'availability_status'    => ['required', 'string', 'max:40'],
            'appointment_required'   => ['nullable', 'boolean'],
            'walk_in_allowed'        => ['nullable', 'boolean'],
            'telemedicine_available' => ['nullable', 'boolean'],
        ]);

        $this->editor->addService($listing, $validated, Auth::id());

        return redirect()
            ->route('portals.listing.edit')
            ->with('success', __('caremap_claim.flash_service_added'));
    }

    public function destroyService(string $service): RedirectResponse
    {
        $listing = $this->listing();

        // Scoped inside the service: an id belonging to another facility
        // matches nothing here.
        $removed = $this->editor->removeService($listing, $service, Auth::id());

        return redirect()
            ->route('portals.listing.edit')
            ->with($removed ? 'success' : 'error', $removed
                ? __('caremap_claim.flash_service_removed')
                : __('caremap_claim.error_service_not_found'));
    }

    public function updateHours(Request $request): RedirectResponse
    {
        $listing = $this->listing();

        $request->validate([
            'hours'                => ['nullable', 'array'],
            'hours.*.opens_at'     => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at'    => ['nullable', 'date_format:H:i'],
        ]);

        $week = [];

        foreach (range(0, 6) as $day) {
            $spec = $request->input("hours.$day", []);

            $week[$day] = [
                'is_24_hours' => (bool) ($spec['is_24_hours'] ?? false),
                'is_closed'   => (bool) ($spec['is_closed'] ?? false),
                'opens_at'    => $spec['opens_at'] ?? null,
                'closes_at'   => $spec['closes_at'] ?? null,
            ];
        }

        $count = $this->editor->replaceHours($listing, $week, Auth::id());

        return redirect()
            ->route('portals.listing.edit')
            ->with('success', __('caremap_claim.flash_hours_updated', ['count' => $count]));
    }

    /** Day labels 0..6, Sunday first, matching care_facility_hours.day_of_week. */
    public static function dayLabels(): array
    {
        return [
            0 => __('caremap_claim.day_sunday'),
            1 => __('caremap_claim.day_monday'),
            2 => __('caremap_claim.day_tuesday'),
            3 => __('caremap_claim.day_wednesday'),
            4 => __('caremap_claim.day_thursday'),
            5 => __('caremap_claim.day_friday'),
            6 => __('caremap_claim.day_saturday'),
        ];
    }

    /** @return array<string,string> value => label */
    public static function serviceCategories(): array
    {
        return [
            'consultation'  => __('caremap_claim.cat_consultation'),
            'emergency'     => __('caremap_claim.cat_emergency'),
            'diagnostic'    => __('caremap_claim.cat_diagnostic'),
            'laboratory'    => __('caremap_claim.cat_laboratory'),
            'imaging'       => __('caremap_claim.cat_imaging'),
            'surgery'       => __('caremap_claim.cat_surgery'),
            'maternity'     => __('caremap_claim.cat_maternity'),
            'pharmacy'      => __('caremap_claim.cat_pharmacy'),
            'dental'        => __('caremap_claim.cat_dental'),
            'rehabilitation'=> __('caremap_claim.cat_rehabilitation'),
            'preventive'    => __('caremap_claim.cat_preventive'),
        ];
    }

    /** @return array<string,string> value => label */
    public static function availabilityStatuses(): array
    {
        return [
            'available'    => __('caremap_claim.avail_available'),
            'limited'      => __('caremap_claim.avail_limited'),
            'unavailable'  => __('caremap_claim.avail_unavailable'),
            'by_referral'  => __('caremap_claim.avail_by_referral'),
        ];
    }
}
