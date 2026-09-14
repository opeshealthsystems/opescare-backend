<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\CareMap\FacilityListingController;
use App\Http\Controllers\Controller;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Services\CareMap\FacilitySettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "My pharmacy" — the pharmacy's own settings page (routes/pharmacy_settings.php).
 *
 * A thin caller of FacilitySettingsService, which holds every rule: the
 * tenant-only resolution, the unlinked-tenant refusal, the reviewed writes
 * (name / licence / address / coordinates audited `requires_review`, a
 * verified listing dropped to `self_reported`), the tenant following the
 * listing, and the hours grid. The non-pharmacy page
 * (FacilitySettingsController, /facility/settings) calls the same service;
 * only the wording and the finder-specific issue report differ here.
 */
class PharmacySettingsController extends Controller
{
    public function __construct(
        private readonly FacilitySettingsService $settings,
        private readonly PharmacyStockReportService $report,
    ) {
    }

    public function index(): View
    {
        $facility = $this->settings->facility();
        $listing  = $this->settings->listingFor($facility);
        $listing?->load('hours');

        return view('portals.pharmacy.settings.index', [
            'facility'  => $facility,
            'listing'   => $listing,
            // The Medicine Finder's own report, so this page and the stock page
            // name the same problems in the same words.
            'issues'    => $this->report->listingIssues($listing),
            'hours'     => $listing ? $listing->hours->keyBy('day_of_week') : collect(),
            'dayLabels' => FacilityListingController::dayLabels(),
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $facility  = $this->settings->facility();
        $listing   = $this->settings->listingOrFail($facility);
        $validated = $request->validate(FacilitySettingsService::identityRules());

        $count = $this->settings->updateIdentity($facility, $listing, $validated, Auth::id());

        return $this->back($count === 0
            ? __('pharmacy_settings.flash_no_changes')
            : __('pharmacy_settings.flash_saved', ['count' => $count]));
    }

    public function updateLocation(Request $request): RedirectResponse
    {
        $facility  = $this->settings->facility();
        $listing   = $this->settings->listingOrFail($facility);
        $validated = $request->validate(FacilitySettingsService::locationRules());

        $count = $this->settings->updateLocation($facility, $listing, $validated, Auth::id());

        return $this->back($count === 0
            ? __('pharmacy_settings.flash_no_changes')
            : __('pharmacy_settings.flash_saved', ['count' => $count]));
    }

    public function updateHours(Request $request): RedirectResponse
    {
        $listing = $this->settings->listingOrFail($this->settings->facility());

        $count = $this->settings->updateHours($request, $listing, Auth::id());

        return $this->back(__('pharmacy_settings.flash_hours_saved', ['count' => $count]));
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('portals.pharmacy.settings.index')->with('success', $message);
    }
}
