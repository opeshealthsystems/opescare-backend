<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\EmergencyAccessEvent;
use App\Models\SecurityIncident;
use Illuminate\Http\Request;
use Throwable;

class SecurityOpsController extends Controller
{
    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-security';
    }

    // ── Dashboard ─────────────────────────────────────────────────

    public function index()
    {
        $stats = [
            'open_incidents'    => SecurityIncident::whereIn('status', ['open', 'investigating'])->count(),
            'emergency_accesses'=> EmergencyAccessEvent::where('created_at', '>=', now()->subDays(7))->count(),
            'audit_events_today'=> AuditEvent::where('created_at', '>=', today())->count(),
            'critical_incidents'=> SecurityIncident::where('severity', 'critical')->whereIn('status', ['open','investigating'])->count(),
        ];

        $recentIncidents = SecurityIncident::orderByDesc('created_at')->limit(5)->get();
        $recentEmergency = EmergencyAccessEvent::with('patient')->orderByDesc('created_at')->limit(5)->get();
        $recentAudit     = AuditEvent::orderByDesc('created_at')->limit(5)->get();

        return view('portals.admin.security_ops.index', compact('stats', 'recentIncidents', 'recentEmergency', 'recentAudit'));
    }

    // ── Security Incidents ────────────────────────────────────────

    public function incidents(Request $request)
    {
        // security_incidents has no facility_id column and never has — this
        // used to filter on one, so the page threw SQLSTATE[42703] for any user
        // holding a facility who was not in a hardcoded list of three roles.
        // privacy_officer, compliance_officer, audit_reviewer and the rest of
        // the platform compliance tier all 500'd here.
        //
        // No scoping is lost by removing it. The whole /portals/admin/security
        // surface is platform-tier only (RequirePlatformAdmin), incidents are
        // platform-global rather than facility-owned, and a filter on a column
        // that does not exist never scoped anything in the first place.
        $q = SecurityIncident::orderByDesc('detected_at');

        if ($request->filled('severity')) {
            $q->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $incidents = $q->paginate(20)->withQueryString();

        return view('portals.admin.security_ops.incidents', compact('incidents'));
    }

    public function incidentStore(Request $request)
    {
        $request->validate([
            'incident_type' => 'required|string|max:100',
            'severity'      => 'required|in:low,medium,high,critical',
            'summary'       => 'required|string|max:2000',
            'detected_at'   => 'required|date',
        ]);

        try {
            SecurityIncident::create([
                'incident_type' => $request->incident_type,
                'severity'      => $request->severity,
                'status'        => 'open',
                'summary'       => $request->summary,
                'detected_at'   => $request->detected_at,
                'created_by'    => $this->demoActorId(),
            ]);

            return redirect()->route('portals.admin.security.incidents')
                ->with('success', __('flash.security_incident_logged'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function incidentUpdate(Request $request, string $id)
    {
        $request->validate([
            'status'       => 'required|in:open,investigating,contained,resolved,closed',
            'summary'      => 'nullable|string|max:2000',
            'contained_at' => 'nullable|date',
            'resolved_at'  => 'nullable|date',
        ]);

        try {
            $incident = SecurityIncident::findOrFail($id);
            $incident->update($request->only(['status', 'summary', 'contained_at', 'resolved_at']));

            return redirect()->route('portals.admin.security.incidents')
                ->with('success', __('flash.incident_updated'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Emergency Access Queue ────────────────────────────────────

    public function emergencyAccess(Request $request)
    {
        $facilityId = session('active_facility_id') ?? auth()->user()?->primary_facility_id ?? null;
        $isPlatformAdmin = in_array(auth()->user()?->role?->name, ['super_admin', 'platform_admin', 'security_officer']);

        $q = EmergencyAccessEvent::with('patient')->orderByDesc('created_at')
            ->when($facilityId && !$isPlatformAdmin, fn ($q) => $q->where('facility_id', $facilityId));

        if ($request->filled('provider_id')) {
            $q->where('provider_id', $request->provider_id);
        }
        if ($request->filled('facility_id') && $isPlatformAdmin) {
            $q->where('facility_id', $request->facility_id);
        }

        $events = $q->paginate(25)->withQueryString();

        return view('portals.admin.security_ops.emergency_access', compact('events'));
    }

    // ── Audit Explorer ────────────────────────────────────────────

    public function auditExplorer(Request $request)
    {
        $facilityId = session('active_facility_id') ?? auth()->user()?->primary_facility_id ?? null;
        $isPlatformAdmin = in_array(auth()->user()?->role?->name, ['super_admin', 'platform_admin', 'security_officer']);

        $q = AuditEvent::orderByDesc('created_at')
            ->when($facilityId && !$isPlatformAdmin, fn ($q) => $q->where('facility_id', $facilityId));

        if ($request->filled('action_type')) {
            $q->where('action_type', $request->action_type);
        }
        if ($request->filled('actor_id')) {
            $q->where('actor_id', $request->actor_id);
        }
        if ($request->filled('resource_type')) {
            $q->where('resource_type', $request->resource_type);
        }
        if ($request->filled('date_from')) {
            $q->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->boolean('emergency_only')) {
            $q->where('emergency_override', true);
        }

        $events       = $q->paginate(30)->withQueryString();
        $actionTypes  = AuditEvent::select('action_type')->distinct()->orderBy('action_type')->pluck('action_type');
        $resourceTypes= AuditEvent::select('resource_type')->distinct()->orderBy('resource_type')->pluck('resource_type');

        return view('portals.admin.security_ops.audit_explorer', compact('events', 'actionTypes', 'resourceTypes'));
    }
}
