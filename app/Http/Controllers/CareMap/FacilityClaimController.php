<?php

namespace App\Http\Controllers\CareMap;

use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\FacilityClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The public claim flow: a facility representative finds their listing in the
 * Care Map and asks to manage it.
 *
 * Submitting produces a claim in `submitted` and nothing else. It does not
 * grant access, does not change what the listing shows, and above all does not
 * touch `verification_status` — see FacilityClaimService::approveClaim.
 */
class FacilityClaimController extends Controller
{
    public function __construct(private FacilityClaimService $claims)
    {
    }

    /** The claim form for one listing. */
    public function create(string $id): View|RedirectResponse
    {
        $listing = CareFacility::findOrFail($id);
        $user    = Auth::user();

        $existing = $this->claims->claimsFor($user->id)
            ->firstWhere('care_facility_id', $listing->id);

        return view('care_map.claim', [
            'facility' => $listing,
            'existing' => $existing,
            'locale'   => app()->getLocale(),
        ]);
    }

    public function store(Request $request, string $id): RedirectResponse
    {
        $listing = CareFacility::findOrFail($id);

        $validated = $request->validate([
            'claimant_name'  => ['required', 'string', 'max:150'],
            'claimant_role'  => ['required', 'string', 'max:64'],
            'claimant_email' => ['required', 'email', 'max:150'],
            'claimant_phone' => ['required', 'string', 'max:40'],
            'claim_reason'   => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->claims->submitDirectoryClaim($listing->id, Auth::id(), $validated);
        } catch (\Throwable $e) {
            $code = $e->getMessage();

            $message = match ($code) {
                'FACILITY_CLAIM_ALREADY_EXISTS' => __('caremap_claim.error_already_submitted'),
                'LISTING_ALREADY_CLAIMED'       => __('caremap_claim.error_already_claimed'),
                default                         => __('caremap_claim.error_generic'),
            };

            return redirect()
                ->route('public.care-map.claim', $listing->id)
                ->withInput()
                ->with('error', $message);
        }

        return redirect()
            ->route('portals.listing.claims')
            ->with('success', __('caremap_claim.flash_submitted'));
    }

    /** "Where has my claim got to?" — the claimant's own status page. */
    public function myClaims(): View
    {
        return view('care_map.listing.claims', [
            'claims'  => $this->claims->claimsFor(Auth::id()),
            'listing' => $this->claims->approvedListingFor(Auth::id()),
        ]);
    }
}
