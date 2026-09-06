<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthPassportPageResource;
use App\Http\Resources\HealthPassportSummaryResource;
use App\Models\Patient;
use App\Services\Patient\HealthPassportBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The patient's Health Passport — their own record, as thirty pages.
 *
 * GET /api/mobile/health-passport                  the covers, status and index
 * GET /api/mobile/health-passport/pages/{pageId}   one page, built from records
 *
 * WHOSE RECORD. Behind `auth.mobile`, so `patient_id` is whatever the
 * middleware resolved: the signed-in patient normally, or the dependent when a
 * guardian is acting for one — ActAsDependent has already checked the link and
 * swapped the id, and it refuses the account/settings prefixes a guardian may
 * never touch. This controller therefore reads the attribute and never a body,
 * query or header value; there is no parameter here that could name another
 * person's passport.
 *
 * WHAT IS IN IT. Only what the platform has actually recorded for this person
 * (App\Services\Patient\HealthPassportBuilder). A page with nothing on it comes
 * back with no sections and the app says so; nothing is ever filled in with
 * specimen content to make a page look complete.
 *
 * WHAT IS NOT IN IT. No credential. The passport is a reading surface, not
 * proof of anything: the QR a patient presents is minted by the existing
 * short-lived endpoint, carries an opaque reference, and never travels through
 * here.
 */
class MobileHealthPassportController extends Controller
{
    public function __construct(private readonly HealthPassportBuilder $passport)
    {
    }

    /** GET /api/mobile/health-passport */
    public function show(Request $request): JsonResponse
    {
        $patient = $this->patient($request);

        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        return response()->json([
            'data' => HealthPassportSummaryResource::make($this->passport->summary($patient)),
        ]);
    }

    /** GET /api/mobile/health-passport/pages/{pageId} */
    public function page(Request $request, string $pageId): JsonResponse
    {
        $patient = $this->patient($request);

        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $page = $this->passport->page($patient, $pageId);

        if ($page === null) {
            // Not one of the thirty. A page id is public vocabulary, so this
            // says nothing about the record.
            return response()->json(['message' => __('passport.unknown_page')], 404);
        }

        return response()->json([
            'data' => HealthPassportPageResource::make($page),
        ]);
    }

    /**
     * The patient this request is FOR, from the middleware's attribute only.
     */
    private function patient(Request $request): ?Patient
    {
        $patientId = $request->attributes->get('patient_id');

        return $patientId ? Patient::find($patientId) : null;
    }
}
