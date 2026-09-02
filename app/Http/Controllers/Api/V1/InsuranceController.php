<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Insurance\Services\ClaimPaymentService;
use App\Modules\Insurance\Services\ClaimService;
use App\Modules\Insurance\Services\InsuranceEligibilityService;
use App\Modules\Insurance\Services\PreauthorizationService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * InsuranceController — Insurance Claims & Preauthorization API.
 *
 * SECURITY: Insurance users must not access full EMR.
 * Every claim response returns only minimum necessary data.
 */
class InsuranceController extends Controller
{
    public function __construct(
        private ClaimService                $claims,
        private PreauthorizationService     $preauth,
        private InsuranceEligibilityService $eligibility,
        private ClaimPaymentService         $claimPayments,
        private DocumentIssuanceService     $issuance
    ) {}

    // ── Eligibility ─────────────────────────────────────────────────────────

    public function checkEligibility(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid'],
            'insurance_plan_id' => ['required', 'uuid'],
        ]);

        return response()->json(
            $this->eligibility->check($validated['patient_id'], $validated['insurance_plan_id'])
        );
    }

    // ── Preauthorization ────────────────────────────────────────────────────

    public function requestPreauth(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'          => ['required', 'uuid'],
            'insurance_plan_id'   => ['required', 'uuid'],
            'service_description' => ['required', 'string', 'max:500'],
            'estimated_cost'      => ['nullable', 'numeric', 'min:0'],
            'urgency'             => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_notes'      => ['nullable', 'string'],
        ]);

        $validated['facility_id'] = $facilityId;

        $preauth = $this->preauth->submit($validated, $request->user()->id);

        try {
            $preauthId = is_array($preauth) ? ($preauth['id'] ?? null) : ($preauth->id ?? null);
            $this->issuance->issueFromModel(
                'PAL',
                'Pre-Authorization Letter',
                ['preauth_id' => $preauthId, 'patient_id' => $validated['patient_id'], 'service_description' => $validated['service_description'], 'estimated_cost' => $validated['estimated_cost'] ?? null, 'urgency' => $validated['urgency'] ?? 'routine'],
                $facilityId,
                $validated['patient_id'],
                null,
                $request->user()?->id
            );
        } catch (\Throwable) {}

        return response()->json($preauth, 201);
    }

    public function decidePreauth(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,partial'],
            'reason'   => ['required', 'string'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json(
            $this->preauth->decide($id, $validated, $request->user()->id)
        );
    }

    // ── Claims ──────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $claims = $this->claims->listForUser($request->user(), $request->all());
        return response()->json($claims);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        return response()->json(
            $this->claims->getMinimumNecessaryView($id, $request->user())
        );
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        $result     = $this->claims->submit($id, $request->user()->id);

        if ($facilityId) {
            try {
                $claim = is_array($result) ? $result : $result->toArray();
                $this->issuance->issueFromModel(
                    'CLM',
                    'Insurance Claim',
                    ['claim_id' => $id, 'patient_id' => $claim['patient_id'] ?? null, 'submitted_at' => now()->toISOString(), 'submitted_by' => $request->user()->id],
                    $facilityId,
                    $claim['patient_id'] ?? null,
                    null,
                    $request->user()->id
                );
            } catch (\Throwable) {}
        }

        return response()->json($result);
    }

    public function decide(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,partial,information_requested'],
            'reason'   => ['required', 'string'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json(
            $this->claims->decide($id, $request->user()->id, $validated)
        );
    }

    public function postPayment(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['nullable', 'string', 'in:bank_transfer,cheque,cash,mobile_money,direct_debit'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'paid_at'          => ['nullable', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $actorId = $request->attributes->get('integration_client_id')
            ?? $request->attributes->get('provider_id')
            ?? $request->user()?->id;

        if (!$actorId) {
            return response()->json(['message' => __('api.actor_unresolved_identity'), 'code' => 'ACTOR_UNRESOLVABLE'], 403);
        }

        try {
            $payment = $this->claimPayments->recordPayment($id, $actorId, $validated);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'CLAIM_NOT_PAYABLE') {
                return response()->json([
                    'message' => __('api.claim_not_payable'),
                ], 422);
            }
            throw $e;
        }

        return response()->json(['data' => $payment], 201);
    }
}
