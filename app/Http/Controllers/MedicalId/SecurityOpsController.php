<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\SecurityIncident;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

/**
 * SecurityOpsController — the break-glass oversight desk.
 *
 * The Finder already lets a clinician holding `capability:emergency_access`
 * break the glass on any patient, and the patient can see and revoke that
 * grant from the app. What was missing was the other half of the control:
 * an INSTITUTIONAL surface where a security or privacy officer can see every
 * override, decide whether it was justified, and leave that decision on the
 * record. "Anyone with the capability can break glass and only the patient
 * might notice" is not a posture a national record can be piloted on.
 *
 * Three deliberate properties of this desk:
 *
 *  1. READ-MOSTLY. It never edits, voids or deletes an access event or an
 *     audit row — those are immutable history. The only thing it writes about
 *     an override is a separate `emergency_review_cases` row: a review is an
 *     annotation ALONGSIDE the event, never a change TO it.
 *
 *  2. NO PATIENT ENUMERATION. There is no patient search here and no patient
 *     name is rendered. A patient appears only inside a specific access event,
 *     identified by Health ID (a lookup key) or a truncated UUID.
 *
 *  3. NO FACILITY SCOPING, and specifically no facility id taken from a
 *     header, body, query string or "the one facility we found". Everything
 *     under /admin/ is platform tier (RequirePlatformAdmin), and oversight of
 *     break-glass is by definition cross-facility — an override at facility A
 *     reviewed only by facility A is not oversight. The main platform's copy
 *     of this controller read `session('active_facility_id') ?? primary_facility_id`
 *     and also accepted `?facility_id=` straight off the query string; both
 *     are gone rather than ported.
 *
 * Demo isolation is deliberately lifted here. `IsDemoRecord` hides is_demo=false
 * rows whenever the app runs with demo.enabled — which is exactly how the
 * hosted Finder runs — so the global scope would hide REAL break-glass events
 * from the desk whose whole job is to see them. Rows carry a demo badge in the
 * view instead, so the two are never confused.
 */use Illuminate\Support\Facades\Log;

class SecurityOpsController extends Controller
{
    /** Statuses a reviewer may record on an override. Matches the migration. */
    private const REVIEW_STATUSES = ['pending', 'approved', 'escalated'];

    // ── Dashboard ─────────────────────────────────────────────────

    public function index()
    {
        $stats = [
            'unreviewed_breakglass' => $this->unreviewedEvents()->count(),
            'live_grants' => $this->events()
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->whereNull('revoked_at')
                ->count(),
            'breakglass_7d' => $this->events()->where('created_at', '>=', now()->subDays(7))->count(),
            'open_incidents' => SecurityIncident::withoutGlobalScope('isolate_demo')
                ->whereIn('status', ['open', 'investigating'])->count(),
            'critical_incidents' => SecurityIncident::withoutGlobalScope('isolate_demo')
                ->where('severity', 'critical')->whereIn('status', ['open', 'investigating'])->count(),
            'audit_events_today' => AuditEvent::where('created_at', '>=', today())->count(),
        ];

        // The point of the landing screen: overrides nobody has signed off on,
        // oldest first — the longest-unreviewed is the most overdue, not the
        // newest one.
        $unreviewed = $this->unreviewedEvents()->orderBy('created_at')->limit(10)->get();

        $recentIncidents = SecurityIncident::withoutGlobalScope('isolate_demo')
            ->orderByDesc('detected_at')->limit(5)->get();
        $recentAudit = AuditEvent::orderByDesc('created_at')->limit(5)->get();

        return view('portals.admin.security_ops.index', compact(
            'stats', 'unreviewed', 'recentIncidents', 'recentAudit'
        ));
    }

    // ── Emergency access / break-glass queue ──────────────────────

    public function emergencyAccess(Request $request)
    {
        $q = $this->events();

        // Filtering by the clinician who broke the glass is the review workflow
        // ("show me everything this provider did"). There is no patient filter:
        // this desk must not become a way to browse patients.
        if ($request->filled('provider_id')) {
            $q->where('provider_id', $request->string('provider_id')->toString());
        }

        $reviewFilter = $request->string('review')->toString();
        if ($reviewFilter === 'unreviewed') {
            $this->whereUnreviewed($q);
        } elseif ($reviewFilter === 'reviewed') {
            $q->whereExists($this->reviewedExists());
        }

        if ($request->string('grant')->toString() === 'live') {
            $q->whereNotNull('expires_at')->where('expires_at', '>', now())->whereNull('revoked_at');
        }
        if ($request->boolean('repeat_only')) {
            $q->where('is_repeat', true);
        }

        // Unreviewed first, then oldest first inside each group: the queue is
        // ordered by what still owes a decision, not by recency.
        $events = $q
            ->orderByRaw("(select count(*) from emergency_review_cases c where c.emergency_access_event_id = emergency_access_events.id and c.status <> 'pending') asc")
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        $reviews = $this->reviewsFor(collect($events->items()));
        $reviewStatuses = self::REVIEW_STATUSES;

        return view('portals.admin.security_ops.emergency_access', compact('events', 'reviews', 'reviewStatuses'));
    }

    /**
     * Record a review decision on one override.
     *
     * Writes a review case; the access event itself is never touched. A second
     * review of the same event overwrites the case rather than creating a
     * duplicate, and always re-stamps who decided and when.
     */
    public function emergencyReview(Request $request, string $id)
    {
        $this->assertUuid($id);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::REVIEW_STATUSES),
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $event = EmergencyAccessEvent::withoutGlobalScope('isolate_demo')->findOrFail($id);

            EmergencyReviewCase::withoutGlobalScope('isolate_demo')->updateOrCreate(
                ['emergency_access_event_id' => $event->id],
                [
                    'status' => $data['status'],
                    'reviewer_notes' => $data['notes'] ?? null,
                    'reviewed_by' => $request->user()?->id,
                    'reviewed_at' => now(),
                ]
            );

            return redirect()->route('portals.admin.security.emergency_access')
                ->with('success', __('security_ops.emerg_review_saved'));
        } catch (Throwable $e) {
            return back()->with('error', $this->failureMessage($e));
        }
    }

    // ── Security incidents ────────────────────────────────────────

    public function incidents(Request $request)
    {
        // `security_incidents` has no facility_id column and never has, so
        // there is nothing to scope on — and nothing is lost: everything under
        // /admin/ is platform tier and incidents are platform-global.
        $q = SecurityIncident::withoutGlobalScope('isolate_demo')->orderByDesc('detected_at');

        if ($request->filled('severity')) {
            $q->where('severity', $request->string('severity')->toString());
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        $incidents = $q->paginate(20)->withQueryString();

        return view('portals.admin.security_ops.incidents', compact('incidents'));
    }

    /** One incident on its own page: the full summary, who opened it, and the update form. */
    public function incidentShow(string $id)
    {
        $this->assertUuid($id);

        $incident = SecurityIncident::withoutGlobalScope('isolate_demo')->with('creator')->findOrFail($id);

        return view('portals.admin.security_ops.incident_show', compact('incident'));
    }

    public function incidentStore(Request $request)
    {
        $data = $request->validate([
            'incident_type' => 'required|string|max:100',
            'severity' => 'required|in:low,medium,high,critical',
            'summary' => 'required|string|max:2000',
            'detected_at' => 'required|date',
        ]);

        try {
            // created_by is a uuid FK to users here (the main platform wrote an
            // email string into it, which this schema would reject).
            // Group D import: opened through the platform's SecurityIncidentService,
            // which adds the audit_events row this desk used to skip.
            app(\App\Modules\SecurityOperations\Services\SecurityIncidentService::class)
                ->openIncident($data, (string) $request->user()->id);

            return redirect()->route('portals.admin.security.incidents')
                ->with('success', __('flash.security_incident_logged'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $this->failureMessage($e));
        }
    }

    public function incidentUpdate(Request $request, string $id)
    {
        $this->assertUuid($id);

        $data = $request->validate([
            'status' => 'required|in:open,investigating,contained,resolved,closed',
            'summary' => 'nullable|string|max:2000',
            'contained_at' => 'nullable|date',
            'resolved_at' => 'nullable|date',
        ]);

        try {
            $incident = SecurityIncident::withoutGlobalScope('isolate_demo')->findOrFail($id);
            $incident->update(array_filter($data, fn ($v) => $v !== null));

            return redirect()->to($request->boolean('stay')
                    ? route('portals.admin.security.incidents.show', $incident->id)
                    : route('portals.admin.security.incidents'))
                ->with('success', __('flash.incident_updated'));
        } catch (Throwable $e) {
            return back()->with('error', $this->failureMessage($e));
        }
    }

    // ── Audit explorer ────────────────────────────────────────────

    public function auditExplorer(Request $request)
    {
        // Group D import: every use of the explorer is itself logged (AuditExplorerService).
        rescue(fn () => app(\App\Modules\SecurityOperations\Services\AuditExplorerService::class)
            ->recordSearch((string) $request->user()->id), null, true);

        $q = AuditEvent::orderByDesc('created_at');

        if ($request->filled('action_type')) {
            $q->where('action_type', $request->string('action_type')->toString());
        }
        if ($request->filled('actor_id')) {
            $q->where('actor_id', $request->string('actor_id')->toString());
        }
        if ($request->filled('resource_type')) {
            $q->where('resource_type', $request->string('resource_type')->toString());
        }
        if ($request->filled('date_from')) {
            $q->where('created_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $q->where('created_at', '<=', $request->string('date_to')->toString().' 23:59:59');
        }
        if ($request->boolean('emergency_only')) {
            $q->where('emergency_override', true);
        }

        $events = $q->paginate(30)->withQueryString();
        $actionTypes = AuditEvent::select('action_type')->distinct()->orderBy('action_type')->pluck('action_type');
        $resourceTypes = AuditEvent::select('resource_type')->distinct()->orderBy('resource_type')->pluck('resource_type');

        return view('portals.admin.security_ops.audit_explorer', compact('events', 'actionTypes', 'resourceTypes'));
    }

    // ── Query helpers ─────────────────────────────────────────────

    /** Base query: every override, demo isolation lifted, patient by Health ID only. */
    private function events(): Builder
    {
        return EmergencyAccessEvent::withoutGlobalScope('isolate_demo')
            ->with(['patient' => fn ($q) => $q->withoutGlobalScope('isolate_demo')]);
    }

    private function unreviewedEvents(): Builder
    {
        return $this->whereUnreviewed($this->events());
    }

    /** An override is unreviewed until a review case exists that is past 'pending'. */
    private function whereUnreviewed(Builder $q): Builder
    {
        return $q->whereNotExists($this->reviewedExists());
    }

    private function reviewedExists(): callable
    {
        return function (QueryBuilder $sub) {
            $sub->selectRaw('1')
                ->from('emergency_review_cases')
                ->whereColumn('emergency_review_cases.emergency_access_event_id', 'emergency_access_events.id')
                ->where('emergency_review_cases.status', '!=', 'pending');
        };
    }

    /** Review cases for a set of events, keyed by event id. */
    private function reviewsFor($events)
    {
        $ids = collect($events)->pluck('id')->filter()->unique()->all();

        if (empty($ids)) {
            return collect();
        }

        return EmergencyReviewCase::withoutGlobalScope('isolate_demo')
            ->whereIn('emergency_access_event_id', $ids)
            ->get()
            ->keyBy('emergency_access_event_id');
    }

    /**
     * What the operator is told when an action fails.
     *
     * NEVER the raw exception message. A malformed id used to reach the
     * Postgres driver, and the resulting 22P02 message — which carries the
     * whole SQL statement, the database name, the host and the port — was
     * being flashed straight onto the page. The detail belongs in the log,
     * where the operator cannot read it and an engineer can.
     */
    private function failureMessage(Throwable $e): string
    {
        Log::warning('security ops action failed', [
            'exception' => $e::class,
            'message'   => $e->getMessage(),
        ]);

        return __('security_ops.action_failed');
    }

    /**
     * A route id that is not a uuid is a 404, not a database error.
     *
     * Without this the value reaches the driver and Postgres raises 22P02 —
     * which also aborts the surrounding transaction, so every later query on
     * the connection fails too.
     */
    private function assertUuid(string $id): void
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            abort(404);
        }
    }
}
