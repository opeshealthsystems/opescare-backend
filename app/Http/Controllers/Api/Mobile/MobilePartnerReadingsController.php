<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Chart\PartnerReadingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/mobile/records/partner-readings — the patient's own
 * partner-reported readings (FHIR R4 pushes from partner systems).
 *
 * Returned apart from every facility reading, each row labelled with the
 * sending facility, the sending system and the provenance "Reported by
 * partner system". The patient is the bearer token's (AuthenticateMobilePatient
 * sets `patient_id` on the request attributes); there is no patient id in
 * the path, query or body, so one patient cannot read another's.
 */
class MobilePartnerReadingsController extends Controller
{
    public function index(Request $request, PartnerReadingsService $readings): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1', 'max:' . PartnerReadingsService::MAX_PAGE],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . PartnerReadingsService::MAX_PER_PAGE],
        ]);

        $result = $readings->forPatient(
            $patientId,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? PartnerReadingsService::DEFAULT_PER_PAGE),
        );

        return response()->json([
            'data' => $result['items']->all(),
            'meta' => [
                'page'             => $result['page'],
                'per_page'         => $result['per_page'],
                'total'            => $result['total'],
                'has_more'         => $result['has_more'],
                'provenance'       => PartnerReadingsService::PROVENANCE,
                'provenance_label' => __('partner_readings.provenance_label'),
                'note'             => __('partner_readings.separate_note'),
            ],
        ]);
    }
}
