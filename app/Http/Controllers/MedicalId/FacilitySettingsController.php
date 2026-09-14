<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\CareMap\FacilityListingController;
use App\Http\Controllers\Controller;
use App\Services\CareMap\FacilityPhotoService;
use App\Services\CareMap\FacilitySettingsService;
use App\Support\StaffCapabilities;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "My facility" — the shared settings page for every facility that is not a
 * pharmacy (routes/facility_settings.php): hospitals, clinics, health
 * centres, laboratories, dental, imaging, eye clinics… The page is the same
 * for all of them; the title and icon follow the facility's type.
 *
 * A thin caller of FacilitySettingsService, which holds every rule (see its
 * docblock). The pharmacy page (PharmacySettingsController) calls the same
 * service, so a hospital director and a pharmacy manager are held to one
 * set of rules about what changes the public directory and how it is audited.
 *
 * ── Who ──────────────────────────────────────────────────────────────────
 * `capability:service_catalogue` — "answers for the institution" — held by
 * the facility admins, lab_manager and pharmacy_manager
 * (config/staff_capabilities.php). A doctor or a receptionist does not set
 * the hospital's public name or address. The facility is the session tenant
 * (PortalContextService); no id in any URL or body is ever read.
 */
class FacilitySettingsController extends Controller
{
    /** Lucide icon per public facility type; `other` for anything unmapped. */
    private const TYPE_ICONS = [
        'hospital'          => 'hospital',
        'clinic'            => 'stethoscope',
        'health_center'     => 'heart-pulse',
        'laboratory'        => 'flask-conical',
        'dental'            => 'smile',
        'imaging_center'    => 'scan',
        'eye_clinic'        => 'eye',
        'specialist'        => 'stethoscope',
        'diagnostic_center' => 'microscope',
        'maternity'         => 'baby',
        'dispensary'        => 'pill',
        'blood_bank'        => 'droplet',
        'pharmacy'          => 'store',
        'other'             => 'building-2',
    ];

    public function __construct(private readonly FacilitySettingsService $settings)
    {
    }

    public function index(): View
    {
        $facility = $this->settings->facility();
        $listing  = $this->settings->listingFor($facility);
        $listing?->load('hours');

        $type     = $this->settings->typeOf($facility, $listing);
        $titleKey = 'facility_settings.title_by_type.' . $type;
        $title    = __($titleKey);

        return view('portals.facility.settings.index', [
            'facility'  => $facility,
            'listing'   => $listing,
            'issues'    => $this->settings->listingIssues($listing),
            'hours'     => $listing ? $listing->hours->keyBy('day_of_week') : collect(),
            'dayLabels' => FacilityListingController::dayLabels(),
            'type'      => $type,
            'title'     => is_string($title) && $title !== $titleKey ? $title : __('facility_settings.title'),
            'typeIcon'  => self::TYPE_ICONS[$type] ?? self::TYPE_ICONS['other'],
            'homeUrl'   => $this->homeUrl($facility->id),
            'photoUrl'  => FacilityPhotoService::urlFor($listing),
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $facility  = $this->settings->facility();
        $listing   = $this->settings->listingOrFail($facility);
        $validated = $request->validate(FacilitySettingsService::identityRules());

        $count = $this->settings->updateIdentity($facility, $listing, $validated, Auth::id());

        return $this->back($count === 0
            ? __('facility_settings.flash_no_changes')
            : __('facility_settings.flash_saved', ['count' => $count]));
    }

    public function updateLocation(Request $request): RedirectResponse
    {
        $facility  = $this->settings->facility();
        $listing   = $this->settings->listingOrFail($facility);
        $validated = $request->validate(FacilitySettingsService::locationRules());

        $count = $this->settings->updateLocation($facility, $listing, $validated, Auth::id());

        return $this->back($count === 0
            ? __('facility_settings.flash_no_changes')
            : __('facility_settings.flash_saved', ['count' => $count]));
    }

    public function updateHours(Request $request): RedirectResponse
    {
        $listing = $this->settings->listingOrFail($this->settings->facility());

        $count = $this->settings->updateHours($request, $listing, Auth::id());

        return $this->back(__('facility_settings.flash_hours_saved', ['count' => $count]));
    }

    /** The facility photo / logo — see FacilityPhotoService for the storage contract. */
    public function updatePhoto(Request $request, FacilityPhotoService $photos): RedirectResponse
    {
        $listing = $this->settings->listingOrFail($this->settings->facility());
        $request->validate(FacilityPhotoService::rules());

        if (! $photos->store($listing, $request->file('photo'), Auth::id())) {
            return redirect()->route('portals.facility.settings.index')
                ->withErrors(['photo' => __('facility_settings.photo_not_an_image')]);
        }

        return $this->back(__('facility_settings.flash_photo_saved'));
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('portals.facility.settings.index')->with('success', $message);
    }

    /**
     * Where the breadcrumb "home" points. The page straddles two portals: a
     * facility administrator lands on /admin, a lab (or pharmacy) manager on
     * the operations desk at /facility. Sending an admin to /facility would
     * only bounce them back through portal.access.
     */
    private function homeUrl(string $facilityId): string
    {
        $profile = StaffCapabilities::roleModelFor(Auth::user(), $facilityId)?->dashboard_profile_key;

        return in_array($profile, ['facility_admin', 'facility_executive', 'multi_facility_admin'], true)
            ? route('portals.admin')
            : route('portals.staff');
    }
}
