<?php

use App\Http\Controllers\Api\Mobile\MobileDoctorMessagingController;
use App\Http\Controllers\Api\Mobile\MobileDoctorProfileController;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — clinician messaging + the clinician's own profile
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php (the same pattern as routes/mobile_vitals.php):
|
|     // Mobile clinician messaging + own profile — same rationale.
|     Route::middleware('api')
|         ->group(base_path('routes/mobile_doctor_messaging.php'));
|
| IMPORTANT: routes loaded this way do NOT inherit the automatic `/api`
| prefix routes/api.php gets, so the prefix below spells out `api/mobile`
| in full. Verify with:
|     php artisan route:list --path=mobile/doctor
|
| auth.provider (AuthenticateMobileProvider) sets `provider_user_id` and
| `provider_facility_id` on the request attributes from the bearer token.
| The controllers read both from there and NOWHERE else — there is no
| facility or clinician identifier in any path, query string or body on
| this surface, so neither can be spoofed by a caller.
|
| Throttles mirror the patient side exactly (routes/api.php):
|   sending a message   throttle:30,1
|   photo upload        throttle:10,1   (a multipart write, like /messages/threads)
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/doctor')->middleware('auth.provider')->group(function () {

    // ── Messaging ────────────────────────────────────────────────────────
    // Thread ids are bigints (message_threads.id), so {id} is digit
    // constrained: an unconstrained segment reaches Postgres as a bigint
    // cast and 500s where it should 404.
    Route::get('messages/threads', [MobileDoctorMessagingController::class, 'index']);
    Route::get('messages/unread-count', [MobileDoctorMessagingController::class, 'unreadCount']);
    Route::get('messages/threads/{id}', [MobileDoctorMessagingController::class, 'show'])
        ->whereNumber('id');
    Route::post('messages/threads/{id}/messages', [MobileDoctorMessagingController::class, 'sendMessage'])
        ->whereNumber('id')
        ->middleware('throttle:30,1');
    Route::patch('messages/threads/{id}/state', [MobileDoctorMessagingController::class, 'state'])
        ->whereNumber('id');

    // ── The clinician's own profile ──────────────────────────────────────
    // No {user} segment, by design. See MobileDoctorProfileController's
    // docblock: this surface has no way to ask about anyone else.
    Route::get('profile', [MobileDoctorProfileController::class, 'show']);
    Route::put('profile', [MobileDoctorProfileController::class, 'update']);
    Route::post('profile/photo', [MobileDoctorProfileController::class, 'uploadPhoto'])
        ->middleware('throttle:10,1');
});

/*
| The photo itself is served OUTSIDE the bearer-token group: an <Image> tag
| cannot send an Authorization header, so the capability lives in a
| short-lived signature instead (URL::temporarySignedRoute, 60 minutes,
| re-issued on every profile read).
|
| ValidateSignature is referenced by class rather than by the `signed` alias
| so this file does not depend on an alias registered in bootstrap/app.php,
| which this slice does not own.
|
| The bytes live on the `local` disk (storage/app/private) — outside the
| webroot, with no public URL and no storage symlink — and are served with
| the Content-Type the SERVER determined from the file's own bytes at upload
| time, plus nosniff.
|
| The name is set on the REGISTRAR (`Route::name(...)->get(...)`) rather than
| chained after `get()`. RouteCollection::add() reads the name at the moment the
| route is added, and a name chained afterwards only becomes resolvable when
| RouteServiceProvider's `booted` callback runs refreshNameLookups(). That
| callback has already fired for anything registered after boot, so a chained
| `->name()` would leave route('mobile.doctor.profile.photo') undefined — and
| the photo endpoint would 500 on the URL it is trying to build.
*/
Route::name('mobile.doctor.profile.photo')
    ->middleware(ValidateSignature::class)
    ->get('api/mobile/doctor/profile/photo/{profile}', [MobileDoctorProfileController::class, 'showPhoto']);
