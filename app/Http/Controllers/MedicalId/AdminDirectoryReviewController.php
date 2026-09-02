<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\FacilityClaimStatus;
use App\Enums\FacilityImportReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\FacilityClaim;
use App\Models\FacilityImportReview;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Modules\CareMap\Services\FacilityImportReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Directory Review — one desk for the two decisions a person has to make about
 * a facility in the Care Map:
 *
 *   1. "Does this person run this hospital?"   (facility_claims)
 *   2. "Is this candidate the same hospital?"  (facility_import_reviews)
 *
 * They are the same job — a human judging a claim about a real institution
 * that a machine must not settle on its own — so they share a screen. 439
 * import reviews have been pending since the OpenStreetMap run with no UI to
 * decide them, and claims could never auto-approve by design.
 *
 * Platform-tier only: the routes live under /admin/care-map, which
 * RequirePlatformAdmin::PLATFORM_ONLY_PREFIXES already covers, and the nav
 * links are wrapped in @platformadmin so the two cannot drift.
 */
class AdminDirectoryReviewController extends Controller
{
    public function __construct(
        private FacilityClaimService $claims,
        private FacilityImportReviewService $imports,
    ) {
    }

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'imports' ? 'imports' : 'claims';

        $pendingClaims = FacilityClaim::with(['careFacility', 'claimant', 'facility'])
            ->open()
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'claims_page')
            ->withQueryString();

        $decidedClaims = FacilityClaim::with(['careFacility', 'claimant', 'reviewer'])
            ->whereIn('claim_status', [
                FacilityClaimStatus::Approved->value,
                FacilityClaimStatus::Rejected->value,
                FacilityClaimStatus::Revoked->value,
            ])
            ->orderByDesc('reviewed_at')
            ->limit(10)
            ->get();

        // Which slice of the import queue. Defaults to the undecided ones —
        // the whole point of the screen — but a deferred row must stay
        // reachable or "defer" would be indistinguishable from "discard".
        $activeStatus = FacilityImportReviewStatus::tryFrom((string) $request->query('status'))
            ?? FacilityImportReviewStatus::Pending;

        $reviewQuery = FacilityImportReview::with(['matchedFacility', 'reviewer'])
            ->where('status', $activeStatus->value);

        if ($reason = $request->query('reason')) {
            $reviewQuery->where('reason', $reason);
        }

        $importReviews = $reviewQuery
            ->orderByRaw('match_score DESC NULLS LAST')
            ->orderBy('candidate_name')
            ->paginate(25, ['*'], 'imports_page')
            ->withQueryString();

        $reasonCounts = FacilityImportReview::query()
            ->where('status', $activeStatus->value)
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->orderByDesc('total')
            ->pluck('total', 'reason');

        $statusCounts = FacilityImportReview::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('portals.admin.directory_review', [
            'tab'             => $tab,
            'pendingClaims'   => $pendingClaims,
            'decidedClaims'   => $decidedClaims,
            'importReviews'   => $importReviews,
            'reasonCounts'    => $reasonCounts,
            'statusCounts'    => $statusCounts,
            'activeStatus'    => $activeStatus,
            'clusterCounts'   => $this->clusterCounts($importReviews->getCollection()),
            'osmAttribution'  => $this->attributionFor($importReviews->getCollection()),
            'activeReason'    => $request->query('reason'),
            'pendingClaimCount'  => $pendingClaims->total(),
            'pendingImportCount' => (int) ($statusCounts[FacilityImportReviewStatus::Pending->value] ?? 0),
        ]);
    }

    /**
     * How many still-open candidates point at each existing listing on this page.
     *
     * 59 of the pending rows sit in clusters of two to seven candidates that all
     * resolve to one facility. A reviewer who accepts them one at a time,
     * without being told the siblings exist, creates the duplicate the queue was
     * built to prevent — so the count is surfaced on every matched row.
     *
     * @param  \Illuminate\Support\Collection<int, FacilityImportReview>  $page
     * @return array<string, int>
     */
    private function clusterCounts($page): array
    {
        $ids = $page->pluck('matched_facility_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return FacilityImportReview::query()
            ->whereIn('matched_facility_id', $ids)
            ->whereIn('status', [
                FacilityImportReviewStatus::Pending->value,
                FacilityImportReviewStatus::Deferred->value,
            ])
            ->selectRaw('matched_facility_id, count(*) as total')
            ->groupBy('matched_facility_id')
            ->pluck('total', 'matched_facility_id')
            ->map(static fn ($total) => (int) $total)
            ->all();
    }

    /**
     * The licence notice the upstream data carries, read off the rows themselves.
     *
     * ODbL requires attribution wherever the derived data is shown, and this
     * screen shows raw OpenStreetMap tags. The string is taken from
     * `source_attribution` on the candidates rather than hardcoded in the
     * template, so a future importer for a differently-licensed source cannot
     * inherit OpenStreetMap's notice by accident.
     *
     * @param  \Illuminate\Support\Collection<int, FacilityImportReview>  $page
     */
    private function attributionFor($page): ?string
    {
        return $page->pluck('source_attribution')->filter()->unique()->implode(' · ') ?: null;
    }

    // ── Claims ──────────────────────────────────────────────────────────────

    public function approveClaim(string $claim): RedirectResponse
    {
        $this->claims->approveClaim($claim, Auth::id());

        return back()->with('success', __('caremap_claim.flash_claim_approved'));
    }

    public function rejectClaim(Request $request, string $claim): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->claims->rejectClaim($claim, Auth::id(), $validated['review_notes'] ?? null);

        return back()->with('success', __('caremap_claim.flash_claim_rejected'));
    }

    public function revokeClaim(Request $request, string $claim): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->claims->revokeClaim($claim, Auth::id(), $validated['review_notes'] ?? null);

        return back()->with('success', __('caremap_claim.flash_claim_revoked'));
    }

    // ── Import reviews ──────────────────────────────────────────────────────

    public function acceptImport(Request $request, string $review): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = FacilityImportReview::findOrFail($review);

        try {
            $this->imports->accept($model, Auth::id(), $validated['review_notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $this->importError($e->getMessage()));
        }

        return back()->with('success', __('caremap_claim.flash_import_accepted'));
    }

    public function mergeImport(Request $request, string $review): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = FacilityImportReview::findOrFail($review);

        try {
            $this->imports->merge($model, Auth::id(), $validated['review_notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $this->importError($e->getMessage()));
        }

        return back()->with('success', __('caremap_claim.flash_import_merged'));
    }

    /**
     * Park a candidate the reviewer cannot settle from a screen.
     *
     * Not a decision, and deliberately not an approval: nothing is created,
     * nothing is merged, and the row stays open. It only leaves the default
     * queue so the reviewer can reach the ones they *can* decide.
     */
    public function deferImport(Request $request, string $review): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = FacilityImportReview::findOrFail($review);

        try {
            $this->imports->defer($model, Auth::id(), $validated['review_notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $this->importError($e->getMessage()));
        }

        return back()->with('success', __('facility_review.flash_deferred'));
    }

    public function rejectImport(Request $request, string $review): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = FacilityImportReview::findOrFail($review);

        try {
            $this->imports->reject($model, Auth::id(), $validated['review_notes'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $this->importError($e->getMessage()));
        }

        return back()->with('success', __('caremap_claim.flash_import_rejected'));
    }

    private function importError(string $code): string
    {
        return match ($code) {
            'IMPORT_REVIEW_ALREADY_DECIDED' => __('caremap_claim.error_import_decided'),
            'IMPORT_REVIEW_NEEDS_NAME'      => __('caremap_claim.error_import_unnamed'),
            'IMPORT_REVIEW_NO_MATCH'        => __('caremap_claim.error_import_no_match'),
            default                         => __('caremap_claim.error_generic'),
        };
    }
}
