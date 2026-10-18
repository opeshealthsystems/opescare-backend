<?php

use App\Http\Controllers\Api\Mobile\MobileRecordImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient record import — records from another provider, patient-provided
|--------------------------------------------------------------------------
| The patient (auth.mobile) uploads a FHIR R4 Bundle or a PDF/image. Items are
| stored against the AUTHENTICATED patient only, provenance 'patient_import',
| verification 'unverified'. The stored file is served outside the bearer
| group behind `signed`, like the patient photo.
*/

Route::prefix('api/mobile')->group(function () {
    Route::get('records/imported/{id}/file', [MobileRecordImportController::class, 'file'])
        ->middleware('signed')
        ->name('mobile.records.imported.file');

    Route::middleware('auth.mobile')->group(function () {
        Route::post('records/import/fhir', [MobileRecordImportController::class, 'importFhir'])->middleware('throttle:10,1');
        Route::post('records/import/document', [MobileRecordImportController::class, 'importDocument'])->middleware('throttle:10,1');
        Route::get('records/imported', [MobileRecordImportController::class, 'index']);
        Route::delete('records/imported/{id}', [MobileRecordImportController::class, 'destroy']);
    });
});
