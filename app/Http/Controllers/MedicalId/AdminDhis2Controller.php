<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Jobs\PushPublicHealthToDhis2Job;
use App\Models\AuditEvent;
use App\Models\Dhis2DataElementMapping;
use App\Models\Dhis2OrgUnitMapping;
use App\Models\Dhis2PushRecord;
use App\Models\Facility;
use App\Models\PublicHealthReport;
use App\Models\ReportItem;
use App\Models\ReportStatusHistory;
use App\Modules\PublicHealth\Services\DataQualityCheckService;
use App\Modules\PublicHealth\Services\DraftGenerationService;
use App\Services\Integration\Dhis2Service;
use App\Services\Interoperability\Dhis2PushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DHIS2 aggregate reporting desk (docs/PLATFORM_IMPORTS.md, group E).
 *
 * The platform has no DHIS2 admin screen (its command pushed on a schedule with
 * no review); this desk is the Finder's review gate over the imported services:
 * generate draft -> read values + data-quality warnings -> approve -> push (queued).
 * Every step writes an audit event (who, when, what). Platform tier: /admin/*.
 */
class AdminDhis2Controller extends Controller
{
    private const UID = '/^[A-Za-z][A-Za-z0-9]{10}$/';

    public function __construct(
        private readonly Dhis2Service $dhis2,
        private readonly Dhis2PushService $pusher,
        private readonly DraftGenerationService $drafts,
    ) {}

    public function index(Request $request): View
    {
        $type = $this->drafts->reportType();
        $reports = PublicHealthReport::where('report_type_id', $type->id)
            ->with('facility:id,name')
            ->withCount('items')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('reporting_period_start')->orderByDesc('updated_at')
            ->paginate(25)->withQueryString();

        $mapped = Dhis2OrgUnitMapping::pluck('org_unit_id', 'facility_id');

        return view('portals.admin.dhis2.index', [
            'configured' => $this->dhis2->isConfigured(),
            'reports' => $reports,
            'mapped' => $mapped,
            'mappedFacilities' => Facility::whereIn('id', $mapped->keys())->orderBy('name')->get(['id', 'name']),
            'defaultMonth' => now()->subMonth()->format('Y-m'),
            'connection' => session('dhis2_connection'),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'facility_id' => ['nullable', 'uuid', 'exists:facilities,id'],
        ]);

        $facilityIds = $data['facility_id'] ?? null
            ? [$data['facility_id']]
            : Dhis2OrgUnitMapping::pluck('facility_id')->all();

        $reports = $this->drafts->generateDrafts($facilityIds, $data['month'], (string) $request->user()->id);

        $this->audit($request, 'dhis2.draft.generated', null, null, [
            'month' => $data['month'],
            'facility_ids' => $facilityIds,
            'report_ids' => array_map(fn ($r) => $r->id, $reports),
        ]);

        if (count($reports) === 1) {
            return redirect()->route('portals.admin.dhis2.drafts.show', $reports[0]->id)
                ->with('success', __('dhis2.flash_generated', ['count' => 1]));
        }

        return redirect()->route('portals.admin.dhis2')
            ->with(count($reports) ? 'success' : 'error', count($reports)
                ? __('dhis2.flash_generated', ['count' => count($reports)])
                : __('dhis2.flash_nothing_generated'));
    }

    public function show(string $id): View
    {
        $report = $this->report($id);
        $report->load(['facility:id,name,status', 'items', 'qualityChecks', 'statusHistory']);

        $items = $report->items->sortBy('indicator_code')->map(fn (ReportItem $item) => [
            'item' => $item,
            'mapping' => $this->pusher->dataElementFor($item->indicator_code),
        ]);

        return view('portals.admin.dhis2.draft', [
            'report' => $report,
            'items' => $items,
            'orgUnit' => $this->pusher->orgUnitFor($report->facility_id),
            'configured' => $this->dhis2->isConfigured(),
            'period' => $report->reporting_period_start->format('Ym'),
            'records' => Dhis2PushRecord::where('report_id', $report->id)->orderBy('indicator_code')->get()->keyBy('indicator_code'),
            'blocking' => $report->qualityChecks->where('status', 'failed')->count(),
            'history' => $report->statusHistory->sortByDesc('changed_at'),
            // One row per push attempt, written by PushPublicHealthToDhis2Job.
            'attempts' => AuditEvent::where('resource_type', 'public_health_report')->where('resource_id', $report->id)
                ->where('action_type', 'like', 'dhis2.push.%')->where('action_type', '!=', 'dhis2.push.requested')
                ->where('action_type', '!=', 'dhis2.push.refused_not_approved')->orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function regenerate(Request $request, string $id): RedirectResponse
    {
        $report = $this->report($id);
        if ($report->status !== 'draft') {
            return back()->with('error', __('dhis2.flash_not_draft'));
        }

        $this->drafts->generateMonthlyDraft($report->facility_id, $report->reporting_period_start->format('Y-m'), (string) $request->user()->id);
        $this->audit($request, 'dhis2.draft.regenerated', $report->id, $report->facility_id, []);

        return back()->with('success', __('dhis2.flash_regenerated'));
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $report = $this->report($id);
        if ($report->status !== 'draft') {
            return back()->with('error', __('dhis2.flash_not_draft'));
        }

        // Re-run the checks: mappings may have changed since the draft was built.
        app(DataQualityCheckService::class)->runQualityChecks($report->load('items'));
        if ($report->qualityChecks()->where('status', 'failed')->exists()) {
            return back()->with('error', __('dhis2.flash_blocked_by_checks'));
        }

        $report->forceFill(['status' => 'approved_for_submission', 'updated_by' => $request->user()->id])->save();
        ReportStatusHistory::create([
            'report_id' => $report->id,
            'old_status' => 'draft',
            'new_status' => 'approved_for_submission',
            'changed_by' => $request->user()->id,
            'reason' => 'approved',
            'changed_at' => now(),
        ]);

        $this->audit($request, 'dhis2.draft.approved', $report->id, $report->facility_id, [
            'values' => $report->items->pluck('value', 'indicator_code')->all(),
        ]);

        return back()->with('success', __('dhis2.flash_approved'));
    }

    public function push(Request $request, string $id): RedirectResponse
    {
        $report = $this->report($id);

        if (! in_array($report->status, Dhis2PushService::PUSHABLE_STATUSES, true)) {
            $this->audit($request, 'dhis2.push.refused_not_approved', $report->id, $report->facility_id, []);

            return back()->with('error', __('dhis2.flash_push_not_approved'));
        }
        if (! $this->dhis2->isConfigured()) {
            return back()->with('error', __('dhis2.not_configured'));
        }
        if ($this->pusher->orgUnitFor($report->facility_id) === null) {
            return back()->with('error', __('dhis2.flash_not_mapped'));
        }

        $this->audit($request, 'dhis2.push.requested', $report->id, $report->facility_id, []);
        PushPublicHealthToDhis2Job::dispatch($report->id, (string) $request->user()->id);

        return back()->with('success', __('dhis2.flash_push_queued'));
    }

    public function testConnection(Request $request): RedirectResponse
    {
        if (! $this->dhis2->isConfigured()) {
            return back()->with('error', __('dhis2.not_configured'));
        }

        $result = $this->dhis2->testConnection();
        $this->audit($request, 'dhis2.connection.tested', null, null, ['connected' => $result['connected']]);

        return back()->with('dhis2_connection', $result);
    }

    public function mappings(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $mappings = Dhis2OrgUnitMapping::orderBy('updated_at', 'desc')->get();
        $facilityNames = Facility::whereIn('id', $mappings->pluck('facility_id'))->pluck('name', 'id');

        $candidates = $q === '' ? collect() : Facility::query()
            ->where('name', 'ILIKE', '%' . addcslashes($q, '%_\\') . '%')
            ->orderBy('name')->limit(25)->get(['id', 'name', 'type', 'status']);

        $codes = collect(array_keys((array) config('dhis2.data_element_map')))
            ->merge(ReportItem::query()->distinct()->pluck('indicator_code'))
            ->merge(Dhis2DataElementMapping::pluck('indicator_code'))
            ->unique()->sort()->values();

        return view('portals.admin.dhis2.mappings', [
            'q' => $q,
            'mappings' => $mappings,
            'facilityNames' => $facilityNames,
            'candidates' => $candidates,
            'mappedIds' => $mappings->pluck('org_unit_id', 'facility_id'),
            'codes' => $codes,
            'elementMappings' => Dhis2DataElementMapping::all()->keyBy('indicator_code'),
            'envMap' => array_filter((array) config('dhis2.data_element_map')),
        ]);
    }

    public function saveOrgUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'uuid', 'exists:facilities,id'],
            'org_unit_id' => ['nullable', 'regex:' . self::UID],
        ]);

        $before = Dhis2OrgUnitMapping::where('facility_id', $data['facility_id'])->value('org_unit_id');

        if (empty($data['org_unit_id'])) {
            // Unmapping is configuration, not a clinical record: the row goes, the audit keeps it.
            Dhis2OrgUnitMapping::where('facility_id', $data['facility_id'])->delete();
        } else {
            Dhis2OrgUnitMapping::updateOrCreate(
                ['facility_id' => $data['facility_id']],
                ['org_unit_id' => $data['org_unit_id'], 'set_by' => $request->user()->id],
            );
        }

        $this->audit($request, 'dhis2.mapping.org_unit_set', null, $data['facility_id'], ['org_unit_id' => $data['org_unit_id'] ?? null], ['org_unit_id' => $before]);

        return back()->with('success', __('dhis2.flash_mapping_saved'));
    }

    public function saveDataElement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'indicator_code' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_.]+$/'],
            'data_element_id' => ['nullable', 'regex:' . self::UID],
            'category_option_combo_id' => ['nullable', 'regex:' . self::UID],
        ]);

        $before = Dhis2DataElementMapping::where('indicator_code', $data['indicator_code'])->first()?->only(['data_element_id', 'category_option_combo_id']);

        if (empty($data['data_element_id'])) {
            Dhis2DataElementMapping::where('indicator_code', $data['indicator_code'])->delete();
        } else {
            Dhis2DataElementMapping::updateOrCreate(
                ['indicator_code' => $data['indicator_code']],
                [
                    'data_element_id' => $data['data_element_id'],
                    'category_option_combo_id' => $data['category_option_combo_id'] ?? null,
                    'set_by' => $request->user()->id,
                ],
            );
        }

        $this->audit($request, 'dhis2.mapping.data_element_set', null, null, $data, $before ?? []);

        return back()->with('success', __('dhis2.flash_mapping_saved'));
    }

    private function report(string $id): PublicHealthReport
    {
        return PublicHealthReport::where('report_type_id', $this->drafts->reportType()->id)->findOrFail($id);
    }

    private function audit(Request $request, string $action, ?string $reportId, ?string $facilityId, array $after, array $before = []): void
    {
        AuditEvent::create([
            'actor_id' => $request->user()->id,
            'actor_role' => $request->user()->role?->name ?? 'platform_admin',
            'facility_id' => $facilityId,
            'action_type' => $action,
            'resource_type' => $reportId ? 'public_health_report' : 'dhis2_reporting',
            'resource_id' => $reportId,
            'source_system' => 'finder_dhis2',
            'ip_address' => $request->ip(),
            'before_state' => $before,
            'after_state' => $after,
            'created_at' => now(),
        ]);
    }
}
