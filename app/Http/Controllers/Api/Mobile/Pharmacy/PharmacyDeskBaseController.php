<?php

namespace App\Http\Controllers\Api\Mobile\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\Pharmacy\PharmacyDeskException;
use App\Services\Pharmacy\PharmacyDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What every pharmacy-desk controller shares: where the facility and the
 * actor come from, and how a refusal is rendered.
 *
 *   facility_id  ← $request->attributes 'provider_facility_id'  (auth.provider)
 *   actor        ← $request->attributes 'provider_user_id'      (auth.provider)
 *
 * Never from the body, the query string or a header — the platform rule.
 */
abstract class PharmacyDeskBaseController extends Controller
{
    public function __construct(protected readonly PharmacyDeskService $desk)
    {
    }

    /**
     * The facility the token is bound to, or a 422 the caller returns as-is.
     * A provider token without a facility cannot run a counter.
     */
    protected function facility(Request $request): Facility|JsonResponse
    {
        $facility = $this->desk->facility($request->attributes->get('provider_facility_id'));

        if (! $facility) {
            return response()->json([
                'message'    => __('pharmacy_api.errors.facility_required'),
                'error_code' => 'facility_required',
            ], 422);
        }

        return $facility;
    }

    protected function actorId(Request $request): string
    {
        return (string) $request->attributes->get('provider_user_id');
    }

    protected function refuse(PharmacyDeskException $e): JsonResponse
    {
        return response()->json([
            'message'    => $e->getMessage(),
            'error_code' => $e->errorCode,
        ], $e->status);
    }
}
