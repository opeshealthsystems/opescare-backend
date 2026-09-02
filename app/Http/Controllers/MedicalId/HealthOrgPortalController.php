<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\HealthOrgOutreachEvent;
use App\Models\HealthOrgProgram;
use App\Models\Patient;
use App\Models\PublicHealthReport;
use App\Models\PublicHealthSignal;
use App\Models\SignalReview;
use App\Services\Portal\PortalContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthOrgPortalController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    /**
     * The health organisation acting on this request, or null for a
     * platform-level role that legitimately has no facility.
     *
     * Null means "unscoped" for reads only — the same contract
     * PortalContextService::scopeToFacility() already implements for every
     * other portal. Writes never accept null; see requireFacilityId().
     */
    private function facilityId(): ?string
    {
        return $this->ctx->facilityId();
    }

    /**
     * The facility a new program / outreach event / report is filed under.
     *
     * This used to end in `?? Facility::value('id')`, which answered the
     * question with whichever row Postgres returned first out of 345 — so an
     * NGO admin whose session had lost its facility filed its programs and its
     * MINSANTE reports under a hospital it had never heard of, and the record
     * carried that hospital's name from then on. There is no safe guess: a
     * write with no facility fails closed.
     *
     * The single-facility fallback is honoured only where it is genuinely
     * unambiguous, matching InventoryPortalController::bloodFacilityId().
     */
    private function requireFacilityId(): string
    {
        $resolved = $this->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which organisation this record belongs to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /**
     * Signals are scoped a little wider than the rest of this portal.
     *
     * A signal carries `facility_id = null` when its scope_type is district or
     * region (see SignalDetectionService) — those are not another facility's
     * records and stay visible. A signal that names a *different* facility is,
     * and does not.
     *
     * The two conditions MUST stay grouped: left un-nested, the trailing
     * orWhereNull escapes every other constraint on the query and every
     * facility's signals come back.
     */
    private function scopeSignals(Builder $query): Builder
    {
        $facilityId = $this->facilityId();

        if (! $facilityId) {
            return $query;
        }

        return $query->where(function ($sub) use ($facilityId) {
            $sub->where('public_health_signals.facility_id', $facilityId)
                ->orWhereNull('public_health_signals.facility_id');
        });
    }

    private function actorId(): ?string
    {
        return (string) (auth()->id() ?? '');
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function dashboard()
    {
        $stats = [
            // Patient counts are patient data: scoped to the signed-in
            // organisation, not the national register.
            'patients'      => $this->ctx->scopeToFacility(Patient::query())->count(),
            // Facility count is registry metadata (the same number the public
            // network pages publish), so it stays national on purpose.
            'facilities'    => Facility::where('status', 'active')->count(),
            'programs'      => $this->ctx->scopeToFacility(HealthOrgProgram::query())->where('status', 'active')->count(),
            'outreach'      => $this->ctx->scopeToFacility(HealthOrgOutreachEvent::query())->whereIn('status', ['planned', 'in_progress'])->count(),
            'reports_draft' => $this->ctx->scopeToFacility(PublicHealthReport::query())->where('status', 'draft')->count(),
            'reports_sent'  => $this->ctx->scopeToFacility(PublicHealthReport::query())->where('status', 'submitted')->count(),
            'signals_open'  => $this->scopeSignals(PublicHealthSignal::query())->whereNull('resolved_at')->count(),
        ];

        return view('portals.healthorg.dashboard', compact('stats'));
    }

    // ------------------------------------------------------------------
    // Programs
    // ------------------------------------------------------------------

    public function programs()
    {
        $programs = $this->ctx->scopeToFacility(HealthOrgProgram::query())
            ->withCount('outreachEvents')
            ->orderByDesc('created_at')
            ->paginate(20);

        $programTypes = ['immunization', 'maternal', 'nutrition', 'disease_control', 'education', 'screening'];

        return view('portals.healthorg.programs', compact('programs', 'programTypes'));
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:160',
            'program_type'      => 'nullable|string|max:40',
            'description'       => 'nullable|string|max:1000',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'target_population' => 'nullable|string|max:160',
        ]);

        HealthOrgProgram::create([
            ...$data,
            'facility_id' => $this->requireFacilityId(),
            'status'      => 'active',
            'created_by'  => $this->actorId(),
        ]);

        return redirect()->route('portals.healthorg.programs')->with('success', __('healthorg.program_created'));
    }

    // ------------------------------------------------------------------
    // Outreach events
    // ------------------------------------------------------------------

    public function outreach()
    {
        $events = $this->ctx->scopeToFacility(HealthOrgOutreachEvent::query())
            ->with('program:id,name')
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        $programs = $this->ctx->scopeToFacility(HealthOrgProgram::query())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('portals.healthorg.outreach', compact('events', 'programs'));
    }

    public function storeOutreach(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'             => 'required|string|max:160',
            'program_id'        => 'nullable|uuid|exists:health_org_programs,id',
            'location'          => 'nullable|string|max:200',
            'scheduled_at'      => 'nullable|date',
            'target_population' => 'nullable|string|max:160',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $facilityId = $this->requireFacilityId();

        // `exists:health_org_programs,id` proves the program is real, not that
        // it is ours — without this an event could be hung off another
        // organisation's program and show up in their register.
        if (! empty($data['program_id'])) {
            abort_unless(
                HealthOrgProgram::where('id', $data['program_id'])
                    ->where('facility_id', $facilityId)
                    ->exists(),
                404
            );
        }

        HealthOrgOutreachEvent::create([
            ...$data,
            'facility_id' => $facilityId,
            'status'      => 'planned',
            'created_by'  => $this->actorId(),
        ]);

        return redirect()->route('portals.healthorg.outreach')->with('success', __('healthorg.outreach_created'));
    }

    public function completeOutreach(Request $request, string $id): RedirectResponse
    {
        // Scoped lookup, not a bare findOrFail: the route carries only an id,
        // so an unscoped find lets one organisation close out — and restate the
        // reach figures of — another's outreach event.
        $event = $this->ctx->scopeToFacility(HealthOrgOutreachEvent::query())->findOrFail($id);

        $data = $request->validate(['people_reached' => 'nullable|integer|min:0|max:1000000']);

        $event->update([
            'status'         => 'completed',
            'people_reached' => $data['people_reached'] ?? $event->people_reached,
        ]);

        return redirect()->route('portals.healthorg.outreach')->with('success', __('healthorg.outreach_completed'));
    }

    // ------------------------------------------------------------------
    // Public Health Reports
    // ------------------------------------------------------------------

    public function reports()
    {
        $reports = $this->ctx->scopeToFacility(PublicHealthReport::query())
            ->with('reportType:id,name')
            ->orderByDesc('created_at')
            ->paginate(25);

        $reportTypes = DB::table('public_health_report_types')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sensitivity_level', 'default_review_required']);

        return view('portals.healthorg.reports', compact('reports', 'reportTypes'));
    }

    public function storeReport(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'report_type_id'         => 'required|uuid|exists:public_health_report_types,id',
            'reporting_period_start' => 'required|date',
            'reporting_period_end'   => 'required|date|after_or_equal:reporting_period_start',
            'notes'                  => 'nullable|string|max:2000',
        ]);

        $type = DB::table('public_health_report_types')->find($data['report_type_id']);

        PublicHealthReport::create([
            'report_type_id'         => $data['report_type_id'],
            'facility_id'            => $this->requireFacilityId(),
            'reporting_period_start' => $data['reporting_period_start'],
            'reporting_period_end'   => $data['reporting_period_end'],
            'status'                 => 'draft',
            'sensitivity_level'      => $type->sensitivity_level ?? 'routine',
            'data_classification'    => 'restricted',
            'generated_by_system'    => false,
            'requires_review'        => (bool) ($type->default_review_required ?? false),
            'requires_correction'    => false,
            'payload_json'           => ['notes' => $data['notes'] ?? ''],
            'created_by'             => $this->actorId(),
        ]);

        return redirect()->route('portals.healthorg.reports')->with('success', __('healthorg.report_created'));
    }

    public function submitReport(Request $request, string $id): RedirectResponse
    {
        // A submitted report goes to MINSANTE under the reporting facility's
        // name. Scoped so one organisation cannot submit another's draft.
        $report = $this->ctx->scopeToFacility(PublicHealthReport::query())->findOrFail($id);

        abort_if($report->status !== 'draft', 422, 'Only draft reports can be submitted.');

        $report->update(['status' => 'submitted', 'updated_by' => $this->actorId()]);

        return redirect()->route('portals.healthorg.reports')->with('success', __('healthorg.report_submitted'));
    }

    // ------------------------------------------------------------------
    // Signals — review / triage
    // ------------------------------------------------------------------

    public function signals()
    {
        $signals = $this->scopeSignals(PublicHealthSignal::query())
            ->with('facility:id,name')
            ->orderByDesc('detected_at')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('portals.healthorg.signals', compact('signals'));
    }

    public function reviewSignal(Request $request, string $id): RedirectResponse
    {
        // Same scoping the list uses: a signal raised against another facility
        // cannot be confirmed, dismissed or resolved from here.
        $signal = $this->scopeSignals(PublicHealthSignal::query())->findOrFail($id);

        $data = $request->validate([
            'action'  => 'required|in:confirm,dismiss,escalate,resolve',
            'comment' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($signal, $data) {
            SignalReview::create([
                'signal_id'   => $signal->id,
                'reviewer_id' => $this->actorId(),
                'action'      => $data['action'],
                'comment'     => $data['comment'] ?? null,
                'reviewed_at' => now(),
            ]);

            $update = ['reviewed_at' => now()];
            $update['status'] = match ($data['action']) {
                'confirm'  => 'confirmed',
                'dismiss'  => 'dismissed',
                'escalate' => 'escalated',
                'resolve'  => 'resolved',
            };
            if ($data['action'] === 'resolve') {
                $update['resolved_at'] = now();
            }
            $signal->update($update);
        });

        return redirect()->route('portals.healthorg.signals')->with('success', __('healthorg.signal_reviewed'));
    }
}
