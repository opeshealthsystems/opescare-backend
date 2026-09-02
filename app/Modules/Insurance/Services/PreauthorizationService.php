<?php

namespace App\Modules\Insurance\Services;

use App\Models\PreauthorizationDecision;
use App\Models\PreauthorizationRequest;
use Illuminate\Support\Facades\DB;

class PreauthorizationService
{
    /**
     * Create a new preauthorization request (draft).
     */
    public function createRequest(array $data): PreauthorizationRequest
    {
        return PreauthorizationRequest::create([
            'patient_insurance_policy_id' => $data['policy_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'facility_id' => $data['facility_id'],
            'requested_by' => $data['actor_id'],
            'service_description' => $data['service_description'],
            'clinical_justification' => $data['clinical_justification'] ?? null,
            'estimated_amount' => $data['estimated_amount'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Submit an existing draft preauthorization request.
     */
    public function submit(string $requestId, string $actorId): PreauthorizationRequest
    {
        $request = PreauthorizationRequest::findOrFail($requestId);

        if ($request->status !== 'draft') {
            throw new \Exception('PREAUTH_NOT_DRAFT');
        }

        $request->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $request->fresh();
    }

    /**
     * Record a decision on a preauthorization request.
     */
    public function decide(string $requestId, string $actorId, array $data): PreauthorizationDecision
    {
        return DB::transaction(function () use ($requestId, $actorId, $data) {
            $request = PreauthorizationRequest::findOrFail($requestId);

            if (!in_array($request->status, ['submitted', 'under_review', 'more_information_required'])) {
                throw new \Exception('PREAUTH_NOT_REVIEWABLE');
            }

            $decision = PreauthorizationDecision::create([
                'preauthorization_request_id' => $request->id,
                'decided_by' => $actorId,
                'decision' => $data['decision'],
                'approved_amount' => $data['approved_amount'] ?? null,
                'reason' => $data['reason'],
                'authorization_number' => $data['authorization_number'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'decided_at' => now(),
            ]);

            $request->update(['status' => $data['decision']]);

            return $decision;
        });
    }

    /**
     * Cancel a preauthorization request.
     */
    public function cancel(string $requestId, string $actorId): PreauthorizationRequest
    {
        $request = PreauthorizationRequest::findOrFail($requestId);

        if (!$request->isPending()) {
            throw new \Exception('PREAUTH_NOT_CANCELLABLE');
        }

        $request->update(['status' => 'cancelled']);

        return $request->fresh();
    }
}
