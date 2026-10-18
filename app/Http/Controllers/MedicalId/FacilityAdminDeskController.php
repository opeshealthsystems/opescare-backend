<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Models\Ward;
use App\Services\Portal\FacilityAdminDeskService;
use App\Services\Portal\PortalContextService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FacilityAdminDeskController — the facility administrator's own desk.
 *
 * Two pages and three writes, all under /facility/team/… because that is the
 * one portal bucket EnsurePortalAccess admits the facility-administration
 * roles to (everything under /admin/* is platform god-mode). The gate is the
 * existing `capability:facility_team`; no capability was invented for this.
 *
 * THE FACILITY IS NEVER TAKEN FROM THE REQUEST. It comes from
 * PortalContextService::facilityId(); a member of another facility is a 404,
 * not a 403 that confirms the id exists. THE ROLE COMES FROM AN ALLOW-LIST:
 * FacilityStaffInvite::INVITABLE_ROLES, the same list the invite form uses,
 * so this desk can neither hand out nor take away an administration role.
 */
class FacilityAdminDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $ctx,
        private readonly FacilityAdminDeskService $desk,
    ) {}

    private function facilityId(): string
    {
        $facilityId = $this->ctx->facilityId();

        abort_if($facilityId === null, 403, __('facility_admin.no_facility_context'));

        return $facilityId;
    }

    // ── Overview ─────────────────────────────────────────────────────────

    public function overview(): View
    {
        $facilityId = $this->facilityId();

        $facility = Facility::query()->whereKey($facilityId)->first()
            ?? Facility::withoutGlobalScopes()->whereKey($facilityId)->first();

        return view('portals.facility.admin.overview', [
            'facility' => $facility,
            'day'      => now(),
            'kpis'     => $this->desk->overview($facilityId, now()),
        ]);
    }

    // ── Read-only invoices (H-4) ─────────────────────────────────────────

    /**
     * The administrator's view of the till: this facility's invoices, no
     * action. See routes/facility_admin.php for why `capability:billing` was
     * not widened instead.
     */
    public function invoices(): View
    {
        $facilityId = $this->facilityId();
        $invoices   = $this->desk->invoices($facilityId);

        return view('portals.facility.admin.invoices', [
            'invoices' => $invoices,
            'patients' => $this->desk->patientsFor($invoices->items()),
        ]);
    }

    // ── Departments (H-5, read only) ─────────────────────────────────────

    public function departments(): View
    {
        $facilityId = $this->facilityId();

        return view('portals.facility.admin.departments', [
            'departments' => $this->desk->departments($facilityId),
        ]);
    }

    // ── Reports & exports (H-6) ──────────────────────────────────────────

    /**
     * The date range a report covers: `from`/`to` as Y-m-d, defaulting to
     * this month, `to` never before `from`, at most REPORT_MAX_DAYS
     * inclusive. A bad range is a validation error, not a silently clamped one.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function reportRange(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = $request->filled('from') ? Carbon::createFromFormat('Y-m-d', $request->input('from'))->startOfDay() : now()->startOfMonth();
        $to   = $request->filled('to')   ? Carbon::createFromFormat('Y-m-d', $request->input('to'))->startOfDay()   : now()->startOfDay();

        if ($to->lt($from)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to' => __('facility_admin.reports_range_order')]);
        }

        if ($from->diffInDays($to) + 1 > FacilityAdminDeskService::REPORT_MAX_DAYS) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'to' => __('facility_admin.reports_range_too_long', ['days' => FacilityAdminDeskService::REPORT_MAX_DAYS]),
            ]);
        }

        return [$from, $to];
    }

    public function reports(Request $request): View
    {
        $facilityId = $this->facilityId();
        [$from, $to] = $this->reportRange($request);

        return view('portals.facility.admin.reports', [
            'from'    => $from,
            'to'      => $to,
            'reports' => $this->desk->reports($facilityId, $from, $to),
            'tables'  => FacilityAdminDeskService::REPORT_TABLES,
        ]);
    }

    /**
     * One table as a streamed CSV: UTF-8 BOM so Excel reads the accents,
     * the facility slug and the range in the filename. Every cell is a
     * count, a median or a whole-franc sum — the service never selects a
     * patient column, so no name or Health ID can reach the file.
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $facilityId = $this->facilityId();

        $request->validate(['table' => ['required', 'in:' . implode(',', FacilityAdminDeskService::REPORT_TABLES)]]);
        [$from, $to] = $this->reportRange($request);

        $table = $request->input('table');
        [$headers, $rows] = $this->desk->reportTable($table, $facilityId, $from, $to);

        $facility = Facility::withoutGlobalScopes()->whereKey($facilityId)->first();
        $slug     = Str::slug((string) ($facility?->name ?? $facilityId)) ?: 'facility';
        $filename = sprintf('%s_%s_%s_%s.csv', $slug, $table, $from->toDateString(), $to->toDateString());

        // resource_id is a uuid column: the facility reported on, with the
        // table named in after_state.
        $this->ctx->auditPatientAccess(
            actionType:   'facility_report_exported',
            resourceType: 'FacilityReport',
            resourceId:   $facilityId,
            extra: ['after_state' => ['table' => $table, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'rows' => count($rows)]],
        );

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, \App\Support\CsvSafe::row(array_map(fn ($v) => $v ?? '', $row)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Wards & beds (H-5) ───────────────────────────────────────────────

    public function wards(): View
    {
        $facilityId = $this->facilityId();
        $board      = $this->desk->wardBoard($facilityId);

        $beds = [];
        foreach ($board['wards'] as $w) {
            $beds[$w['id']] = Bed::query()->where('ward_id', $w['id'])->orderBy('bed_number')->get();
        }

        return view('portals.facility.admin.wards', [
            'board'     => $board,
            'bedsByWard' => $beds,
            'wardTypes' => array_keys(Ward::wardTypes()),
        ]);
    }

    public function createWard(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $v = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'ward_type' => ['required', 'string', 'in:' . implode(',', array_keys(Ward::wardTypes()))],
            'capacity'  => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $ward = $this->desk->createWard($facilityId, trim($v['name']), $v['ward_type'], (int) $v['capacity']);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_ward_created',
            resourceType: 'Ward',
            resourceId:   $ward->id,
            extra: ['after_state' => ['name' => $ward->name, 'ward_type' => $ward->ward_type, 'total_beds' => $ward->total_beds]],
        );

        return redirect()->route('portals.facility.admin.wards')
            ->with('success', __('facility_admin.ward_created', ['name' => $ward->name]));
    }

    public function addBeds(Request $request, string $wardId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $ward = $this->desk->ward($facilityId, $wardId);
        abort_if($ward === null, 404);

        $v = $request->validate([
            'count'  => ['required', 'integer', 'min:1', 'max:200'],
            'prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        $prefix = strtoupper($v['prefix'] ?? '') ?: strtoupper(Str::substr(Str::slug($ward->name, ''), 0, 1)) ?: 'B';
        $before = Bed::query()->where('ward_id', $ward->id)->count();
        $labels = $this->desk->addBeds($ward, (int) $v['count'], $prefix);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_ward_beds_added',
            resourceType: 'Ward',
            resourceId:   $ward->id,
            extra: [
                'before_state' => ['beds' => $before],
                'after_state'  => ['beds' => $before + count($labels), 'labels' => $labels],
            ],
        );

        return redirect()->route('portals.facility.admin.wards')
            ->with('success', __('facility_admin.beds_added', ['count' => count($labels), 'ward' => $ward->name]));
    }

    public function setBedService(Request $request, string $wardId, string $bedId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $ward = $this->desk->ward($facilityId, $wardId);
        abort_if($ward === null, 404);

        $bed = Str::isUuid($bedId) ? Bed::query()->where('ward_id', $ward->id)->whereKey($bedId)->first() : null;
        abort_if($bed === null, 404);

        $v = $request->validate(['in_service' => ['required', 'boolean']]);

        $change = $this->desk->setBedService($bed, (bool) $v['in_service']);

        if ($change === null) {
            return redirect()->route('portals.facility.admin.wards')
                ->with('error', __('facility_admin.bed_status_unchanged', ['bed' => $bed->bed_number]));
        }

        [$before, $after] = $change;

        $this->ctx->auditPatientAccess(
            actionType:   'facility_bed_service_changed',
            resourceType: 'Bed',
            resourceId:   $bed->id,
            extra: [
                'before_state' => ['status' => $before, 'ward_id' => $ward->id],
                'after_state'  => ['status' => $after, 'ward_id' => $ward->id],
            ],
        );

        return redirect()->route('portals.facility.admin.wards')
            ->with('success', __($after === FacilityAdminDeskService::BED_IN_SERVICE ? 'facility_admin.bed_back_in_service' : 'facility_admin.bed_out_of_service', ['bed' => $bed->bed_number]));
    }

    // ── Member page ──────────────────────────────────────────────────────

    public function member(string $userId): View
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        $postings = FacilityRoleAssignment::query()
            ->with(['role', 'facility' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('user_id', $member->id)
            ->orderByDesc('is_active')
            ->orderByDesc('assigned_at')
            ->get();

        $credentials = $member->providerCredentials()
            ->orderByRaw('verified_at IS NULL DESC')
            ->orderBy('expiry_date')
            ->get();

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_profile_view',
            resourceType: 'User',
            resourceId:   $member->id,
        );

        return view('portals.facility.admin.member', [
            'member'      => $member,
            'facilityId'  => $facilityId,
            'roleHere'    => $this->desk->roleNameAt($facilityId, $member),
            'assignment'  => $this->desk->activeAssignment($facilityId, $member->id),
            'postings'    => $postings,
            'credentials' => $credentials,
            'profile'     => $member->staffProfile,
            'manageable'  => $this->desk->manageable($facilityId, Auth::user(), $member),
            'roles'       => Role::query()->whereIn('name', FacilityStaffInvite::INVITABLE_ROLES)->orderBy('name')->get(),
        ]);
    }

    // ── Writes ───────────────────────────────────────────────────────────

    public function changeRole(Request $request, string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', FacilityStaffInvite::INVITABLE_ROLES)],
        ]);

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        $role = Role::where('name', $validated['role'])->first();
        if (! $role) {
            return back()->with('error', __('team.role_unknown'));
        }

        [$before, $after] = $this->desk->changeRole($facilityId, $member, $role, Auth::id());

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_role_changed',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['role' => $before],
                'after_state'  => ['role' => $after],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __('facility_admin.role_changed', ['role' => $this->roleLabel($after)]));
    }

    public function deactivate(string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        if ($this->desk->activeAssignment($facilityId, $member->id) === null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.already_inactive'));
        }

        $suspended = $this->desk->deactivate($facilityId, $member);

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_deactivated',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['posting' => 'active'],
                'after_state'  => ['posting' => 'ended', 'account_suspended' => $suspended],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __($suspended ? 'facility_admin.deactivated_and_suspended' : 'facility_admin.deactivated'));
    }

    public function reactivate(string $userId): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $member = $this->desk->member($facilityId, $userId);
        abort_if($member === null, 404);

        if ($this->desk->activeAssignment($facilityId, $member->id) !== null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.already_active'));
        }

        // manageable() reads the role the person LAST held here (the account
        // role is the fallback), so a deactivated administrator stays out of
        // reach of this desk exactly as an active one is.
        if (! $this->desk->manageable($facilityId, Auth::user(), $member)) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('facility_admin.member_not_manageable'));
        }

        $role = $this->desk->reactivate($facilityId, $member, Auth::id());

        if ($role === null) {
            return redirect()->route('portals.facility.admin.member', $member->id)
                ->with('error', __('team.role_unknown'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'facility_member_reactivated',
            resourceType: 'User',
            resourceId:   $member->id,
            extra: [
                'before_state' => ['posting' => 'ended'],
                'after_state'  => ['posting' => 'active', 'role' => $role],
            ],
        );

        return redirect()->route('portals.facility.admin.member', $member->id)
            ->with('success', __('facility_admin.reactivated', ['role' => $this->roleLabel($role)]));
    }

    private function roleLabel(?string $roleName): string
    {
        if ($roleName === null || $roleName === '') {
            return '';
        }

        $label = __('team.roles.' . $roleName);

        return is_string($label) && $label !== 'team.roles.' . $roleName ? $label : $roleName;
    }
}
