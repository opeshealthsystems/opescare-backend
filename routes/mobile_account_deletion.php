<?php

use App\Http\Controllers\AccountDeletionPageController;
use App\Http\Controllers\Api\Mobile\MobileAccountDeletionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account deletion — in-app, and the public page that documents it
|--------------------------------------------------------------------------
|
| THE BLOCKER THIS CLOSES
| -----------------------
| Apple 5.1.1(v) and Google Play's account-deletion policy both require an app
| that offers account CREATION to offer account DELETION from inside the app.
| This app creates accounts (POST /mobile/auth/register) and had no deletion
| endpoint at all — `app/settings.tsx` carried a comment saying exactly that.
| An almost automatic rejection.
|
| Google additionally wants a publicly reachable URL explaining the deletion
| route, openable by a reviewer who has not installed the app. That is the web
| route at the bottom of this file.
|
|
| REGISTRATION — one line, in AppServiceProvider::boot(), alongside the other
| mobile route groups:
|
|     // Account deletion — the in-app erasure flow (Apple 5.1.1(v) /
|     // Google Play) plus the public page documenting it. The file applies
|     // its own middleware per group, so it is loaded without one here.
|     Route::group([], base_path('routes/mobile_account_deletion.php'));
|
| Note the empty attribute array rather than the ->middleware('api') used by
| the neighbouring files: this file carries BOTH an API group and a web group,
| and stacking 'api' over the public HTML page would put the API throttle and
| JSON error rendering on a marketing page. Each group below states its own
| middleware instead.
|
| Verify:
|     php artisan route:list --path=account/deletion
|     php artisan route:list --path=account-deletion
|
|
| WHY THE API ROUTES CANNOT TOUCH ANOTHER PATIENT
| -----------------------------------------------
| `auth.mobile` (AuthenticateMobilePatient) resolves the bearer token against
| `patient_access_tokens` and stamps `patient_id` onto the request ATTRIBUTES.
| MobileAccountDeletionController reads it from there and nowhere else, and no
| route below carries a patient identifier in its path. A `patient_id` in the
| body is not overridden — it is never read at all, because the controller's
| validate() call enumerates the only three fields it consumes
| (`confirmation`, `credential`, `reason`) and Laravel returns only those.
|
|
| THROTTLES
| ---------
| POST .../deletion carries `throttle:5,1`: it checks a password or PIN, so it
| is a credential-verification endpoint and must not be a free brute-force
| oracle for the account's password.
|
| POST .../deletion/cancel is deliberately throttled far more loosely
| (`throttle:30,1`). Cancelling is the SAFE direction — it preserves data — and
| a patient racing a deadline to stop an irreversible erasure must never be
| rate-limited into missing it.
|
| GET .../deletion is not throttled beyond the default API budget: it is a read
| of the caller's own state, and a patient checking whether their account is
| still closing should be able to check as often as they like.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Patient API
|--------------------------------------------------------------------------
*/
Route::prefix('api/mobile')
    ->middleware(['api', 'auth.mobile'])
    ->group(function () {

        /*
         * GET /api/mobile/account/deletion
         *
         * Current closure state AND the full itemised disclosure — what is
         * erased, what is de-identified, what is retained and under what legal
         * basis, when the retained part is purged, and what cannot be deleted
         * at all — with live counts taken from this patient's own record.
         *
         * Both in one response on purpose: the screen must never be able to
         * render a confirm button before it holds the text describing what
         * confirming does.
         */
        Route::get('account/deletion', [MobileAccountDeletionController::class, 'show']);

        /*
         * POST /api/mobile/account/deletion
         *
         * Opens the 30-day grace period. Requires BOTH a password/PIN and the
         * typed confirmation word. Nothing is erased here — this only sets a
         * date.
         */
        Route::post('account/deletion', [MobileAccountDeletionController::class, 'store'])
            ->middleware('throttle:5,1');

        /*
         * POST /api/mobile/account/deletion/cancel
         *
         * Stops a pending closure. Authentication is the whole check: only a
         * live token for this patient reaches it, and requiring the password
         * again would let a forgotten password run the clock out on a patient
         * trying to save their own record.
         */
        Route::post('account/deletion/cancel', [MobileAccountDeletionController::class, 'cancel'])
            ->middleware('throttle:30,1');
    });

/*
|--------------------------------------------------------------------------
| Public page
|--------------------------------------------------------------------------
|
| Deliberately NOT a web deletion form. Deleting a medical account requires the
| holder authenticated; a public form taking a phone number would be both an
| enumeration oracle and a denial-of-service against other people's records.
| The page documents the in-app route and the assisted route, and reads its
| numbers from AccountDeletionService so it can never quote a grace period the
| app does not honour.
*/
Route::middleware('web')->group(function () {
    Route::get('/account-deletion', [AccountDeletionPageController::class, 'show'])
        ->name('public.account-deletion');
});
