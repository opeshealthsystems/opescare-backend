<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\MedicalIdAccessEvent;
use App\Models\HealthIdQrToken;

class AdminPortalController extends Controller
{
    private function facilityId(): ?string
    {
        return session('active_facility_id') ?? auth()->user()?->primary_facility_id ?? null;
    }

    public function index(Request $request)
    {
        $facilityId = $this->facilityId();

        $stats = [
            'total_ids'         => Patient::whereNotNull('health_id')
                                          ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
                                          ->where('is_demo', false)
                                          ->count(),
            'active_tokens'     => HealthIdQrToken::where('status', 'active')->count(),
            'total_access_logs' => MedicalIdAccessEvent::when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))->count(),
            'denied_access'     => MedicalIdAccessEvent::when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
                                                       ->where('result', 'denied')
                                                       ->count(),
        ];

        $recentLogs = MedicalIdAccessEvent::with('patient')
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Platform-wide figures (revenue, user/org/subscription counts) are god-mode
        // only. The view already hides them behind @platformadmin; gating the QUERIES
        // too means a facility admin who reaches this page never triggers a
        // platform-wide revenue aggregation that was never meant for their tier.
        $platformStats = \App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($request->user()) ? [
            'facilities'           => \App\Models\Facility::count(),
            'organizations'        => \App\Models\DeveloperOrganization::count(),
            'active_subscriptions' => \App\Models\OrganizationSubscription::whereIn('status', ['active', 'trialing'])->count(),
            'open_tickets'         => \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'total_users'          => \App\Models\User::count(),
            'pending_onboarding'   => \App\Models\OrganizationSubscription::where('status', 'pending')->count(),
            'monthly_revenue'      => \App\Models\Invoice::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'paid')->sum('subtotal_amount'),
        ] : null;

        // The duplicate-review panel used to load itself from
        // `/api/v1/connect/admin/merge-cases`, an endpoint this fork does not
        // have — so it sat on its loading spinner forever. The real desk is
        // AdminIdentityReviewController at /admin/mpi; the dashboard now shows
        // that desk's own count and links to it instead of pretending to be it.
        // Platform tier only, for the same reason the figures above are: the
        // MPI queue spans every facility, and /admin/mpi is behind
        // RequirePlatformAdmin — offering a facility admin a link that 403s
        // is the exact bug SidebarLinksAreReachableTest exists to catch.
        $duplicatesPending = $platformStats === null
            ? null
            : \App\Models\MpiCandidate::where('status', 'pending_review')->count();

        return view('portals.admin.index', compact('stats', 'recentLogs', 'platformStats', 'duplicatesPending'));
    }
}
