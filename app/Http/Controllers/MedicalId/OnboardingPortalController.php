<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityGoLiveReadiness;
use App\Modules\FacilityReadiness\Services\FacilityGoLiveService;
use App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Facility Onboarding & Go-Live Portal Controller
 *
 * Provides the admin portal UI for tracking and managing facility onboarding,
 * go-live readiness checklists, and approvals.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group D). Finder adaptations:
 *  - mounted on /admin/onboarding (platform tier via RequirePlatformAdmin);
 *  - the index no longer creates a readiness row + audit event for EVERY
 *    facility on a page view (write-on-read over ~480 tenants): it paginates
 *    and reads the rows that exist; a row is created when a facility is opened;
 *  - facilities are looked up by uuid with demo isolation lifted (oversight
 *    must see real tenants on a demo-mode host); a non-uuid id is a 404;
 *  - the actor is the signed-in user id, not `session('auth_email')` (the
 *    readiness approved_by / audit actor columns are uuid);
 *  - each row shows the automatic Finder readiness score next to the checklist.
 */
class OnboardingPortalController extends Controller
{
    public function __construct(
        private readonly FacilityGoLiveService $goLiveService,
        private readonly FacilityReadinessScoringService $scoring,
    ) {}

    // -------------------------------------------------------------------------
    // Index — list all facilities with readiness status
    // -------------------------------------------------------------------------

    public function index(): View
    {
        $facilities = Facility::withoutGlobalScope('isolate_demo')->orderBy('name')->paginate(25);
        $readinessMap = FacilityGoLiveReadiness::whereIn('facility_id', collect($facilities->items())->pluck('id'))
            ->get()->keyBy('facility_id');

        $scores = [];
        foreach ($facilities as $facility) {
            $scores[$facility->id] = $this->scoring->evaluate($facility->id)['score'];
        }

        $approved = FacilityGoLiveReadiness::where('status', 'approved')->count();
        $ready    = FacilityGoLiveReadiness::where('status', 'ready_for_approval')->count();

        $stats = [
            'total'              => $facilities->total(),
            'approved'           => $approved,
            'ready_for_approval' => $ready,
            'pending'            => max(0, $facilities->total() - $approved - $ready),
        ];

        return view('portals.admin.onboarding.index', compact('facilities', 'readinessMap', 'scores', 'stats'));
    }

    // -------------------------------------------------------------------------
    // Show — facility onboarding checklist detail
    // -------------------------------------------------------------------------

    public function show(Request $request, string $facility): View
    {
        $facility     = $this->facility($facility);
        $readiness    = $this->goLiveService->getOrCreateReadiness($facility->id, $request->user()?->id);
        $labels       = $this->goLiveService->checklistLabels();
        $missingItems = $this->goLiveService->missingItems($readiness);
        $risks        = $this->goLiveService->risks($readiness);
        $checklist    = $this->goLiveService->checklistOf($readiness);
        $automatic    = $this->scoring->evaluate($facility->id);

        $completedCount = collect($checklist)->filter(fn ($v) => $v === true)->count();
        $totalCount     = count($labels);

        return view('portals.admin.onboarding.show', compact(
            'facility', 'readiness', 'labels', 'missingItems', 'risks',
            'checklist', 'completedCount', 'totalCount', 'automatic'
        ));
    }

    // -------------------------------------------------------------------------
    // Mark checklist item
    // -------------------------------------------------------------------------

    public function markItem(Request $request, string $facility): RedirectResponse
    {
        $facility = $this->facility($facility);

        $validated = $request->validate([
            'item'     => 'required|string',
            'complete' => 'required|boolean',
        ]);

        $readiness = $this->goLiveService->getOrCreateReadiness($facility->id, $request->user()->id);

        try {
            $this->goLiveService->markItem(
                $readiness,
                $validated['item'],
                (bool) $validated['complete'],
                $request->user()->id
            );
        } catch (\InvalidArgumentException) {
            return back()->with('error', __('onboarding.admin_portal.flash_item_unknown'));
        }

        return back()->with('success', __('onboarding.admin_portal.flash_item_updated'));
    }

    // -------------------------------------------------------------------------
    // Approve go-live
    // -------------------------------------------------------------------------

    public function approve(Request $request, string $facility): RedirectResponse
    {
        $facility = $this->facility($facility);

        $validated = $request->validate([
            'approval_note' => 'required|string|max:2000',
        ]);

        $readiness = $this->goLiveService->getOrCreateReadiness($facility->id, $request->user()->id);

        try {
            $this->goLiveService->approveGoLive($readiness, $request->user()->id, $validated['approval_note']);
        } catch (\RuntimeException $e) {
            return back()->with('error', __('onboarding.admin_portal.flash_incomplete'));
        }

        return redirect()
            ->route('portals.admin.onboarding.show', $facility->id)
            ->with('success', __('onboarding.admin_portal.flash_approved', ['name' => $facility->name]));
    }

    // -------------------------------------------------------------------------

    private function facility(string $id): Facility
    {
        abort_unless((bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id), 404);

        return Facility::withoutGlobalScope('isolate_demo')->findOrFail($id);
    }
}
