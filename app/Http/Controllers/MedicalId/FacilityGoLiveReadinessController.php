<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityReadinessScore;
use App\Modules\FacilityReadiness\Services\FacilityGoLiveService;
use App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Facility go-live readiness (score + checklist) for one facility.
 *
 * Imported from the platform's Api/V1/Admin/FacilityGoLiveReadinessController
 * (docs/PLATFORM_IMPORTS.md, group D). Finder adaptations:
 *  - web only, at /admin/facilities/{id}/readiness (platform tier). The JSON
 *    endpoints (store / markItem / approve / computeScore / latestScore) were
 *    not imported: the onboarding portal carries those writes on the web.
 *  - the platform's index() resolved the facility from
 *    `session('active_facility_id') ?? primary_facility_id ?? first facility`;
 *    here the facility is the route id only, and an unknown id is a 404 (so the
 *    `go_live_readiness_empty` view was not needed).
 *  - computeScore stores a FacilityReadinessScore snapshot from the Finder's
 *    real checks (FacilityReadinessScoringService); `computed_by` does not
 *    exist in the Finder schema and is dropped.
 */
class FacilityGoLiveReadinessController extends Controller
{
    public function show(Request $request, string $id, FacilityGoLiveService $service, FacilityReadinessScoringService $scoring): View
    {
        $facility  = $this->facility($id);
        $readiness = $service->getOrCreateReadiness($facility->id, $request->user()?->id);

        return view('portals.admin.go_live_readiness', [
            'facility'     => $facility,
            'readiness'    => $readiness,
            'checklist'    => $service->checklistOf($readiness),
            'missingItems' => $service->missingItems($readiness),
            'labels'       => $service->checklistLabels(),
            'automatic'    => $scoring->evaluate($facility->id),
            'latestScore'  => FacilityReadinessScore::latestFor($facility->id),
        ]);
    }

    public function computeScore(string $id, FacilityReadinessScoringService $scoring): RedirectResponse
    {
        $facility = $this->facility($id);
        $score = $scoring->calculateAndStore($facility->id);

        return redirect()->route('portals.admin.facilities.readiness', $facility->id)
            ->with('success', __('readiness.flash_score_computed', ['score' => (int) $score->overall_score]));
    }

    private function facility(string $id): Facility
    {
        abort_unless((bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id), 404);

        return Facility::withoutGlobalScope('isolate_demo')->findOrFail($id);
    }
}
