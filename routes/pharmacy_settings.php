<?php

/*
|--------------------------------------------------------------------------
| "My pharmacy" settings (web)
|--------------------------------------------------------------------------
|
| One page where a pharmacy manager sets up the facility itself: identity,
| location, contacts, hours, licence. Registered from AppServiceProvider;
| the middleware stack is declared here next to the routes it gates.
*/

use App\Http\Controllers\MedicalId\PharmacySettingsController;
use Illuminate\Support\Facades\Route;

/*
| THE GATE mirrors routes/pharmacy_ops.php (capability judged BEFORE
| portal.access so a role refusal stays a 403 and is not hidden by the
| wrong-bucket redirect). The capability is `service_catalogue` — "answers
| for the institution" — held by pharmacy_manager, lab_manager and the
| facility admins (config/staff_capabilities.php). A pharmacist or a
| technician does not set the pharmacy's public name or address.
|
| The facility is the session tenant (PortalContextService), never a URL id
| or a body field; the public listing is the care_facilities row linked to
| it. No route here creates or links a listing — that stays an admin
| decision (admin.care-map.review.listings.link).
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'capability:service_catalogue', 'portal.access'])->group(function () {
    Route::get('/pharmacy/settings', [PharmacySettingsController::class, 'index'])
        ->name('portals.pharmacy.settings.index');
    Route::post('/pharmacy/settings/identity', [PharmacySettingsController::class, 'updateIdentity'])
        ->name('portals.pharmacy.settings.identity');
    Route::post('/pharmacy/settings/location', [PharmacySettingsController::class, 'updateLocation'])
        ->name('portals.pharmacy.settings.location');
    Route::post('/pharmacy/settings/hours', [PharmacySettingsController::class, 'updateHours'])
        ->name('portals.pharmacy.settings.hours');
});
