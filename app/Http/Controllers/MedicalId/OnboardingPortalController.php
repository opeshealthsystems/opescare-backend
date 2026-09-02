<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityGoLiveReadiness;
use App\Modules\FacilityReadiness\Services\FacilityGoLiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Facility Onboarding & Go-Live Portal Controller
 *
 * Provides the admin portal UI for tracking and managing facility onboarding,
 * go-live readiness checklists, and approvals.
 */
class OnboardingPortalController extends Controller
{
    public function __construct(private readonly FacilityGoLiveService $goLiveService) {}

    // -------------------------------------------------------------------------
    // Index — list all facilities with readiness status
    // -------------------------------------------------------------------------

    public function index(): View
    {
        $facilities = Facility::all();
        $readinessMap = [];

        foreach ($facilities as $facility) {
            $readinessMap[$facility->id] = $this->goLiveService->getOrCreateReadiness($facility->id);
        }

        $stats = [
            'total'             => $facilities->count(),
            'approved'          => collect($readinessMap)->where('status', 'approved')->count(),
            'ready_for_approval'=> collect($readinessMap)->where('status', 'ready_for_approval')->count(),
            'pending'           => collect($readinessMap)->where('status', 'pending')->count(),
        ];

        return view('portals.admin.onboarding.index', compact('facilities', 'readinessMap', 'stats'));
    }

    // -------------------------------------------------------------------------
    // Show — facility onboarding checklist detail
    // -------------------------------------------------------------------------

    public function show(Facility $facility): View
    {
        $readiness    = $this->goLiveService->getOrCreateReadiness($facility->id);
        $labels       = $this->goLiveService->checklistLabels();
        $missingItems = $this->goLiveService->missingItems($readiness);
        $risks        = $this->goLiveService->risks($readiness);
        $checklist    = $readiness->checklist_json ?? [];

        $completedCount = collect($checklist)->filter(fn ($v) => $v === true)->count();
        $totalCount     = count($labels);

        return view('portals.admin.onboarding.show', compact(
            'facility', 'readiness', 'labels', 'missingItems', 'risks',
            'checklist', 'completedCount', 'totalCount'
        ));
    }

    // -------------------------------------------------------------------------
    // Mark checklist item
    // -------------------------------------------------------------------------

    public function markItem(Request $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validate([
            'item'     => 'required|string',
            'complete' => 'required|boolean',
        ]);

        $readiness = $this->goLiveService->getOrCreateReadiness($facility->id, $this->demoActorId());

        try {
            $this->goLiveService->markItem(
                $readiness,
                $validated['item'],
                (bool) $validated['complete'],
                $this->demoActorId()
            );
        } catch (\InvalidArgumentException) {
            return back()->with('error', __('flash.checklist_item_unknown'));
        }

        return back()->with('success', __('flash.checklist_item_updated'));
    }

    // -------------------------------------------------------------------------
    // Approve go-live
    // -------------------------------------------------------------------------

    public function approve(Request $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validate([
            'approval_note' => 'required|string|max:2000',
        ]);

        $readiness = $this->goLiveService->getOrCreateReadiness($facility->id, $this->demoActorId());

        try {
            $this->goLiveService->approveGoLive($readiness, $this->demoActorId(), $validated['approval_note']);
        } catch (\RuntimeException $e) {
            return back()->with('error', __('flash.onboarding_incomplete'));
        }

        return redirect()
            ->route('portals.admin.onboarding.show', $facility)
            ->with('success', __('flash.onboarding_golive_approved', ['name' => $facility->name]));
    }

    // -------------------------------------------------------------------------

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }
}
