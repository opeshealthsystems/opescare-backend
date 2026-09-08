<?php

use App\Http\Controllers\Api\Fhir\FhirIngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FHIR R4 INGEST — OpesCare Finder
|--------------------------------------------------------------------------
| The door an external system (a hospital's own EMR, a lab machine's
| middleware, a partner clinic) pushes clinical data through, against an
| EXISTING Health ID.
|
| Its own file, loaded from AppServiceProvider::boot() like the ~40 other
| per-feature route files, so that parallel work on separate features cannot
| collide in routes/api.php.
|
| ── WHAT IS DELIBERATELY ABSENT ───────────────────────────────────────────
| There is NO POST /Patient, and there never will be. A partner may not
| create, update or upsert a patient record: a Health ID is minted only by
| OpesCare identity services. Every route below resolves an existing Health ID
| exactly and refuses the write if it does not resolve. Adding a Patient write
| route here would defeat the single most important rule this API has.
|
| Reads are out of scope for this surface entirely — this is ingest only.
|
| ── THE MIDDLEWARE STACK, AND WHY EACH LAYER ─────────────────────────────
|   throttle  A refused write still answers 404-vs-403, so an unlimited caller
|             could walk the Health ID space. The limit is what turns that
|             from a search into a slog, alongside the fhir_ingest_events log.
|   auth.bearer:<scope>
|             RS256 bearer token + SMART-on-FHIR system scope. It also sets
|             facility_id in $request->attributes, which is the ONLY place the
|             controller will read a facility from.
|
| Consent and idempotency are enforced in the controller rather than as route
| middleware, because both refusals must be rendered as FHIR OperationOutcome
| resources; the platform's generic consent.grant / idempotency middleware
| answer in the Connect JSON envelope, which a FHIR client cannot parse.
*/

Route::prefix('api/fhir/R4')->group(function () {

    // CapabilityStatement — unauthenticated, per the FHIR specification.
    // It declares honestly that Patient is never created here.
    Route::get('/metadata', [FhirIngestController::class, 'metadata'])
        ->middleware('throttle:120,1');

    Route::middleware(['throttle:120,1', 'auth.bearer:' . FhirIngestController::SCOPE_OBSERVATION])
        ->post('/Observation', [FhirIngestController::class, 'observation']);

    Route::middleware(['throttle:120,1', 'auth.bearer:' . FhirIngestController::SCOPE_DIAGNOSTIC_REPORT])
        ->post('/DiagnosticReport', [FhirIngestController::class, 'diagnosticReport']);

    Route::middleware(['throttle:120,1', 'auth.bearer:' . FhirIngestController::SCOPE_IMMUNIZATION])
        ->post('/Immunization', [FhirIngestController::class, 'immunization']);

    // Transaction Bundle — all-or-nothing. No scope on the route: a Bundle
    // carries several resource types, so the scope for each entry is checked
    // as that entry is read, inside the transaction that will roll back if any
    // of them is refused.
    Route::middleware(['throttle:60,1', 'auth.bearer'])
        ->post('/', [FhirIngestController::class, 'transaction']);
});
