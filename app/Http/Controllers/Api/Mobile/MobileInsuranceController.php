<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PatientInsurancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileInsuranceController extends Controller
{
    /**
     * List the authenticated patient's insurance policies.
     * GET /api/mobile/insurance
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        $policies = PatientInsurancePolicy::where('patient_id', $patientId)
            ->with(['plan:id,name,plan_type,insurance_provider_id', 'plan.provider:id,name,country_code'])
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn($p) => [
                'id'                       => $p->id,
                'policy_number'            => $p->policy_number,
                'member_id'                => $p->member_id,
                'group_number'             => $p->group_number,
                'status'                   => $p->status,
                'relationship_to_primary'  => $p->relationship_to_primary,
                'effective_date'           => $p->effective_date?->toDateString(),
                'expiry_date'              => $p->expiry_date?->toDateString(),
                'is_active'                => $p->isActive(),
                'is_expired'               => $p->isExpired(),
                'plan' => $p->plan ? [
                    'id'            => $p->plan->id,
                    'name'          => $p->plan->name,
                    'provider_name' => $p->plan->provider?->name,
                    'plan_type'     => $p->plan->plan_type,
                ] : null,
            ]);

        return response()->json(['data' => $policies]);
    }
}
