<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientMerge;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use App\Modules\MasterPatientIndex\Services\PatientMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Patient Identity — the desk where two records become one, or are declared two.
 *
 * Third of the review desks (directory, legal, now identity), same shape: a
 * human judging a question a machine must not settle on its own. Here the
 * question is "are these the same person?", and the cost of a wrong yes is one
 * patient's history filed under another patient's name — so nothing on this
 * screen happens without a chosen survivor and a written reason, and every
 * merge can be undone from the same screen.
 *
 * Platform-tier only: /admin/mpi is covered by the platform.admin middleware
 * on its route group.
 */
class AdminIdentityReviewController extends Controller
{
    public function __construct(
        private readonly MasterPatientIndexService $mpi,
        private readonly PatientMergeService $merges,
    ) {
    }

    public function index(Request $request): View
    {
        $tab = match ($request->query('tab')) {
            'merged'   => 'merged',
            'rejected' => 'rejected',
            default    => 'pending',
        };

        $tier = in_array($request->query('tier'), ['deterministic', 'probable', 'possible'], true)
            ? $request->query('tier')
            : null;

        $candidates = $this->mpi->listCandidates(
            ['status' => $tab === 'pending' ? 'pending_review' : $tab, 'tier' => $tier],
            20,
        )->withQueryString();

        // What the reviewer needs beside the demographics: how much history
        // each record carries, and which logins open it. Judging a duplicate
        // from names alone is how wrong merges happen.
        $patientIds = $candidates->getCollection()
            ->flatMap(fn (MpiCandidate $c) => [$c->source_patient_id, $c->target_patient_id])
            ->unique()->values()->all();

        $footprint = $this->footprint($patientIds);

        $counts = [
            'pending'  => MpiCandidate::where('status', 'pending_review')->count(),
            'merged'   => MpiCandidate::where('status', 'merged')->count(),
            'rejected' => MpiCandidate::where('status', 'rejected')->count(),
        ];

        $tierCounts = [
            'deterministic' => MpiCandidate::where('status', 'pending_review')->where('match_score', '>=', MasterPatientIndexService::TIER_DETERMINISTIC)->count(),
            'probable'      => MpiCandidate::where('status', 'pending_review')->where('match_score', '>=', MasterPatientIndexService::TIER_PROBABLE)->where('match_score', '<', MasterPatientIndexService::TIER_DETERMINISTIC)->count(),
            'possible'      => MpiCandidate::where('status', 'pending_review')->where('match_score', '<', MasterPatientIndexService::TIER_PROBABLE)->count(),
        ];

        $registerSize = Patient::withoutGlobalScope('isolate_demo')->count();

        return view('portals.admin.mpi.index', compact('tab', 'tier', 'candidates', 'footprint', 'counts', 'tierCounts', 'registerSize'));
    }

    /** Run detection now, on demand. Queues candidates; merges nothing. */
    public function detect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:10', 'max:5000'],
        ]);

        $report = $this->mpi->detectDuplicates(limit: (int) ($validated['limit'] ?? 500));

        return redirect()
            ->route('portals.admin.mpi.index')
            ->with('success', __('mpi.flash_detected', ['scanned' => $report['scanned'], 'created' => $report['created']]));
    }

    public function merge(Request $request, MpiCandidate $candidate): RedirectResponse
    {
        $validated = $request->validate([
            'survivor_patient_id' => ['required', 'uuid'],
            'reason'              => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $merge = $this->mpi->confirmMatch($candidate, Auth::id(), $validated['survivor_patient_id'], $validated['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $this->humanise($e));
        }

        return back()->with('success', __('mpi.flash_merged', [
            'moved' => $merge->movedRowCount(),
            'kept'  => $merge->keptRowCount(),
        ]));
    }

    public function reject(Request $request, MpiCandidate $candidate): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->mpi->rejectMatch($candidate, Auth::id(), $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $this->humanise($e));
        }

        return back()->with('success', __('mpi.flash_rejected'));
    }

    public function unmerge(Request $request, PatientMerge $merge): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->merges->unmerge($merge, Auth::id(), $validated['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $this->humanise($e));
        }

        return back()->with('success', __('mpi.flash_unmerged'));
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * Per patient: clinical row counts and login emails. One query per table,
     * for the page's patients at once — never one per card.
     *
     * @param  list<string>  $patientIds
     * @return array<string, array{prescriptions: int, appointments: int, encounters: int, logins: list<string>}>
     */
    private function footprint(array $patientIds): array
    {
        if ($patientIds === []) {
            return [];
        }

        $out = [];
        foreach ($patientIds as $id) {
            $out[(string) $id] = ['prescriptions' => 0, 'appointments' => 0, 'encounters' => 0, 'logins' => []];
        }

        foreach (['prescriptions', 'appointments', 'encounters'] as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)->select('patient_id', DB::raw('count(*) as n'))
                ->whereIn('patient_id', $patientIds)->groupBy('patient_id')->get();
            foreach ($rows as $r) {
                $out[(string) $r->patient_id][$table] = (int) $r->n;
            }
        }

        foreach (DB::table('users')->whereIn('patient_id', $patientIds)->get(['patient_id', 'email']) as $u) {
            $out[(string) $u->patient_id]['logins'][] = $u->email;
        }

        return $out;
    }

    private function humanise(\Throwable $e): string
    {
        $code = explode(':', $e->getMessage(), 2)[0];

        return match ($code) {
            'MPI_CANDIDATE_NOT_PENDING'    => __('mpi.error_not_pending'),
            'MPI_SURVIVOR_NOT_IN_PAIR'     => __('mpi.error_survivor_not_in_pair'),
            'MPI_PATIENT_ALREADY_MERGED',
            'MPI_SURVIVOR_ALREADY_MERGED'  => __('mpi.error_already_merged'),
            'MPI_MERGE_NOT_ACTIVE'         => __('mpi.error_merge_not_active'),
            'MPI_MERGE_SAME_PATIENT'       => __('mpi.error_same_patient'),
            default                        => __('mpi.error_generic'),
        };
    }
}
