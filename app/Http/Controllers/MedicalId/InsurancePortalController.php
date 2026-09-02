<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\EligibilityCheck;
use App\Models\InsuranceClaim;
use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\PreauthorizationRequest;
use App\Modules\Insurance\Services\ClaimPaymentService;
use App\Modules\Insurance\Services\ClaimService;
use App\Modules\Insurance\Services\InsuranceEligibilityService;
use App\Modules\Insurance\Services\PreauthorizationService;
use Illuminate\Http\Request;
use Throwable;

class InsurancePortalController extends Controller
{
    // -----------------------------------------------------------------
    // Demo helpers
    // -----------------------------------------------------------------

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    private function demoFacilityId(): ?string
    {
        return session('active_facility_id') ?? auth()->user()?->primary_facility_id ?? null;
    }

    // -----------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------

    public function dashboard()
    {
        $stats = [
            'providers'    => InsuranceProvider::count(),
            'active_plans' => InsurancePlan::where('status', 'active')->count(),
            'policies'     => PatientInsurancePolicy::count(),
            'open_claims'  => InsuranceClaim::whereNotIn('status', ['paid', 'rejected', 'cancelled'])->count(),
            'pending_auth' => PreauthorizationRequest::whereNotIn('status', ['approved', 'rejected', 'cancelled'])->count(),
            'paid_claims'  => InsuranceClaim::where('status', 'paid')->count(),
            'total_claim_value' => (float) InsuranceClaim::sum('claimed_amount'),
        ];

        $recentClaims = InsuranceClaim::with('patient')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentPreauths = PreauthorizationRequest::with('patient')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('portals.insurance.dashboard', compact('stats', 'recentClaims', 'recentPreauths'));
    }

    // -----------------------------------------------------------------
    // Patient Policies
    // -----------------------------------------------------------------

    public function policies(Request $req)
    {
        $q = PatientInsurancePolicy::with(['plan.provider', 'latestEligibility'])
            ->orderByDesc('created_at');

        if ($patientId = $req->input('patient_id')) {
            $q->where('patient_id', $patientId);
        }
        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }

        $policies = $q->limit(100)->get();
        $patients = Patient::limit(200)->get();
        $plans = InsurancePlan::with('provider')->where('status', 'active')->get();

        return view('portals.insurance.policies', compact('policies', 'patients', 'plans'));
    }

    public function policiesStore(Request $req)
    {
        $data = $req->validate([
            'patient_id'          => 'required|string',
            'insurance_plan_id'   => 'required|string',
            'policy_number'       => 'required|string|max:100',
            'member_id'           => 'nullable|string|max:100',
            'group_number'        => 'nullable|string|max:100',
            'relationship_to_primary' => 'nullable|string|max:50',
            'primary_member_name' => 'nullable|string|max:200',
            'effective_date'      => 'nullable|date',
            'expiry_date'         => 'nullable|date|after_or_equal:effective_date',
            'notes'               => 'nullable|string|max:1000',
        ]);

        try {
            PatientInsurancePolicy::create([
                'patient_id'          => $data['patient_id'],
                'insurance_plan_id'   => $data['insurance_plan_id'],
                'policy_number'       => $data['policy_number'],
                'member_id'           => $data['member_id'] ?? null,
                'group_number'        => $data['group_number'] ?? null,
                'relationship_to_primary' => $data['relationship_to_primary'] ?? 'self',
                'primary_member_name' => $data['primary_member_name'] ?? null,
                'effective_date'      => $data['effective_date'] ?? null,
                'expiry_date'         => $data['expiry_date'] ?? null,
                'status'              => 'pending',
                'notes'               => $data['notes'] ?? null,
            ]);

            return redirect()->route('portals.insurance.policies')
                ->with('success', __('flash.insurance_policy_registered'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_policy_register_failed', ['error' => $e->getMessage()]));
        }
    }

    public function policiesActivate(string $id, Request $req)
    {
        try {
            $policy = PatientInsurancePolicy::findOrFail($id);
            $policy->update([
                'status' => 'active',
                'verified_by' => $this->demoActorId(),
                'verified_at' => now(),
            ]);

            return back()->with('success', __('flash.insurance_policy_activated'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_policy_activate_failed', ['error' => $e->getMessage()]));
        }
    }

    public function policiesDeactivate(string $id)
    {
        try {
            $policy = PatientInsurancePolicy::findOrFail($id);
            $policy->update(['status' => 'inactive']);

            return back()->with('success', __('flash.insurance_policy_deactivated'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_policy_deactivate_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Eligibility Checks
    // -----------------------------------------------------------------

    public function eligibilityStore(string $policyId, Request $req, InsuranceEligibilityService $svc)
    {
        $data = $req->validate([
            'status' => 'required|in:eligible,not_eligible,unknown,expired,failed',
            'notes'  => 'nullable|string|max:500',
        ]);

        try {
            $svc->checkEligibility(
                $policyId,
                $this->demoActorId(),
                $data['status'],
                $data['notes'] ?? null
            );

            return back()->with('success', __('flash.insurance_eligibility_recorded'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_eligibility_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Preauthorizations
    // -----------------------------------------------------------------

    public function preauths(Request $req)
    {
        $q = PreauthorizationRequest::with(['policy.plan.provider', 'latestDecision'])
            ->orderByDesc('created_at');

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }

        $preauths = $q->limit(100)->get();
        $policies = PatientInsurancePolicy::with('plan.provider')
            ->where('status', 'active')
            ->limit(200)->get();

        return view('portals.insurance.preauths', compact('preauths', 'policies'));
    }

    public function preauthsStore(Request $req, PreauthorizationService $svc)
    {
        $data = $req->validate([
            'policy_id'              => 'required|string',
            'service_description'    => 'required|string|max:500',
            'clinical_justification' => 'nullable|string|max:2000',
            'estimated_amount'       => 'nullable|numeric|min:0',
            'invoice_id'             => 'nullable|string',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        try {
            $svc->createRequest(array_merge($data, [
                'facility_id' => $this->demoFacilityId(),
                'actor_id'    => $this->demoActorId(),
            ]));

            return redirect()->route('portals.insurance.preauths')
                ->with('success', __('flash.insurance_preauth_created'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_preauth_create_failed', ['error' => $e->getMessage()]));
        }
    }

    public function preauthsSubmit(string $id, PreauthorizationService $svc)
    {
        try {
            $svc->submit($id, $this->demoActorId());

            return back()->with('success', __('flash.insurance_preauth_submitted'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_preauth_submit_failed', ['error' => $e->getMessage()]));
        }
    }

    public function preauthsDecide(string $id, Request $req, PreauthorizationService $svc)
    {
        $data = $req->validate([
            'decision'             => 'required|in:approved,rejected,more_information_required',
            'reason'               => 'required|string|max:1000',
            'approved_amount'      => 'nullable|numeric|min:0',
            'authorization_number' => 'nullable|string|max:100',
        ]);

        try {
            $svc->decide($id, $this->demoActorId(), $data);

            return back()->with('success', __('flash.insurance_decision_recorded'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_decision_failed', ['error' => $e->getMessage()]));
        }
    }

    public function preauthsCancel(string $id, PreauthorizationService $svc)
    {
        try {
            $svc->cancel($id, $this->demoActorId());

            return back()->with('success', __('flash.insurance_preauth_cancelled'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_preauth_cancel_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Insurance Claims
    // -----------------------------------------------------------------

    public function claims(Request $req)
    {
        $q = InsuranceClaim::with(['policy.plan.provider', 'latestDecision'])
            ->orderByDesc('created_at');

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }
        if ($policyId = $req->input('policy_id')) {
            $q->where('patient_insurance_policy_id', $policyId);
        }

        $claims = $q->limit(100)->get();
        $policies = PatientInsurancePolicy::with('plan.provider')
            ->where('status', 'active')
            ->limit(200)->get();

        return view('portals.insurance.claims', compact('claims', 'policies'));
    }

    public function claimsStore(Request $req, ClaimService $svc)
    {
        $data = $req->validate([
            'policy_id'                   => 'required|string',
            'invoice_id'                  => 'nullable|string',
            'preauthorization_request_id' => 'nullable|string',
            'notes'                       => 'nullable|string|max:1000',
            'items'                       => 'required|array|min:1',
            'items.*.description'         => 'required|string|max:500',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.unit_price'          => 'required|numeric|min:0',
        ]);

        try {
            $svc->createClaim(array_merge($data, [
                'facility_id' => $this->demoFacilityId(),
                'actor_id'    => $this->demoActorId(),
            ]));

            return redirect()->route('portals.insurance.claims')
                ->with('success', __('flash.insurance_claim_created'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_claim_create_failed', ['error' => $e->getMessage()]));
        }
    }

    public function claimsSubmit(string $id, ClaimService $svc)
    {
        try {
            $svc->submit($id, $this->demoActorId());

            return back()->with('success', __('flash.insurance_claim_submitted'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_claim_submit_failed', ['error' => $e->getMessage()]));
        }
    }

    public function claimsDecide(string $id, Request $req, ClaimService $svc)
    {
        $data = $req->validate([
            'decision'            => 'required|in:approved,partially_approved,rejected,more_information_required,disputed',
            'reason'              => 'required|string|max:1000',
            'approved_amount'     => 'nullable|numeric|min:0',
            'missing_information' => 'nullable|string|max:1000',
        ]);

        try {
            $svc->decide($id, $this->demoActorId(), $data);

            return back()->with('success', __('flash.insurance_claim_decision_recorded'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_decision_failed', ['error' => $e->getMessage()]));
        }
    }

    public function claimsCancel(string $id, ClaimService $svc)
    {
        try {
            $svc->cancel($id, $this->demoActorId());

            return back()->with('success', __('flash.insurance_claim_cancelled'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_claim_cancel_failed', ['error' => $e->getMessage()]));
        }
    }

    public function claimsPay(string $id, Request $req, ClaimPaymentService $svc)
    {
        $data = $req->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|in:bank_transfer,cheque,cash,eft,other',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ]);

        try {
            $svc->recordPayment($id, $this->demoActorId(), $data);

            return back()->with('success', __('flash.insurance_claim_payment_recorded'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_claim_payment_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Providers & Plans (admin setup)
    // -----------------------------------------------------------------

    public function providers(Request $req)
    {
        $providers = InsuranceProvider::with('activePlans')
            ->orderBy('name')
            ->get();

        return view('portals.insurance.providers', compact('providers'));
    }

    public function providersStore(Request $req)
    {
        $data = $req->validate([
            'name'          => 'required|string|max:200',
            'code'          => 'nullable|string|max:50|unique:insurance_providers,code',
            'country_code'  => 'nullable|string|max:3',
            'contact_email' => 'nullable|email|max:200',
            'contact_phone' => 'nullable|string|max:30',
        ]);

        try {
            InsuranceProvider::create(array_merge($data, ['status' => 'active']));

            return back()->with('success', __('flash.insurance_provider_added'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_provider_add_failed', ['error' => $e->getMessage()]));
        }
    }

    public function plansStore(string $providerId, Request $req)
    {
        $data = $req->validate([
            'name'                       => 'required|string|max:200',
            'plan_code'                  => 'nullable|string|max:50',
            'plan_type'                  => 'nullable|string|max:50',
            'requires_preauthorization'  => 'nullable|boolean',
            'cashless_available'         => 'nullable|boolean',
            'copay_percentage'           => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            InsurancePlan::create(array_merge($data, [
                'insurance_provider_id'     => $providerId,
                'requires_preauthorization' => $req->boolean('requires_preauthorization'),
                'cashless_available'        => $req->boolean('cashless_available'),
                'status'                    => 'active',
            ]));

            return back()->with('success', __('flash.insurance_plan_added'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.insurance_plan_add_failed', ['error' => $e->getMessage()]));
        }
    }
}
