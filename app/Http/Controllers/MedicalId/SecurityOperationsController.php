<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AccessReview;
use App\Models\AuditExport;
use App\Models\BreachReport;
use App\Models\SuspiciousAccessFlag;
use App\Models\User;
use App\Modules\SecurityOperations\Services\AccessReviewService;
use App\Modules\SecurityOperations\Services\BreachWorkflowService;
use App\Modules\SecurityOperations\Services\ComplianceExportService;
use App\Modules\SecurityOperations\Services\SuspiciousAccessDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Security operations — access review, suspicious access, breach workflow,
 * compliance export.
 *
 * Imported from the platform's Api/V1/SecurityOperationsController
 * (docs/PLATFORM_IMPORTS.md, group D) and turned into web pages under the
 * Finder's platform-tier /admin/security prefix. Reconciled with the Finder's
 * existing SecurityOpsController, which already owns the overview, the
 * break-glass review queue (emergency_review_cases: reviewer, note, timestamp),
 * incidents and the audit explorer — none of those are duplicated here.
 *
 * Adaptations:
 *  - the actor is always the signed-in user; the platform read reviewer ids
 *    from the request body;
 *  - no patient search and no patient content anywhere: flags show the user
 *    and counts, exports carry metadata/aggregates only;
 *  - a flag review annotates the flag; it never touches access_logs or
 *    emergency_access_events, which are immutable;
 *  - searchAuditLog was not re-exposed (the Finder audit explorer does more
 *    and now records its searches through AuditExplorerService).
 */
class SecurityOperationsController extends Controller
{
    private const OUTCOMES = ['no_change', 'modified', 'revoked', 'escalated'];

    private const FLAG_DECISIONS = ['reviewed', 'dismissed', 'escalated'];

    public function __construct(
        private readonly SuspiciousAccessDetectionService $suspiciousAccess,
        private readonly BreachWorkflowService $breachWorkflow,
        private readonly AccessReviewService $accessReview,
        private readonly ComplianceExportService $compliance,
    ) {}

    // ── Access review ─────────────────────────────────────────────

    public function accessReviews(): View
    {
        $reviews = AccessReview::orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByDesc('created_at')->paginate(25);

        $subjects = User::withoutGlobalScopes()->whereIn('id', collect($reviews->items())->pluck('reviewed_subject_id'))
            ->get(['id', 'email'])->keyBy('id');

        return view('portals.admin.security_ops.access_reviews', [
            'reviews'  => $reviews,
            'subjects' => $subjects,
            'outcomes' => self::OUTCOMES,
            'overdue'  => $this->accessReview->getOverdueSchedules()->count(),
        ]);
    }

    public function initiateAccessReview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'  => 'required|email|max:255',
            'reason' => 'required|string|max:2000',
        ]);

        $target = User::withoutGlobalScopes()->where('email', $data['email'])->first();
        if (! $target) {
            return back()->withInput()->with('error', __('security_ops.ar_user_not_found'));
        }

        $this->accessReview->initiateReview($target->id, $request->user()->id, $data['reason']);

        return redirect()->route('portals.admin.security.access_review')->with('success', __('security_ops.ar_initiated'));
    }

    public function completeAccessReview(Request $request, string $id): RedirectResponse
    {
        $this->assertUuid($id);
        $data = $request->validate([
            'outcome' => 'required|in:' . implode(',', self::OUTCOMES),
            'notes'   => 'nullable|string|max:2000',
        ]);

        $this->accessReview->completeReview($id, $request->user()->id, $data['outcome'], $data['notes'] ?? null);

        return redirect()->route('portals.admin.security.access_review')->with('success', __('security_ops.ar_completed'));
    }

    // ── Suspicious access ─────────────────────────────────────────

    public function suspiciousAccess(Request $request): View
    {
        $q = SuspiciousAccessFlag::query()
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        $flags = $q->paginate(25)->withQueryString();
        $users = User::withoutGlobalScopes()
            ->whereIn('id', collect($flags->items())->pluck('user_id')->merge(collect($flags->items())->pluck('reviewed_by'))->filter())
            ->get(['id', 'email'])->keyBy('id');

        return view('portals.admin.security_ops.suspicious_access', [
            'flags'     => $flags,
            'users'     => $users,
            'decisions' => self::FLAG_DECISIONS,
            'threshold' => SuspiciousAccessDetectionService::HIGH_VOLUME_THRESHOLD,
            'window'    => SuspiciousAccessDetectionService::HIGH_VOLUME_WINDOW_MINUTES,
        ]);
    }

    public function runDetection(): RedirectResponse
    {
        $flags = $this->suspiciousAccess->scan();

        return redirect()->route('portals.admin.security.suspicious_access')
            ->with('success', __('security_ops.sa_scan_done', ['count' => count($flags)]));
    }

    public function reviewFlag(Request $request, string $id): RedirectResponse
    {
        $this->assertUuid($id);
        $data = $request->validate([
            'decision' => 'required|in:' . implode(',', self::FLAG_DECISIONS),
            'notes'    => 'required|string|max:2000',
        ]);

        SuspiciousAccessFlag::findOrFail($id)->review($request->user()->id, $data['decision'], $data['notes']);

        return redirect()->route('portals.admin.security.suspicious_access')->with('success', __('security_ops.sa_reviewed'));
    }

    // ── Breach workflow ───────────────────────────────────────────

    public function breaches(): View
    {
        $breaches = BreachReport::orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('discovered_at')->paginate(20);

        return view('portals.admin.security_ops.breaches', [
            'breaches' => $breaches,
            'overdue'  => $this->breachWorkflow->getBreachesRequiringRegulatoryAction()->pluck('id')->all(),
        ]);
    }

    public function openBreach(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'                      => 'nullable|string|max:255',
            'description'                => 'required|string|max:5000',
            'breach_type'                => 'required|in:unauthorized_access,data_leak,ransomware,lost_device,insider_threat',
            'severity'                   => 'required|in:low,medium,high,critical',
            'discovered_at'              => 'nullable|date',
            'estimated_affected_records' => 'nullable|integer|min:0',
        ]);

        $this->breachWorkflow->openBreach(array_filter($data, fn ($v) => $v !== null), $request->user()->id);

        return redirect()->route('portals.admin.security.breaches')->with('success', __('security_ops.br_opened'));
    }

    public function containBreach(Request $request, string $id): RedirectResponse
    {
        $this->assertUuid($id);
        $data = $request->validate(['notes' => 'required|string|max:2000']);
        $this->breachWorkflow->contain($id, $request->user()->id, $data['notes']);

        return redirect()->route('portals.admin.security.breaches')->with('success', __('security_ops.br_contained'));
    }

    public function notifyBreach(Request $request, string $id): RedirectResponse
    {
        $this->assertUuid($id);
        $this->breachWorkflow->markNotified($id, $request->user()->id);

        return redirect()->route('portals.admin.security.breaches')->with('success', __('security_ops.br_notified'));
    }

    public function closeBreach(Request $request, string $id): RedirectResponse
    {
        $this->assertUuid($id);
        $data = $request->validate(['resolution' => 'required|string|max:2000']);
        $this->breachWorkflow->closeBreach($id, $request->user()->id, $data['resolution']);

        return redirect()->route('portals.admin.security.breaches')->with('success', __('security_ops.br_closed'));
    }

    // ── Compliance export ─────────────────────────────────────────

    public function complianceExport(Request $request): View
    {
        return view('portals.admin.security_ops.compliance_export', [
            'types'   => ComplianceExportService::EXPORT_TYPES,
            'exports' => AuditExport::orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function downloadComplianceExport(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'export_type' => 'required|in:' . implode(',', ComplianceExportService::EXPORT_TYPES),
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
        ]);

        $export = $this->compliance->requestExport(
            $data['export_type'],
            $request->user()->id,
            array_filter(['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null]),
            'csv'
        );

        $rows = $this->compliance->rows($data['export_type'], $data['from'] ?? null, $data['to'] ?? null);

        // Rendered on demand, never stored: the record says it was produced.
        $export->update(['status' => 'ready', 'expires_at' => now()]);

        $filename = 'opescare-' . $data['export_type'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function assertUuid(string $id): void
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            abort(404);
        }
    }
}
