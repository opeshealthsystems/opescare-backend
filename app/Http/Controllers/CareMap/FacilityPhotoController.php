<?php

namespace App\Http\Controllers\CareMap;

use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GET /facility/photo/{listing} — the facility photo / logo written by
 * FacilityPhotoService, behind the `signed` middleware: the signature IS
 * the authorisation and it expires (same contract as the patient photo).
 * The Content-Type is the one the server wrote at upload (always JPEG) and
 * `nosniff` stops a browser second-guessing it.
 */
class FacilityPhotoController extends Controller
{
    public function show(string $listing): BinaryFileResponse|JsonResponse
    {
        if (! Str::isUuid($listing)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $row = CareFacility::query()->find($listing);

        if (! $row || ! $row->logo_path || ! Storage::disk('local')->exists($row->logo_path)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $response = response()->file(Storage::disk('local')->path($row->logo_path), [
            'Content-Type'           => 'image/jpeg',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }
}
