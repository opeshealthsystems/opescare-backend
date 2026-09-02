<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalCertificateController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function issue(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_context_required')], 403);
        }

        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid'],
            'issuing_doctor_id' => ['required', 'uuid'],
            'certificate_type' => ['required', 'in:fitness,sick_leave,return_to_work,disability,travel,sports,school,other'],
            'diagnosis'        => ['nullable', 'string'],
            'fit_for_duty'     => ['nullable', 'boolean'],
            'restrictions'     => ['nullable', 'string'],
            'valid_from'       => ['required', 'date'],
            'valid_until'      => ['nullable', 'date', 'after_or_equal:valid_from'],
            'days_off'         => ['nullable', 'integer', 'min:1'],
            'purpose'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string'],
        ]);

        $title = 'Medical Certificate — ' . ucwords(str_replace('_', ' ', $validated['certificate_type']));

        $document = $this->issuance->issueFromModel(
            'MCD',
            $title,
            $validated,
            $facilityId,
            $validated['patient_id'],
            null,
            $validated['issuing_doctor_id']
        );

        return response()->json([
            'data' => [
                'document_id'       => $document->id,
                'document_number'   => $document->document_number,
                'verification_code' => $document->verification_code,
                'title'             => $document->title,
                'issued_at'         => $document->issued_at,
            ],
        ], 201);
    }
}
