<?php

use Illuminate\Support\Facades\Route;

// ── Public imaging-exam finder ─────────────────────────────────────────────
// Reads the catalogue rows imaging centres already publish (LOINC imaging
// codes projected by LabCatalogueProjector). Loaded inside the 'web' group.
Route::get('/network/imaging-finder', \App\Http\Controllers\Imaging\ImagingExamFinderController::class)
    ->name('public.network.imaging-finder');
