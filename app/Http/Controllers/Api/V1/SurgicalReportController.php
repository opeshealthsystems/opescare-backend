<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurgicalReportController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_context_required')], 403);
        }

        $validated = $request->validate([
            'patient_id'        => ['required', 'uuid'],
            'surgeon_id'        => ['required', 'uuid'],
            'anaesthetist_id'   => ['nullable', 'uuid'],
            'procedure_name'    => ['required', 'string', 'max:300'],
            'procedure_code'    => ['nullable', 'string', 'max:50'],
            'operation_date'    => ['required', 'date'],
            'duration_minutes'  => ['nullable', 'integer', 'min:1'],
            'anaesthesia_type'  => ['nullable', 'in:general,regional,local,sedation,none'],
            'pre_op_diagnosis'  => ['required', 'string'],
            'post_op_diagnosis' => ['nullable', 'string'],
            'findings'          => ['required', 'string'],
            'complications'     => ['nullable', 'string'],
            'implants_used'     => ['nullable', 'array'],
            'blood_loss_ml'     => ['nullable', 'integer', 'min:0'],
            'specimens_sent'    => ['nullable', 'array'],
            'notes'             => ['nullable', 'string'],
        ]);

        $payload = array_merge($validated, ['facility_id' => $facilityId]);

        $title = 'Surgical Report — ' . $validated['procedure_name'];

        $document = $this->issuance->issueFromModel(
            'SUR',
            $title,
            $payload,
            $facilityId,
            $validated['patient_id'],
            null,
            $validated['surgeon_id']
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
