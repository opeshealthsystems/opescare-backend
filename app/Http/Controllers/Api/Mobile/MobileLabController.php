<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\PresentsLabResults;
use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabResultInterpretation;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Patient\PatientDocumentService;

/**
 * Mobile Patient API — Laboratory results.
 *
 * READ ONLY. Every endpoint here reads released laboratory records; none of
 * them writes, amends or deletes one. That is deliberate and it is the whole
 * safety posture of this controller: a laboratory result is an immutable
 * clinical event (the house pattern is App\Models\Prescription), and a patient
 * app is not a party that may edit one. A patient can ASK for a test — that is
 * MobileLabRequestController, and it writes to a different table.
 *
 * Sources, all of them real:
 *   - `lab_orders`        the panel a clinician ordered
 *   - `lab_results`       the analytes the laboratory released, with its own
 *                         flag and its own free-text reference range
 *   - `reference_ranges`  published intervals by analyte, sex and age band —
 *                         the only source that carries CRITICAL thresholds
 *   - `official_documents` the signed PDF, when one actually exists
 *
 * Nothing is filled in. Where the laboratory sent no reference range and the
 * platform holds no published one, the payload says so (`ref_low`/`ref_high`/
 * `reference` all null, `flag: "unflagged"`) and the app prints "Not
 * recorded" — it never prints a plausible interval or calls an uninterpreted
 * value normal. See App\Http\Controllers\Api\Mobile\Concerns\PresentsLabResults
 * for the precedence rules.
 *
 * Isolation: `patient_id` comes only from `$request->attributes`, set by
 * AuthenticateMobilePatient from the bearer token. It is never read from the
 * route, the query string, a header or the body, so one patient can never
 * address another's results.
 */
class MobileLabController extends Controller
{
    use PresentsLabResults;

    /** Default and maximum page size for the order list. */
    private const PER_PAGE     = 20;
    private const MAX_PER_PAGE = 100;

    /** How many recent orders the dashboard scans for the trendable-analyte list. */
    private const TREND_SCAN_ORDERS = 200;

    /** A trend needs at least this many numeric points to be worth charting. */
    private const MIN_TREND_POINTS = 2;

    /** How long a report download link stays valid. */
    private const DOWNLOAD_LINK_TTL_MINUTES = 15;

    /**
     * GET /api/mobile/labs
     *
     * The patient's lab orders, newest first, each with its full result set.
     *
     * Query: page, per_page, status (comma-separated: ordered,in_progress,
     * completed,cancelled), flag (normal|abnormal|critical|all).
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = $this->patient($patientId);

        $perPage = min(max((int) $request->query('per_page', self::PER_PAGE), 1), self::MAX_PER_PAGE);
        $page    = max((int) $request->query('page', 1), 1);

        $query = LabOrder::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('ordered_at')
            ->orderByDesc('created_at');

        $this->applyStatusFilter($query, $request->query('status'));

        $total  = (clone $query)->count();
        $orders = $query->forPage($page, $perPage)->get();

        $rows = $this->presentOrders($orders, $patient);

        // The flag filter is applied AFTER shaping, because `overall_flag` is
        // derived from the results and the ranges — it is not a column, so the
        // database cannot answer it. Paging stays on the unfiltered set so the
        // pagination block never claims a total it did not count.
        $flag = strtolower(trim((string) $request->query('flag', 'all')));
        if ($flag !== '' && $flag !== 'all') {
            $rows = array_values(array_filter($rows, fn (array $row) => $row['overall_flag'] === $flag));
        }

        return response()->json($this->paginated($rows, $total, $perPage, $page));
    }

    /**
     * GET /api/mobile/labs/summary
     *
     * The dashboard counters and the analytes that can actually be charted.
     *
     * The imaging counters are part of the shared diagnostics contract; this
     * server has no imaging endpoint, so they are honestly 0 rather than
     * omitted — the app renders the tile either way and a missing key would
     * read as a loading state.
     */
    public function summary(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = $this->patient($patientId);

        $orders = LabOrder::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('ordered_at')
            ->orderByDesc('created_at')
            ->limit(self::TREND_SCAN_ORDERS)
            ->get();

        $totalOrders = LabOrder::query()->where('patient_id', $patientId)->count();

        $rows = $this->presentOrders($orders, $patient);

        $pending    = 0;
        $results    = 0;
        $abnormal   = 0;
        $critical   = 0;
        $chartable  = [];

        foreach ($rows as $row) {
            if (in_array($row['status'], ['ordered', 'in_progress'], true)) {
                $pending++;
            }

            foreach ($row['results'] as $result) {
                $results++;

                if ($result['flag'] === 'critical') {
                    $critical++;
                    $abnormal++;
                } elseif ($result['is_abnormal']) {
                    $abnormal++;
                }

                if ($result['numeric_value'] !== null) {
                    $chartable[$result['analyte']] = ($chartable[$result['analyte']] ?? 0) + 1;
                }
            }
        }

        // Only analytes with enough numeric history to draw a line. Offering a
        // chip that opens an unchartable screen is worse than not offering it.
        $trendable = array_keys(array_filter(
            $chartable,
            fn (int $count) => $count >= self::MIN_TREND_POINTS
        ));
        sort($trendable);

        return response()->json([
            'data' => [
                'lab_orders_total'    => $totalOrders,
                'lab_orders_pending'  => $pending,
                'results_total'       => $results,
                'results_abnormal'    => $abnormal,
                'results_critical'    => $critical,
                'imaging_total'       => 0,
                'imaging_reported'    => 0,
                'latest_lab_order'    => $rows[0] ?? null,
                'trendable_analytes'  => array_values($trendable),
            ],
        ]);
    }

    /**
     * GET /api/mobile/labs/{id}
     *
     * One order with its full result set, plus who ordered it.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $order     = $this->findOrder($patientId, $id);
        $patient   = $this->patient($patientId);

        return response()->json(['data' => $this->presentOne($order, $patient)]);
    }

    /**
     * GET /api/mobile/labs/{id}/report
     *
     * The order, the patient block a printed report carries, and the stored
     * PDF — if one genuinely exists.
     *
     * `document` is null unless an `official_documents` row names this exact
     * order in its payload AND holds a file. A report the laboratory never
     * signed is not conjured from the result rows: the app shows "No signed
     * PDF on file", which is true, instead of offering a download that would
     * produce a document no clinician ever released.
     */
    public function report(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $order     = $this->findOrder($patientId, $id);
        $patient   = $this->patient($patientId);

        return response()->json([
            'data' => [
                'order'    => $this->presentOne($order, $patient),
                'patient'  => $this->presentPatient($patient),
                'document' => $this->presentDocument($order),
            ],
        ]);
    }

    /**
     * GET /api/mobile/labs/{id}/report/download
     *
     * Renders the report and streams it. Reached through a short-lived signed
     * link (see presentDocument) because the app hands the URL to the system
     * browser, which carries no bearer token. The signature is scoped to this
     * one order and expires; the handler still re-resolves the order under the
     * patient it names before rendering anything.
     *
     * The PDF is built here and now from the released results, and is never
     * written to disk. Until this change the handler read
     * `official_documents.pdf_path`, which nothing in this codebase has ever
     * written — so every Download button in the app returned "no report file
     * for this order".
     */
    public function download(Request $request, PatientDocumentService $documents, string $id): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'This download link has expired.');

        $patientId = (string) $request->query('patient');
        abort_if($patientId === '' || ! Str::isUuid($patientId), 404);

        // Re-resolve the order under the patient the signature names, so a
        // valid link for one patient cannot be pointed at another's panel.
        $order   = $this->findOrder($patientId, $id);
        $patient = $this->patient($patientId);

        abort_if($patient === null, 404);

        return $documents->download($patient, PatientDocumentService::LABORATORY_REPORT, (string) $order->id);
    }

    /**
     * GET /api/mobile/labs/trends/{analyte}
     *
     * One analyte's numeric series across every order it appears in, oldest
     * first. Keyed by analyte, not by order, because the series spans orders.
     *
     * An analyte the patient has no numeric result for returns 200 with an
     * empty `points` array, NOT a 404 — the app reads 404 as "this server does
     * not serve laboratory results at all" and would show the wrong message.
     *
     * Nothing is interpolated. `points` holds exactly the results the
     * laboratory released; a gap in time stays a gap.
     */
    public function trend(Request $request, string $analyte): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = $this->patient($patientId);
        $needle    = $this->normaliseAnalyte(urldecode($analyte));

        $results = $this->resultsFor($patientId)
            ->filter(fn (LabResult $r) => $this->normaliseAnalyte($r->parameter_name) === $needle
                || $this->normaliseAnalyte($r->loinc_code) === $needle)
            ->sortBy(fn (LabResult $r) => $r->resulted_at?->getTimestamp() ?? 0)
            ->values();

        $ranges = $this->loadReferenceRanges($results);
        $sex    = $patient?->sex;
        $dob    = $this->patientDateOfBirth($patient);

        $shaped = $results
            ->map(fn (LabResult $r) => $this->presentResult($r, $ranges, $sex, $dob))
            ->values();

        // Only numeric results can be plotted; free-text ones ("Negative") stay
        // in the record but are not points on a line.
        $points = $shaped
            ->filter(fn (array $r) => $r['numeric_value'] !== null)
            ->map(fn (array $r) => [
                'recorded_at' => $r['entered_at'],
                'value'       => $r['numeric_value'],
                'flag'        => $r['flag'],
            ])
            ->values();

        $latest  = $shaped->last();
        $values  = $points->pluck('value')->all();
        $first   = $values[0] ?? null;
        $last    = $values !== [] ? $values[count($values) - 1] : null;

        return response()->json([
            'data' => [
                // The analyte as the laboratory spells it, not as the URL did.
                'analyte'        => $latest['analyte'] ?? urldecode($analyte),
                'unit'           => $latest['unit'] ?? null,
                'ref_low'        => $latest['ref_low'] ?? null,
                'ref_high'       => $latest['ref_high'] ?? null,
                'latest'         => $latest,
                'direction'      => $this->direction($first, $last),
                'change_percent' => $this->changePercent($first, $last),
                'min'            => $values === [] ? 0 : min($values),
                'max'            => $values === [] ? 0 : max($values),
                'points'         => $points->all(),
            ],
        ]);
    }

    // ── Shaping helpers ──────────────────────────────────────────────────────

    /**
     * Shape a set of orders, loading their results, their ranges and the
     * ordering clinicians' names in a fixed number of queries regardless of how
     * many orders there are.
     *
     * @param  Collection<int, LabOrder>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function presentOrders(Collection $orders, ?Patient $patient): array
    {
        if ($orders->isEmpty()) {
            return [];
        }

        $results = LabResult::query()
            ->whereIn('lab_order_id', $orders->pluck('id')->all())
            ->orderBy('parameter_name')
            ->get()
            ->groupBy('lab_order_id');

        $ranges = $this->loadReferenceRanges($results->flatten());
        $names  = $this->clinicianNames($orders->pluck('ordered_by')->filter()->unique()->all());
        $sex    = $patient?->sex;
        $dob    = $this->patientDateOfBirth($patient);

        $rows = $orders
            ->map(fn (LabOrder $order) => $this->presentOrderDetail(
                $order,
                $results->get($order->id, collect()),
                $ranges,
                $sex,
                $dob,
                $order->ordered_by !== null ? ($names[$order->ordered_by] ?? null) : null
            ))
            ->values()
            ->all();

        return $this->attachClinicianReview($rows);
    }

    // ── The clinician's review ───────────────────────────────────────────────

    /**
     * Two ADDITIVE keys on every order row — nothing else on the row changes:
     *
     *   `interpretations`  the clinician's notes written FOR the patient, newest
     *                      first, each with who wrote it and when. Only rows the
     *                      clinician chose to share are read, and the private
     *                      `clinician_comment` column is never selected here —
     *                      not filtered out afterwards, never loaded at all.
     *   `reviewed_at`      when a clinician last acknowledged this result in the
     *                      results inbox (the `notification_events` ledger
     *                      MobileResultsInboxController writes), by anyone, or
     *                      null. "Reviewed" is the fact of the acknowledgement;
     *                      it says nothing about what the review concluded.
     *
     * The pre-test indication (`clinical_notes`) stays where it was: it is why
     * the test was ordered, not what the clinician made of the result.
     *
     * Two bounded queries however many orders there are, so the list is as
     * cheap to decorate as the detail.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function attachClinicianReview(array $rows): array
    {
        $orderIds = array_values(array_filter(array_map(
            fn (array $row) => isset($row['id']) ? (string) $row['id'] : null,
            $rows
        )));

        if ($orderIds === []) {
            return $rows;
        }

        // Shared notes only. The column list is explicit so the private comment
        // cannot reach this surface by accident.
        $shared = LabResultInterpretation::query()
            ->sharedWithPatient()
            ->whereIn('lab_order_id', $orderIds)
            ->orderByDesc('shared_at')
            ->orderByDesc('created_at')
            ->get(['id', 'lab_order_id', 'author_user_id', 'patient_note', 'shared_at', 'created_at'])
            ->groupBy('lab_order_id');

        $authors = $this->authors(
            $shared->flatten()->pluck('author_user_id')->filter()->unique()->values()->all()
        );

        // The ledger is keyed on the inbox's composite row id, not the bare
        // order id — ask it in its own vocabulary.
        $rowIds = array_map(
            fn (string $id) => MobileResultsInboxController::labRowId($id),
            $orderIds
        );

        $reviewed = NotificationEvent::query()
            ->where('event_type', MobileResultsInboxController::ACK_EVENT)
            ->where('related_resource_type', MobileResultsInboxController::LAB_RESOURCE_TYPE)
            ->whereIn('related_resource_id', $rowIds)
            ->whereNotNull('acknowledged_at')
            ->groupBy('related_resource_id')
            ->selectRaw('related_resource_id, MAX(acknowledged_at) AS reviewed_at')
            ->pluck('reviewed_at', 'related_resource_id');

        foreach ($rows as &$row) {
            $orderId = (string) $row['id'];

            $row['interpretations'] = ($shared->get($orderId) ?? collect())
                ->map(function (LabResultInterpretation $note) use ($authors) {
                    $author = $authors[$note->author_user_id] ?? null;

                    return [
                        'patient_note'    => $note->patient_note,
                        'author_name'     => $author['name'] ?? null,
                        'author_role'     => $author['role_label'] ?? null,
                        'author_role_key' => $author['role_key'] ?? null,
                        'at'              => $this->isoOrNull($note->shared_at ?? $note->created_at),
                    ];
                })
                ->values()
                ->all();

            $row['reviewed_at'] = $this->isoOrNull(
                $reviewed[MobileResultsInboxController::labRowId($orderId)] ?? null
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Who wrote each note: name plus role, the role rendered through the
     * platform's role catalogue (lang/portal.php `role_name.*`) with the slug
     * as the fallback, so the patient reads "Doctor", never "doctor".
     *
     * @param  array<int, string>  $ids
     * @return array<string, array{name: ?string, role_key: ?string, role_label: ?string}>
     */
    private function authors(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->with('role:id,name')
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'role_id'])
            ->mapWithKeys(function (User $user) {
                $slug = $user->role?->name;
                $key  = $slug !== null ? 'portal.role_name.' . $slug : null;

                return [(string) $user->id => [
                    'name'       => $this->blankToNull((string) $user->name),
                    'role_key'   => $slug,
                    'role_label' => $key !== null && Lang::has($key) ? __($key) : $slug,
                ]];
            })
            ->all();
    }

    /**
     * Any timestamp shape → ISO-8601, or null. `notification_events` declares
     * no date casts, so a MAX() over it arrives as a bare SQL string.
     */
    private function isoOrNull(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toIso8601String();
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function presentOne(LabOrder $order, ?Patient $patient): array
    {
        return $this->presentOrders(collect([$order]), $patient)[0];
    }

    /**
     * The order, scoped to the authenticated patient.
     *
     * `lab_orders.id` is a Postgres uuid column: comparing it to a non-UUID
     * string raises 22P02 and would surface as a 500. A malformed id in the URL
     * is a not-found, not a server fault.
     */
    private function findOrder(string $patientId, string $id): LabOrder
    {
        abort_unless(Str::isUuid($id), 404);

        return LabOrder::query()
            ->where('id', $id)
            ->where('patient_id', $patientId)
            ->firstOrFail();
    }

    /** Every result the patient has, for the cross-order trend series. */
    private function resultsFor(string $patientId): Collection
    {
        return LabResult::query()
            ->where('patient_id', $patientId)
            ->orderBy('resulted_at')
            ->get();
    }

    private function patient(string $patientId): ?Patient
    {
        return Patient::query()->find($patientId);
    }

    /**
     * `users.id` → `users.name`, for the "Ordered by" line.
     *
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    private function clinicianNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    /** Comma-separated contract statuses → the local statuses they cover. */
    private function applyStatusFilter(\Illuminate\Database\Eloquent\Builder $query, mixed $status): void
    {
        $wanted = collect(explode(',', (string) $status))
            ->map(fn (string $s) => strtolower(trim($s)))
            ->filter()
            ->unique();

        if ($wanted->isEmpty() || $wanted->contains('all')) {
            return;
        }

        // Reverse of PresentsLabResults::presentStatus(). `ordered` is the
        // catch-all there, so it is matched by exclusion here rather than by an
        // enumerated list — an unrecognised local status must land in exactly
        // the same bucket on both sides of the filter.
        $known = [
            'completed'   => ['resulted', 'completed', 'final', 'released', 'verified'],
            'in_progress' => ['processing', 'collected', 'in_progress', 'in_lab', 'received', 'partial'],
            'cancelled'   => ['cancelled', 'canceled', 'voided', 'entered_in_error', 'rejected'],
        ];

        $everythingMapped = array_merge(...array_values($known));

        $query->where(function ($sub) use ($wanted, $known, $everythingMapped) {
            foreach ($known as $contract => $locals) {
                if ($wanted->contains($contract)) {
                    $sub->orWhereIn(DB::raw('lower(status)'), $locals);
                }
            }

            if ($wanted->contains('ordered')) {
                $sub->orWhereNotIn(DB::raw('lower(status)'), $everythingMapped);
            }
        });
    }

    // ── Report document ──────────────────────────────────────────────────────


    /**
     * @return array<string, mixed>|null
     */
    /** The statuses that mean the laboratory has released the panel. */
    private const RELEASED_STATUSES = ['resulted', 'completed', 'released', 'reported'];

    private function presentDocument(LabOrder $order): ?array
    {
        // A report exists once the laboratory has released the panel and not
        // before: offering a download of unreleased values would hand a patient
        // a result no clinician has checked, which is what the release step is
        // there to prevent.
        if (! in_array(strtolower((string) $order->status), self::RELEASED_STATUSES, true)) {
            return null;
        }

        return [
            'id'         => (string) $order->id,
            'label'      => $this->blankToNull($order->test_name),
            'file_type'  => 'pdf',
            // Unknown until the sheet is rendered, and it is rendered on the
            // way out. The app prints an unknown size as a dash, never as 0.
            'file_size'  => null,
            'created_at' => $order->resulted_at?->toIso8601String(),
            // Short-lived and signed: the app opens this in the system browser,
            // which carries no bearer token, so the URL has to authenticate
            // itself. It names one order, for one patient, for 15 minutes.
            'download_url' => URL::temporarySignedRoute(
                'mobile.labs.report.download',
                now()->addMinutes(self::DOWNLOAD_LINK_TTL_MINUTES),
                [
                    'id'      => (string) $order->id,
                    'patient' => (string) $order->patient_id,
                ]
            ),
        ];
    }

    // ── Trend maths ──────────────────────────────────────────────────────────

    /**
     * Which way the series is moving. `flat` when there is nothing to compare —
     * a single point has no direction, and claiming one would be a story about
     * data that is not there.
     */
    private function direction(?float $first, ?float $last): string
    {
        if ($first === null || $last === null || $first === $last) {
            return 'flat';
        }

        return $last > $first ? 'up' : 'down';
    }

    /**
     * Percentage change across the series, or null.
     *
     * Null — not 0 — when there are fewer than two points or the first value is
     * zero. "0%" means "measured, and unchanged"; this is "not calculable".
     */
    private function changePercent(?float $first, ?float $last): ?float
    {
        if ($first === null || $last === null || $first == 0.0) {
            return null;
        }

        return round((($last - $first) / abs($first)) * 100, 1);
    }
}
