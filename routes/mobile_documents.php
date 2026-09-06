<?php

use App\Http\Controllers\Api\Mobile\MobilePatientDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Patient Routes — Downloadable documents
|
| Loaded from AppServiceProvider::boot() rather than routes/api.php, which is
| sealed. NOTE the explicit 'api/mobile' prefix: files loaded through
| Route::middleware('api')->group(base_path(...)) do NOT inherit the '/api'
| prefix bootstrap/app.php gives routes/api.php, so it is written out here or
| every client call 404s. Verify with:
|   php artisan route:list --path=mobile/documents
|
| The listing is behind `auth.mobile`, which also carries ActAsDependent — so a
| guardian holding an active link lists the DEPENDENT'S documents by sending the
| X-OpesCare-Dependent header. The patient is taken from the request attribute
| the middleware stamped and from nowhere else.
|
| The download sits OUTSIDE that group on purpose: the app hands the URL to the
| system browser, which carries no bearer token, so the link has to authenticate
| itself. It is a short-lived signed URL naming one patient and one record, and
| the handler re-verifies the signature before rendering anything.
|
| Nothing here reads a file. Every sheet is rendered from the record at the
| moment of the request and never written to disk.
|--------------------------------------------------------------------------
*/

Route::prefix('api/mobile')->group(function () {
    Route::get('documents/{type}/download', [MobilePatientDocumentController::class, 'download'])
        ->where('type', '[a-z_]{4,32}')
        ->name('mobile.documents.download');
});
