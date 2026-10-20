<?php

namespace App\Http\Controllers\CareMap;

use App\Http\Controllers\Controller;
use App\Enums\FacilityClaimStatus;
use App\Models\CareFacility;
use App\Models\FacilityClaim;
use App\Modules\CareMap\Services\FacilityClaimProofService;
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
    public function __construct(
        private FacilityClaimService $claims,
        private FacilityClaimProofService $proof,
    ) {
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
            'document'       => FacilityClaimProofService::documentRules(),
        ]);

        $document = $request->file('document');
        unset($validated['document']);

        try {
            $this->claims->submitDirectoryClaim($listing->id, Auth::id(), $validated, $document);
        } catch (\Throwable $e) {
            $code = $e->getMessage();

            $message = match ($code) {
                'FACILITY_CLAIM_ALREADY_EXISTS' => __('caremap_claim.error_already_submitted'),
                'LISTING_ALREADY_CLAIMED'       => __('caremap_claim.error_already_claimed'),
                'DOCUMENT_REQUIRED', 'DOCUMENT_INVALID' => __('caremap_claim.error_document_invalid'),
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
        $claims = $this->claims->claimsFor(Auth::id());

        return view('care_map.listing.claims', [
            'claims'      => $claims,
            'listing'     => $this->claims->approvedListingFor(Auth::id()),
            // Only the claimant's own approved, not-yet-activated claims.
            'activations' => $claims
                ->filter(fn (FacilityClaim $c) => $c->activated_at === null)
                ->mapWithKeys(fn (FacilityClaim $c) => [$c->id => $this->claims->activationUrlFor($c)])
                ->filter(),
        ]);
    }

    /** The claimant types the code that was sent to the listing's phone. */
    public function verifyCode(Request $request, string $claim): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:12']]);
        $row       = $this->ownClaim($claim);

        try {
            $this->proof->verifyCode($row, $validated['code']);
        } catch (\Throwable $e) {
            return redirect()->route('portals.listing.claims')->with('error', $this->codeError($e->getMessage()));
        }

        return redirect()->route('portals.listing.claims')->with('success', __('caremap_claim.flash_code_verified'));
    }

    /** Send a fresh code to the listing's phone (cooldown + limits apply). */
    public function resendCode(string $claim): RedirectResponse
    {
        $row = $this->ownClaim($claim);

        try {
            $row = $this->proof->sendCode($row);
        } catch (\Throwable $e) {
            return redirect()->route('portals.listing.claims')->with('error', $this->codeError($e->getMessage()));
        }

        $message = $row->claim_status === FacilityClaimStatus::PendingVerification
            ? __('caremap_claim.flash_code_resent')
            : __('caremap_claim.flash_code_undeliverable');

        return redirect()->route('portals.listing.claims')->with('success', $message);
    }

    /**
     * The activation link from the approval notice (signed). Only the
     * claimant's own signed-in account can use it, and only once two-step
     * verification is enrolled: without it, the claimant is sent to enrol
     * first and then opens the link again.
     */
    public function activate(Request $request, string $claim): RedirectResponse
    {
        $row  = $this->ownClaim($claim);
        $user = Auth::user();

        try {
            $this->claims->activate($row, $user);
        } catch (\Throwable $e) {
            if ($e->getMessage() === 'MFA_REQUIRED') {
                $request->session()->put('url.intended', $request->fullUrl());
                $request->session()->put('mfa.user_id', $user->id);
                $request->session()->put('mfa.setup_required', true);

                return redirect()->route('mfa.challenge')->with('info', __('caremap_claim.flash_activate_needs_mfa'));
            }

            return redirect()->route('portals.listing.claims')->with('error', __('caremap_claim.error_activation'));
        }

        return redirect()->route('portals.listing.claims')->with('success', __('caremap_claim.flash_activated'));
    }

    /** A claim of the signed-in user, or 404 — never someone else's. */
    private function ownClaim(string $id): FacilityClaim
    {
        return FacilityClaim::query()
            ->whereKey($id)
            ->where('claimant_user_id', Auth::id())
            ->firstOrFail();
    }

    private function codeError(string $code): string
    {
        return match ($code) {
            'CODE_INVALID'           => __('caremap_claim.error_code_invalid'),
            'CODE_EXPIRED'           => __('caremap_claim.error_code_expired'),
            'CODE_TOO_MANY_ATTEMPTS' => __('caremap_claim.error_code_attempts'),
            'CODE_SEND_LIMIT'        => __('caremap_claim.error_code_send_limit'),
            'CODE_RESEND_TOO_SOON'   => __('caremap_claim.error_code_too_soon'),
            'CLAIM_NOT_AWAITING_CODE'=> __('caremap_claim.error_code_not_awaited'),
            default                  => __('caremap_claim.error_generic'),
        };
    }
}
