<?php

/*
|--------------------------------------------------------------------------
| "My facility" settings (web) — every facility type except pharmacies
|--------------------------------------------------------------------------
|
| One page where the person who answers for a hospital, clinic, health
| centre, laboratory, dental / imaging / eye clinic… sets up the facility
| itself: identity, location, contacts, hours, licence. Registered from
| AppServiceProvider; the middleware stack is declared here next to the
| routes it gates. Pharmacies have their own copy of this page at
| /pharmacy/settings (routes/pharmacy_settings.php); both are thin callers of
| App\Services\CareMap\FacilitySettingsService.
*/

use App\Http\Controllers\CareMap\FacilityPhotoController;
use App\Http\Controllers\MedicalId\FacilitySettingsController;
use Illuminate\Support\Facades\Route;

/*
| THE GATE mirrors routes/pharmacy_settings.php (capability judged BEFORE
| portal.access so a role refusal stays a 403 and is not hidden by the
| wrong-bucket redirect). The capability is `service_catalogue` — "answers
| for the institution" — held by the facility admins, lab_manager and
| pharmacy_manager (config/staff_capabilities.php). A doctor, a nurse or a
| receptionist does not set the hospital's public name or address.
|
| EnsurePortalAccess has its own 'facility/settings' bucket, listed before
| 'facility', because the audience straddles two portals: facility admins
| live under /admin, lab managers on the /facility desk. That bucket is
| deliberately wide; the capability above decides who may act.
|
| The facility is the session tenant (PortalContextService), never a URL id
| or a body field; the public listing is the care_facilities row linked to
| it. No route here creates or links a listing — that stays an admin
| decision (admin.care-map.review.listings.link).
*/
// facility.context: the same rule as routes/facility_admin.php and
// /facility/team — a user posted at more than one distinct facility chooses
// one; nobody is pinned to primary_facility_id here while the team page asks.
Route::middleware(['web', 'auth', 'mfa.verified', 'capability:service_catalogue', 'portal.access', 'facility.context'])->group(function () {
    Route::get('/facility/settings', [FacilitySettingsController::class, 'index'])
        ->name('portals.facility.settings.index');
    Route::post('/facility/settings/identity', [FacilitySettingsController::class, 'updateIdentity'])
        ->name('portals.facility.settings.identity');
    Route::post('/facility/settings/location', [FacilitySettingsController::class, 'updateLocation'])
        ->name('portals.facility.settings.location');
    Route::post('/facility/settings/hours', [FacilitySettingsController::class, 'updateHours'])
        ->name('portals.facility.settings.hours');
    Route::post('/facility/settings/photo', [FacilitySettingsController::class, 'updatePhoto'])
        ->name('portals.facility.settings.photo');
});

/*
| The photo itself, for the <img> on both settings pages (and any future
| public slot): OUTSIDE the portal gate, the SIGNATURE is the authorisation,
| minted for 60 minutes by FacilityPhotoService::urlFor() and checked by the
| `signed` middleware — the same contract as the patient photo in routes/api.php.
*/
Route::get('/facility/photo/{listing}', [FacilityPhotoController::class, 'show'])
    ->middleware(['web', 'signed'])
    ->name('facility.photo');
