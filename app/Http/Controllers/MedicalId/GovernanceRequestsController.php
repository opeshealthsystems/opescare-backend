<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Models\RecordCorrectionDecision;
use App\Modules\Governance\Services\GovernanceDeskService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * GovernanceRequestsController — the patient-rights desk.
 *
 * THE DEFECT THIS CLOSES. Patients can file a data-export request and a
 * record-correction request from the shipped mobile app; both land in
 * `data_export_requests` / `correction_requests` with status `pending`.
 * `DataExportService::downloadExport()` refuses to produce anything unless
 * the request is `approved`. The only code able to approve was
 * `Api\V1\Admin\AdminGovernanceController`, which has never had a single
 * route registered and no screen at all. A patient exercised a statutory
 * right under Cameroon Law No. 2010/012, the request was stored, and nobody
 * on this platform could act on it — ever. This is that door.
 *
 * PLATFORM TIER. The desk lives under /admin/, which `RequirePlatformAdmin`
 * treats as platform-only in full: a facility-tier user gets a 403 from the
 * middleware before this controller is reached. Patient-rights requests are
 * platform-wide by nature — a patient's record spans facilities, so a
 * facility-scoped reviewer could not see the request they were deciding.
 *
 * NO FACILITY FROM THE REQUEST. The facility id is read from
 * `$request->attributes` — set by the auth middleware — and from nowhere
 * else. It is used only to stamp the audit trail; it never widens or narrows
 * what this desk shows, and if the middleware set none, none is recorded.
 * A platform-tier action legitimately has no facility, and inventing one puts
 * a false facility on a compliance row.
 *
 * NO PATIENT ENUMERATION. The queue lists requests, not patients: it can be
 * filtered by status and type only. There is no name box and no Health-ID
 * search, so this screen cannot be used to find out whether a given person is
 * a patient here.
 */
class GovernanceRequestsController extends Controller
{
    /** Statuses the two request tables may hold. Matches the migrations. */
    private const EXPORT_STATUSES     = ['pending', 'approved', 'rejected', 'downloaded', 'expired'];
    private const CORRECTION_STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct(
        private readonly GovernanceDeskService $desk,
    ) {
    }

    // ── Queue ────────────────────────────────────────────────────────────

    /**
     * The queue, both kinds of request in one place.
     *
     * Pending first and oldest-pending first inside that, because the clock a
     * statutory request runs against starts when the patient files it, not
     * when somebody notices.
     */
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $exports = DataExportRequest::query()
            ->when(in_array($status, self::EXPORT_STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'exports')
            ->withQueryString();

        $corrections = CorrectionRequest::query()
            ->when(in_array($status, self::CORRECTION_STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('created_at')
            ->paginate(25, ['*'], 'corrections')
            ->withQueryString();

        // Postgres. TO_CHAR, never DATE_FORMAT.
        $exportCounts = DataExportRequest::query()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $correctionCounts = CorrectionRequest::query()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Which corrections this desk is able to act on at all, worked out
        // here so the screen never draws an approve button it cannot honour.
        $refusals = [];
        foreach ($corrections as $correction) {
            $refusals[$correction->id] = $this->desk->correctionRefusalReason($correction);
        }

        // The oldest thing still waiting, in days. A queue with a 40-day-old
        // pending request is a different fact from a queue with three items,
        // and only one of those two numbers says so.
        $oldestPending = DataExportRequest::where('status', 'pending')->min('created_at');
        $oldestCorrection = CorrectionRequest::where('status', 'pending')->min('created_at');
        $oldest = collect([$oldestPending, $oldestCorrection])->filter()->min();
        $oldestDays = $oldest ? (int) now()->diffInDays(\Illuminate\Support\Carbon::parse($oldest)) : null;

        $statuses = self::EXPORT_STATUSES;

        return view('portals.admin.governance.index', compact(
            'exports', 'corrections', 'exportCounts', 'correctionCounts',
            'refusals', 'status', 'statuses', 'oldestDays'
        ));
    }

    // ── One export request ───────────────────────────────────────────────

    public function showExport(Request $request, string $id)
    {
        $export = $this->findExport($id);
        $history = $this->desk->history('data_export_request', (string) $export->id);

        return view('portals.admin.governance.export', compact('export', 'history'));
    }

    /**
     * Approve an export.
     *
     * The reason is required on the grant as well as the refusal. "Because
     * the law says so" is a perfectly good reason; an unexplained approval of
     * a full-record disclosure is not.
     */
    public function approveExport(Request $request, string $id)
    {
        $export = $this->findExport($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);

        $result = $this->desk->approveExport(
            $export, $actorId, $actorRole, $facilityId, $ip, trim($validated['reason'])
        );

        return $result['ok']
            ? back()->with('success', __('governance_desk.flash_export_approved'))
            : back()->with('error', __($result['reason']));
    }

    /** Refuse an export. The reason is mandatory — see the class docblock. */
    public function refuseExport(Request $request, string $id)
    {
        $export = $this->findExport($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);

        $result = $this->desk->refuseExport(
            $export, $actorId, $actorRole, $facilityId, $ip, trim($validated['reason'])
        );

        return $result['ok']
            ? back()->with('success', __('governance_desk.flash_export_refused'))
            : back()->with('error', __($result['reason']));
    }

    // ── One correction request ───────────────────────────────────────────

    public function showCorrection(Request $request, string $id)
    {
        $correction = $this->findCorrection($id);

        $refusal   = $this->desk->correctionRefusalReason($correction);
        $target    = $this->desk->targetRecord($correction);
        $decisions = RecordCorrectionDecision::where('correction_request_id', $correction->id)
            ->orderByDesc('decided_at')->get();
        $history   = $this->desk->history('correction_request', (string) $correction->id);

        return view('portals.admin.governance.correction', compact(
            'correction', 'refusal', 'target', 'decisions', 'history'
        ));
    }

    /**
     * Approve a correction — an AMENDMENT, never an overwrite.
     *
     * Refused outright for any resource type this desk cannot amend safely.
     * The refusal is shown on the screen before the button is drawn and
     * enforced again in the service, because a desk that says "approved" and
     * silently changes nothing is worse than one that says no.
     */
    public function approveCorrection(Request $request, string $id)
    {
        $correction = $this->findCorrection($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);

        $result = $this->desk->approveCorrection(
            $correction, $actorId, $actorRole, $facilityId, $ip, trim($validated['reason'])
        );

        return $result['ok']
            ? back()->with('success', __('governance_desk.flash_correction_approved'))
            : back()->with('error', __($result['reason']));
    }

    /** Refuse a correction. Reason mandatory and shown to the patient. */
    public function refuseCorrection(Request $request, string $id)
    {
        $correction = $this->findCorrection($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$actorId, $actorRole, $facilityId, $ip] = $this->actor($request);

        $result = $this->desk->refuseCorrection(
            $correction, $actorId, $actorRole, $facilityId, $ip, trim($validated['reason'])
        );

        return $result['ok']
            ? back()->with('success', __('governance_desk.flash_correction_refused'))
            : back()->with('error', __($result['reason']));
    }

    // ── Decision log ─────────────────────────────────────────────────────

    /** Every decision this desk has taken, newest first. */
    public function log(Request $request)
    {
        $events = AuditEvent::query()
            ->where('source_system', 'governance_desk')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('portals.admin.governance.log', compact('events'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * The uuid shape is checked before the query so a malformed id is an
     * honest 404 rather than a Postgres 22P02 surfacing as a 500.
     */
    private function findExport(string $id): DataExportRequest
    {
        abort_unless(Str::isUuid($id), 404);

        return DataExportRequest::query()->findOrFail($id);
    }

    private function findCorrection(string $id): CorrectionRequest
    {
        abort_unless(Str::isUuid($id), 404);

        return CorrectionRequest::query()->findOrFail($id);
    }

    /**
     * Actor, role, facility and IP for the audit trail.
     *
     * `facility_id` comes from `$request->attributes` only — never a header,
     * a body field, a query string or "the one facility we found".
     *
     * @return array{0:?string,1:?string,2:?string,3:?string}
     */
    private function actor(Request $request): array
    {
        $user = $request->user();
        $facilityId = $request->attributes->get('facility_id');

        return [
            $user?->id ? (string) $user->id : null,
            $user?->role?->name,
            is_string($facilityId) && Str::isUuid($facilityId) ? $facilityId : null,
            $request->ip(),
        ];
    }
}
