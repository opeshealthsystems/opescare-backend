<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use App\Models\PatientInsurancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileInsuranceMarketplaceController extends Controller
{
    /**
     * GET /api/mobile/insurance/marketplace
     * Returns active providers with their purchasable plans.
     */
    public function index(): JsonResponse
    {
        $providers = InsuranceProvider::where('status', 'active')
            ->with(['activePlans' => fn($q) => $q->where('is_purchasable', true)])
            ->get()
            ->filter(fn($p) => $p->activePlans->isNotEmpty())
            ->map(fn($provider) => [
                'id'            => $provider->id,
                'name'          => $provider->name,
                'code'          => $provider->code,
                'logo_url'      => $provider->logo_url ?? null,
                'contact_email' => $provider->contact_email,
                'contact_phone' => $provider->contact_phone,
                'plans'         => $provider->activePlans->map(fn($plan) => $this->formatPlan($plan)),
            ])
            ->values();

        return response()->json(['data' => $providers]);
    }

    /**
     * GET /api/mobile/insurance/marketplace/plans/{id}
     * Returns full detail for a single purchasable plan.
     */
    public function show(string $id): JsonResponse
    {
        $plan = InsurancePlan::with('provider')
            ->where('id', $id)
            ->where('status', 'active')
            ->where('is_purchasable', true)
            ->firstOrFail();

        return response()->json(['data' => $this->formatPlan($plan, detailed: true)]);
    }

    /**
     * POST /api/mobile/insurance/marketplace/plans/{id}/purchase
     * Self-enroll the authenticated patient into a plan.
     */
    public function purchase(Request $request, string $id): JsonResponse
    {
        $plan = InsurancePlan::where('id', $id)
            ->where('status', 'active')
            ->where('is_purchasable', true)
            ->firstOrFail();

        $patientId = $request->attributes->get('patient_id');

        // Prevent duplicate enrollment in the same plan. Self-enrolment below
        // always creates the policy as 'pending', so checking only for 'active'
        // never matched a policy this endpoint had itself created — the patient
        // could tap Enroll repeatedly and mint unlimited duplicate policies.
        $existing = PatientInsurancePolicy::where('patient_id', $patientId)
            ->where('insurance_plan_id', $plan->id)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => __('api.policy_already_exists'),
                'policy_id' => $existing->id,
            ], 409);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:mobile_money,card,bank_transfer',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $policy = PatientInsurancePolicy::create([
            'patient_id'              => $patientId,
            'insurance_plan_id'       => $plan->id,
            'policy_number'           => 'POL-' . strtoupper(Str::random(10)),
            'relationship_to_primary' => 'self',
            'effective_date'          => now()->toDateString(),
            'expiry_date'             => now()->addYear()->toDateString(),
            'status'                  => 'pending',
            'notes'                   => 'Self-enrolled via mobile app. Payment method: ' . $validated['payment_method']
                . ($validated['payment_reference'] ? '. Ref: ' . $validated['payment_reference'] : ''),
        ]);

        return response()->json([
            'message'       => __('api.enrollment_submitted'),
            'policy_id'     => $policy->id,
            'policy_number' => $policy->policy_number,
            'status'        => $policy->status,
            'effective_date'=> $policy->effective_date->toDateString(),
            'expiry_date'   => $policy->expiry_date->toDateString(),
        ], 201);
    }

    private function formatPlan(InsurancePlan $plan, bool $detailed = false): array
    {
        $data = [
            'id'                      => $plan->id,
            'name'                    => $plan->name,
            'plan_type'               => $plan->plan_type,
            'description'             => $plan->description,
            'monthly_premium'         => $plan->monthly_premium,
            'annual_premium'          => $plan->annual_premium,
            'deductible'              => $plan->deductible,
            'copay_percentage'        => $plan->copay_percentage,
            'cashless_available'      => $plan->cashless_available,
            'requires_preauthorization' => $plan->requires_preauthorization,
        ];

        if ($detailed) {
            $data['covered_services'] = $plan->covered_services;
            $data['provider'] = $plan->provider ? [
                'id'            => $plan->provider->id,
                'name'          => $plan->provider->name,
                'contact_email' => $plan->provider->contact_email,
                'contact_phone' => $plan->provider->contact_phone,
            ] : null;
        }

        return $data;
    }
}
