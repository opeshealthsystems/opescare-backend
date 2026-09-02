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

        $platformStats = [
            'facilities'           => \App\Models\Facility::count(),
            'organizations'        => \App\Models\DeveloperOrganization::count(),
            'active_subscriptions' => \App\Models\OrganizationSubscription::whereIn('status', ['active', 'trialing'])->count(),
            'open_tickets'         => \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'total_users'          => \App\Models\User::count(),
            'pending_onboarding'   => \App\Models\OrganizationSubscription::where('status', 'pending')->count(),
            'monthly_revenue'      => \App\Models\Invoice::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'paid')->sum('subtotal_amount'),
        ];

        return view('portals.admin.index', compact('stats', 'recentLogs', 'platformStats'));
    }
}
