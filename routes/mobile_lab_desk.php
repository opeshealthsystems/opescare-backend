<?php

use App\Http\Controllers\Api\Mobile\Lab\LabDeskController;
use App\Http\Controllers\Api\Mobile\Lab\LabRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Provider Routes — LAB DESK
|
| The laboratory's side of a lab order: collect the sample, enter results,
| release them, and answer the patient's test requests. The patient reads
| the same orders through routes/mobile_labs.php; the ordering clinician
| through routes/mobile_doctor_results.php. Nothing here changes either.
|
| Loaded from AppServiceProvider::boot() to avoid touching the sealed
| routes/api.php file (same pattern as routes/mobile_doctor_results.php).
|
| NOTE the explicit 'api/mobile/lab' prefix: files loaded via
| Route::middleware('api')->group(base_path(...)) do NOT inherit the
| automatic '/api' prefix that routes/api.php gets from bootstrap/app.php,
| so it has to be written out here or every client call 404s.
| Verify with: php artisan route:list --path=api/mobile/lab
|
| Auth is auth.provider (AuthenticateMobileProvider) — the lab member's user
| id and facility_id always come from the bearer token via
| $request->attributes, never from the request body, query string or a header.
|
| Role gates (config/staff_capabilities.php), listed AFTER auth.provider
| because they read the provider_user_id attribute that middleware sets:
|   • `capability:lab_results` — the whole desk (labtech, lab_scientist,
|     lab_manager, lab_validator). Doctors, pharmacists and the front desk
|     get 403 {error_code: role_forbidden}.
|   • `capability:lab_results,sample_collection` — /collect only, so a
|     sample_collection account can book a sample in without ever seeing a
|     result.
|
| ROUTE ORDER: `orders/{id}/…` and `requests/{id}/…` carry the id in a fixed
| position and every literal segment differs from `orders` / `requests`
| themselves, so nothing here can be swallowed by a wildcard.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile/lab')->middleware(['auth.provider', 'capability:lab_results'])->group(function () {
    Route::get('home', [LabDeskController::class, 'home']);

    Route::get('orders', [LabDeskController::class, 'orders']);
    Route::get('orders/{id}', [LabDeskController::class, 'show']);
    Route::post('orders/{id}/results', [LabDeskController::class, 'results']);
    Route::post('orders/{id}/release', [LabDeskController::class, 'release']);

    Route::get('requests', [LabRequestController::class, 'requests']);
    Route::post('requests/{id}/approve', [LabRequestController::class, 'approve']);
    Route::post('requests/{id}/decline', [LabRequestController::class, 'decline']);
});

// Sample collection is the one desk action a collection-only account may do.
Route::prefix('api/mobile/lab')->middleware(['auth.provider', 'capability:lab_results,sample_collection'])->group(function () {
    Route::post('orders/{id}/collect', [LabDeskController::class, 'collect']);
});
