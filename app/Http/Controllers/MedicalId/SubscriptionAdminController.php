<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ModuleEntitlement;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionAdminController extends Controller
{
    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }

    // ── Plans ─────────────────────────────────────────────────────────────────

    public function plans()
    {
        $plans = SubscriptionPlan::with('planFeatures')
            ->orderBy('sort_order')
            ->orderBy('price_kobo')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'   => SubscriptionPlan::count(),
            'active'  => SubscriptionPlan::where('is_active', true)->count(),
            'public'  => SubscriptionPlan::where('is_public', true)->count(),
        ];

        return view('portals.admin.subscription.plans', compact('plans', 'stats'));
    }

    public function plansStore(Request $request, SubscriptionService $svc)
    {
        $request->validate([
            'name'                   => 'required|string|max:100',
            'audience'               => 'nullable|in:patient,household,facility,insurer,lab,pharmacy,healthorg,developer',
            'billing_cycle'          => 'required|in:monthly,annual',
            'price'                  => 'required|numeric|min:0',
            'annual_price'           => 'nullable|numeric|min:0',
            'currency'               => 'nullable|string|size:3',
            'description'            => 'nullable|string|max:500',
            'max_facilities'         => 'nullable|integer|min:1',
            'max_staff'              => 'nullable|integer|min:1',
            'max_patients_per_month' => 'nullable|integer|min:1',
            'trial_days'             => 'nullable|integer|min:0',
            'sort_order'             => 'nullable|integer',
            'is_public'              => 'nullable|boolean',
            'feature_keys'           => 'nullable|array',
            'feature_keys.*'         => 'string',
            'feature_labels'         => 'nullable|array',
        ]);

        $planFeatures = [];
        foreach ($request->input('feature_keys', []) as $i => $key) {
            $planFeatures[] = [
                'key'        => $key,
                'label'      => $request->input("feature_labels.{$i}", $key),
                'limit_type' => 'boolean',
            ];
        }

        $data = $request->except(['feature_keys', 'feature_labels']);
        $data['plan_features'] = $planFeatures;
        $data['is_public']     = $request->boolean('is_public', true);

        $svc->createPlan($data, $this->demoActorId());

        return redirect()->route('portals.admin.subscription.plans')
            ->with('success', __('flash.subscription_plan_created'));
    }

    public function plansToggle(Request $request, string $planId, SubscriptionService $svc)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $svc->togglePlan($planId, !$plan->is_active, $this->demoActorId());

        return back()->with('success', $plan->is_active ? __('flash.plan_deactivated') : __('flash.plan_activated'));
    }

    // ── Subscriptions ─────────────────────────────────────────────────────────

    public function subscriptions(Request $request)
    {
        $svc   = app(SubscriptionService::class);
        $stats = $svc->getAdminStats();

        $query = OrganizationSubscription::with('plan')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('organization_name', 'like', '%' . $request->search . '%');
        }

        $subscriptions = $query->paginate(20)->withQueryString();
        $plans         = SubscriptionPlan::active()->orderBy('sort_order')->get();
        $facilities    = Facility::orderBy('name')->get(['id', 'name']);

        return view('portals.admin.subscription.subscriptions', compact(
            'subscriptions', 'stats', 'plans', 'facilities'
        ));
    }

    public function subscriptionsStore(Request $request, SubscriptionService $svc)
    {
        $request->validate([
            'organization_id'   => 'required|uuid',
            'organization_name' => 'required|string|max:200',
            'plan_id'           => 'required|uuid',
            'billing_email'     => 'nullable|email',
            'billing_name'      => 'nullable|string|max:200',
            'payment_method'    => 'nullable|in:bank_transfer,card,ussd,cash',
            'discount_percent'  => 'nullable|integer|min:0|max:100',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $svc->subscribe(
            $request->organization_id,
            $request->organization_name,
            $request->plan_id,
            $request->only(['billing_email', 'billing_name', 'payment_method', 'discount_percent', 'notes']),
            $this->demoActorId()
        );

        return redirect()->route('portals.admin.subscription')
            ->with('success', __('flash.subscription_created'));
    }

    public function subscriptionsCancel(Request $request, string $id, SubscriptionService $svc)
    {
        $request->validate(['reason' => 'required|string|min:5|max:500']);
        $svc->cancelSubscription($id, $request->reason, $this->demoActorId());

        return back()->with('success', __('flash.subscription_cancelled'));
    }

    public function subscriptionsRenew(string $id, SubscriptionService $svc)
    {
        $svc->renewSubscription($id, $this->demoActorId());
        return back()->with('success', __('flash.subscription_renewed'));
    }

    public function subscriptionsPause(string $id, SubscriptionService $svc)
    {
        $svc->pauseSubscription($id, $this->demoActorId());
        return back()->with('success', __('flash.subscription_paused'));
    }

    public function subscriptionsReactivate(string $id, SubscriptionService $svc)
    {
        $svc->reactivateSubscription($id, $this->demoActorId());
        return back()->with('success', __('flash.subscription_reactivated'));
    }

    public function subscriptionsChangePlan(Request $request, string $id, SubscriptionService $svc)
    {
        $request->validate(['plan_id' => 'required|uuid']);
        $svc->changePlan($id, $request->plan_id, $this->demoActorId());

        return back()->with('success', __('flash.plan_changed'));
    }

    public function subscriptionDetail(string $id)
    {
        $subscription = OrganizationSubscription::with([
            'plan.planFeatures', 'invoices', 'moduleEntitlements', 'usageMetrics',
        ])->findOrFail($id);

        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        return view('portals.admin.subscription.detail', compact('subscription', 'plans'));
    }

    // ── Invoices ──────────────────────────────────────────────────────────────

    public function invoices(Request $request)
    {
        $query = SubscriptionInvoice::with('subscription')
            ->orderBy('invoice_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->paginate(25)->withQueryString();

        $stats = [
            'paid_this_month'  => SubscriptionInvoice::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)->sum('total_kobo'),
            'overdue_count'    => SubscriptionInvoice::where('status', 'sent')
                ->where('due_date', '<', now())->count(),
            'overdue_amount'   => SubscriptionInvoice::where('status', 'sent')
                ->where('due_date', '<', now())->sum('total_kobo'),
            'pending_count'    => SubscriptionInvoice::where('status', 'sent')->count(),
        ];

        return view('portals.admin.subscription.invoices', compact('invoices', 'stats'));
    }

    public function invoiceMarkPaid(Request $request, string $id, SubscriptionService $svc)
    {
        $request->validate([
            'payment_reference' => 'required|string|max:200',
            'payment_method'    => 'required|in:bank_transfer,card,ussd,cash',
        ]);

        $svc->markInvoicePaid($id, $request->payment_reference, $request->payment_method, $this->demoActorId());

        return back()->with('success', __('flash.invoice_paid'));
    }
}
