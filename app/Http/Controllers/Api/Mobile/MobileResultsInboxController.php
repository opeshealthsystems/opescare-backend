<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CriticalValueAlert;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\User;
use App\Modules\Governance\Services\AccessLogService;
use App\Modules\Notifications\Models\NotificationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — the clinician's RESULTS INBOX.
 *
 * An unacknowledged critical result is the most dangerous object a hospital
 * system holds, so the rules this file obeys are written out rather than
 * implied:
 *
 *  1. NOTHING IS FABRICATED. Every value, flag, unit and reference interval is
 *     printed exactly as the laboratory recorded it. A missing reference range
 *     is reported as missing — this controller never derives one, and never
 *     computes an abnormality of its own. `is_critical` comes from the lab's
 *     own flag vocabulary (CriticalValueService::CRITICAL_FLAGS = HH|LL) or
 *     from a `critical_value_alerts` row raised by the bench, and from nowhere
 *     else.
 *
 *  2. AN ABNORMAL RESULT IS NEVER RENDERED AS NORMAL. Severity is the WORST
 *     flag on the order, never an average and never the first row. An order
 *     whose analytes carry no flag at all reports `info` ("not recorded"), not
 *     `success` — "the lab did not say" and "the lab said normal" are
 *     different claims and are kept apart.
 *
 *  3. CRITICALS SORT FIRST. Unacknowledged criticals head the list, then
 *     unacknowledged abnormals, then everything else newest-first. The counts
 *     in `meta` never collapse criticals into a generic total: `critical` and
 *     `critical_pending` are their own figures.
 *
 *  4. ACKNOWLEDGEMENT IS AN AUDITABLE CLINICAL ACT, NOT A TOGGLE. It records
 *     WHO, WHEN and WHICH result, is idempotent (a second call returns the
 *     ORIGINAL signature — so a retry after a dropped response cannot make a
 *     landed acknowledgement look failed), and has no reversal.
 *
 *  5. SCOPE COMES FROM THE TOKEN. `facility_id` is read ONLY from
 *     `$request->attributes` (stamped by AuthenticateMobileProvider); so is the
 *     clinician's user id. Neither is ever read from the body, the query
 *     string, a header or the session, and there is no parameter that would let
 *     a caller name either. The inbox is further narrowed to the clinician's
 *     OWN orders — it is their worklist, not the facility's whole result feed.
 *
 * ── WHERE THE DATA COMES FROM ─────────────────────────────────────────────
 *
 * There is no `results_inbox` table on this deployment and this controller does
 * not invent one. An inbox row is DERIVED from the clinical source of truth:
 *
 *   • `lab_orders` that have been resulted, with their `lab_results` analytes;
 *   • `radiology_reports` that have been finalised (final|amended|corrected).
 *
 * The row id is the source row's id with a kind prefix — `lab-<uuid>` /
 * `rad-<uuid>` — so the inbox has no identity of its own to drift out of sync.
 *
 * Acknowledgement state is persisted in `notification_events`, the platform's
 * existing in-app notification ledger (see App\Support\Notify), whose columns
 * are literally named for this: `recipient_user_id`, `related_resource_type` /
 * `related_resource_id`, `requires_acknowledgement`, `acknowledgement_status`,
 * `acknowledged_by`, `acknowledged_at`. Nothing is duplicated and no table is
 * added.
 *
 * ── WHY THE LEDGER ROW CARRIES A CONTENT SIGNATURE ────────────────────────
 *
 * An acknowledgement is a statement about the result AS IT READ AT THE TIME.
 * If the bench later amends the order — adds an analyte, corrects a value,
 * raises a flag from H to HH — the thing the clinician signed off no longer
 * exists. The ledger row therefore stores a signature of the released content;
 * a row only counts as acknowledged while the signature still matches. An
 * amended critical returns to the top of the inbox as unacknowledged rather
 * than sitting silently signed-off, and acknowledging it writes a SECOND,
 * separate ledger row. Re-tapping unchanged content matches the existing row
 * and changes nothing.
 */
class MobileResultsInboxController extends Controller
{
    /** The ledger event type. Distinct, so nothing else in the bell feed picks these up. */
    private const ACK_EVENT = 'doctor_result_acknowledged';

    /** The lab's own critical vocabulary — the same constant CriticalValueService uses. */
    private const CRITICAL_FLAGS = ['HH', 'LL'];

    /** The lab's own abnormal-but-not-critical vocabulary. */
    private const ABNORMAL_FLAGS = ['H', 'L', 'A', 'ABNORMAL'];

    /** Flags that are an explicit statement of normality (as opposed to silence). */
    private const NORMAL_FLAGS = ['N', 'NORMAL', 'WNL'];

    /** Radiology statuses that mean "released to the ordering clinician". */
    private const RELEASED_REPORT_STATUSES = ['final', 'amended', 'corrected'];

    /** How many source rows of each kind the inbox loads. `meta.truncated` says when it bit. */
    private const MAX_ROWS_PER_KIND = 250;

    // ──────────────────────────────────────────────────────────────────
    // Endpoints
    // ──────────────────────────────────────────────────────────────────

    /**
     * The inbox.
     *
     * GET /api/mobile/doctor/results-inbox?filter=unacknowledged|critical|acknowledged
     * (filter omitted = everything)
     *
     * Counts in `meta.counts` are computed over the whole loaded set, never
     * over the filtered slice — the "Critical" chip must read 3 while you are
     * standing in the "Done" tab.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $validated = $request->validate([
            'filter' => 'nullable|string|in:all,unacknowledged,critical,acknowledged',
        ]);

        $filter = $validated['filter'] ?? 'all';

        $build = $this->buildRows($request);
        $rows  = $build['rows'];

        $counts = [
            'all'              => $rows->count(),
            'unacknowledged'   => $rows->whereNull('acknowledged_at')->count(),
            'critical'         => $rows->where('is_critical', true)->count(),
            'acknowledged'     => $rows->whereNotNull('acknowledged_at')->count(),
            'critical_pending' => $rows->where('is_critical', true)->whereNull('acknowledged_at')->count(),
        ];

        $visible = match ($filter) {
            'unacknowledged' => $rows->whereNull('acknowledged_at'),
            'critical'       => $rows->where('is_critical', true),
            'acknowledged'   => $rows->whereNotNull('acknowledged_at'),
            default          => $rows,
        };

        // Every patient whose name is disclosed by this read is logged once.
        $this->auditPatients(
            $request,
            $visible->pluck('_patient_uuid')->filter()->unique()->all(),
            'results_inbox_review',
            'ResultsInbox',
            null,
            'view'
        );

        return response()->json([
            'data' => $visible->map(fn (array $row) => $this->publicRow($row))->values(),
            'meta' => [
                'counts'    => $counts,
                'filter'    => $filter,
                // Honest about its own completeness: true means older results
                // exist beyond the window this response loaded.
                'truncated' => $build['truncated'],
            ],
        ], 200);
    }

    /**
     * One result, with the underlying report.
     *
     * GET /api/mobile/doctor/results-inbox/{id}     ({id} = lab-<uuid> | rad-<uuid>)
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $row = $this->findRow($request, $id);

        if ($row === null) {
            return response()->json(['message' => __('doctor_results.not_found')], 404);
        }

        $this->auditPatients(
            $request,
            array_filter([$row['_patient_uuid']]),
            'results_inbox_review',
            $row['_kind'] === 'lab' ? 'LabOrder' : 'RadiologyReport',
            $row['_source_id'],
            'view'
        );

        return response()->json([
            'data' => $this->publicRow($row) + ['result' => $this->resultContext($row)],
        ], 200);
    }

    /**
     * Acknowledge a result. Idempotent; never a toggle; never reversible here.
     *
     * POST /api/mobile/doctor/results-inbox/{id}/acknowledge
     *
     * A second call answers 200 with the ORIGINAL acknowledgement — same
     * signature, same timestamp, same name — so a client retrying after a
     * dropped response cannot make a landed acknowledgement look failed, and
     * cannot un-acknowledge anything by tapping twice.
     */
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $row = $this->findRow($request, $id);

        if ($row === null) {
            return response()->json(['message' => __('doctor_results.not_found')], 404);
        }

        $userId = $this->providerUserId($request);

        try {
            $event = DB::transaction(function () use ($row, $userId) {
                // Lock the SOURCE row for the length of the transaction, so two
                // simultaneous taps on the same result serialise here rather
                // than both inserting a ledger row.
                $this->lockSource($row);

                $existing = $this->acknowledgementFor($userId, $row['_resource_type'], $row['id'], $row['_signature']);

                if ($existing !== null) {
                    return $existing;
                }

                return NotificationEvent::create([
                    'uuid'                     => (string) Str::uuid(),
                    'event_type'               => self::ACK_EVENT,
                    'communication_type'       => 'in_app',
                    'actor_id'                 => $userId,
                    'recipient_user_id'        => $userId,
                    'recipient_type'           => 'user',
                    'related_resource_type'    => $row['_resource_type'],
                    'related_resource_id'      => $row['id'],
                    // The clinical statement itself: WHAT was acknowledged, as
                    // it read at the moment it was acknowledged.
                    'payload_json'             => json_encode([
                        'signature'    => $row['_signature'],
                        'title'        => $row['title'],
                        'severity'     => $row['severity'],
                        'is_critical'  => $row['is_critical'],
                        'patient_id'   => $row['_patient_uuid'],
                        'facility_id'  => $row['_facility_id'],
                        'reported_at'  => $row['reported_at'],
                        'source_id'    => $row['_source_id'],
                    ]),
                    'priority'                 => $row['is_critical'] ? 'critical' : 'normal',
                    'status'                   => 'acknowledged',
                    'requires_acknowledgement' => true,
                    'acknowledgement_status'   => 'acknowledged',
                    'acknowledged_by'          => $userId,
                    'acknowledged_at'          => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('doctor results inbox: acknowledgement failed', [
                'result_id' => $id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json(['message' => __('doctor_results.acknowledge_failed')], 422);
        }

        $this->auditPatients(
            $request,
            array_filter([$row['_patient_uuid']]),
            'results_inbox_acknowledgement',
            $row['_resource_type'],
            $row['_source_id'],
            'acknowledge'
        );

        $row = $this->applyAcknowledgement($row, $event);

        return response()->json([
            'data' => $this->publicRow($row) + ['result' => $this->resultContext($row)],
        ], 200);
    }

    /**
     * The analyte rows for one lab order.
     *
     * GET /api/mobile/doctor/orders/{id}/results
     *
     * Accepts the bare `lab_orders.id` (what a chart row carries) or the
     * inbox's `lab-<uuid>` form.
     */
    public function orderResults(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $orderId = str_starts_with($id, 'lab-') ? substr($id, 4) : $id;

        if (! Str::isUuid($orderId)) {
            // lab_orders.id is a uuid column; a malformed id makes Postgres
            // raise 22P02, which would surface as a 500. Not found is honest.
            return response()->json(['message' => __('doctor_results.order_not_found')], 404);
        }

        $order = LabOrder::query()
            ->where('facility_id', $this->providerFacilityId($request))
            ->where('ordered_by', $this->providerUserId($request))
            ->find($orderId);

        if ($order === null) {
            return response()->json(['message' => __('doctor_results.order_not_found')], 404);
        }

        $results = LabResult::where('lab_order_id', $order->id)
            ->orderBy('parameter_name')
            ->get();

        $names = $this->userNames($results->pluck('verified_by')->filter()->unique()->all());

        $this->auditPatients(
            $request,
            array_filter([$order->patient_id]),
            'results_inbox_review',
            'LabOrder',
            $order->id,
            'view'
        );

        return response()->json([
            'data' => $results->map(fn (LabResult $r) => [
                'id'                => $r->id,
                'clinical_order_id' => $r->lab_order_id,
                // No local test catalogue on this deployment; the LOINC code is
                // the only test identifier recorded, and null means none was.
                'lab_test_id'       => $r->loinc_code,
                'analyte'           => $r->parameter_name,
                'value'             => $r->value,
                'unit'              => $r->unit,
                // The LABORATORY's interval, verbatim. Never derived here.
                'reference'         => $r->reference_range,
                'flag'              => $r->flag,
                'is_abnormal'       => $this->flagIsAbnormal($r->flag),
                'entered_by'        => $r->verified_by,
                'entered_by_name'   => $names[$r->verified_by] ?? null,
                'entered_at'        => $r->resulted_at?->toIso8601String(),
            ])->values(),
        ], 200);
    }

    /**
     * The results half of the clinician's unread badge.
     *
     * GET /api/mobile/doctor/messages/unread-count
     * GET /api/mobile/doctor/results-inbox/counts   (same payload, own namespace)
     *
     * Criticals are reported as their OWN figure and are never folded into the
     * generic total — a badge reading "7" that hides one critical is exactly
     * the collapse this screen exists to prevent.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $rows = $this->buildRows($request)['rows'];

        return response()->json([
            'data' => [
                'unacknowledged_results'  => $rows->whereNull('acknowledged_at')->count(),
                'critical_unacknowledged' => $rows->where('is_critical', true)->whereNull('acknowledged_at')->count(),
                'critical_total'          => $rows->where('is_critical', true)->count(),
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Scope — the token, and only the token
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility this session may read. Stamped by AuthenticateMobileProvider
     * from the bearer token. Platform rule: facility_id comes ONLY from the
     * request attributes — never a header, body, query string or session.
     */
    private function providerFacilityId(Request $request): ?string
    {
        return $request->attributes->get('provider_facility_id');
    }

    /**
     * Render this response in the caller's language.
     *
     * SetLocale is registered on the `web` middleware group only, so an API
     * request would otherwise always answer in English however the phone is
     * configured — and every string on this screen is safety copy. `?lang=` is
     * honoured alongside `Accept-Language` for parity with the web surface.
     * Same approach as MobileDoctorChartController.
     */
    private function applyLocale(Request $request): void
    {
        $requested = $request->query('lang');

        if (! is_string($requested) || ! in_array($requested, ['en', 'fr'], true)) {
            $requested = str_contains(strtolower((string) $request->header('Accept-Language', '')), 'fr')
                ? 'fr'
                : null;
        }

        if ($requested !== null) {
            App::setLocale($requested);
        }
    }

    /** The clinician's user id, likewise stamped by the middleware. */
    private function providerUserId(Request $request): ?string
    {
        return $request->attributes->get('provider_user_id');
    }

    // ──────────────────────────────────────────────────────────────────
    // Row assembly
    // ──────────────────────────────────────────────────────────────────

    /**
     * Every inbox row for this clinician, sorted.
     *
     * @return array{rows: Collection<int,array<string,mixed>>, truncated: bool}
     */
    private function buildRows(Request $request): array
    {
        $facilityId = $this->providerFacilityId($request);
        $userId     = $this->providerUserId($request);

        if ($facilityId === null || $userId === null) {
            return ['rows' => collect(), 'truncated' => false];
        }

        [$labRows, $labTruncated] = $this->labRows($facilityId, $userId);
        [$radRows, $radTruncated] = $this->radiologyRows($facilityId, $userId);

        $rows = $labRows->concat($radRows);

        $rows = $this->attachAcknowledgements($rows, $userId);

        return ['rows' => $this->sortRows($rows), 'truncated' => $labTruncated || $radTruncated];
    }

    /**
     * Resulted lab orders this clinician placed at this facility.
     *
     * @return array{0: Collection<int,array<string,mixed>>, 1: bool}
     */
    private function labRows(string $facilityId, string $userId): array
    {
        $orders = LabOrder::query()
            ->where('facility_id', $facilityId)
            ->where('ordered_by', $userId)
            ->where(function ($q) {
                $q->whereNotNull('resulted_at')->orWhere('status', 'resulted');
            })
            ->orderByDesc('resulted_at')
            ->orderByDesc('ordered_at')
            ->limit(self::MAX_ROWS_PER_KIND + 1)
            ->get();

        $truncated = $orders->count() > self::MAX_ROWS_PER_KIND;
        $orders    = $orders->take(self::MAX_ROWS_PER_KIND);

        if ($orders->isEmpty()) {
            return [collect(), false];
        }

        $analytes = LabResult::whereIn('lab_order_id', $orders->pluck('id'))
            ->orderBy('parameter_name')
            ->get()
            ->groupBy('lab_order_id');

        // A bench-raised critical alert makes the result critical regardless of
        // how the flag reads — the alert is the lab's own escalation.
        $alerted = CriticalValueAlert::whereIn('lab_result_id', $analytes->flatten()->pluck('id'))
            ->pluck('lab_result_id')
            ->unique()
            ->flip();

        $patients = $this->patients($orders->pluck('patient_id')->filter()->unique()->all());

        $rows = $orders->map(function (LabOrder $order) use ($analytes, $alerted, $patients) {
            $rowAnalytes = $analytes->get($order->id, collect());

            $severity = $this->labSeverity($rowAnalytes, $alerted);
            $patient  = $patients[$order->patient_id] ?? null;

            return [
                'id'                 => 'lab-' . $order->id,
                '_kind'              => 'lab',
                '_resource_type'     => 'LabOrder',
                '_source_id'         => $order->id,
                '_facility_id'       => $order->facility_id,
                '_patient_uuid'      => $order->patient_id,
                '_order'             => $order,
                '_analytes'          => $rowAnalytes,
                '_alerted'           => $alerted,
                '_signature'         => $this->labSignature($order, $rowAnalytes),
                'category'           => 'lab_result',
                'category_label'     => __('doctor_results.category.lab_result'),
                'severity'           => $severity,
                'priority'           => $this->priorityFor($severity, $order->urgency),
                'is_critical'        => $severity === 'critical',
                'title'              => $order->test_name,
                'body'               => $this->labBody($rowAnalytes, $severity),
                'patient_name'       => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
                'patient_health_id'  => $patient?->health_id,
                'reported_at'        => ($order->resulted_at ?? $order->ordered_at)?->toIso8601String(),
                'read_at'            => null,
                'acknowledged_at'    => null,
                'acknowledged_by'    => null,
                'acknowledged_by_name' => null,
                'action_route'       => 'result',
                'action_id'          => 'lab-' . $order->id,
            ];
        });

        return [$rows, $truncated];
    }

    /**
     * Finalised radiology reports for imaging this clinician ordered.
     *
     * @return array{0: Collection<int,array<string,mixed>>, 1: bool}
     */
    private function radiologyRows(string $facilityId, string $userId): array
    {
        $reports = RadiologyReport::query()
            ->where('facility_id', $facilityId)
            ->where('ordered_by', $userId)
            ->whereIn('status', self::RELEASED_REPORT_STATUSES)
            ->orderByDesc('finalized_at')
            ->orderByDesc('study_date')
            ->limit(self::MAX_ROWS_PER_KIND + 1)
            ->get();

        $truncated = $reports->count() > self::MAX_ROWS_PER_KIND;
        $reports   = $reports->take(self::MAX_ROWS_PER_KIND);

        if ($reports->isEmpty()) {
            return [collect(), false];
        }

        $patients = $this->patients($reports->pluck('patient_id')->filter()->unique()->all());

        $rows = $reports->map(function (RadiologyReport $report) use ($patients) {
            $patient = $patients[$report->patient_id] ?? null;

            // radiology_reports carries no abnormality flag on this deployment.
            // "info" says the report is released and unclassified; it is NOT
            // "success", which would assert a normal study nobody recorded.
            $severity = 'info';

            return [
                'id'                 => 'rad-' . $report->id,
                '_kind'              => 'radiology',
                '_resource_type'     => 'RadiologyReport',
                '_source_id'         => $report->id,
                '_facility_id'       => $report->facility_id,
                '_patient_uuid'      => $report->patient_id,
                '_report'            => $report,
                '_signature'         => $this->radiologySignature($report),
                'category'           => 'radiology',
                'category_label'     => __('doctor_results.category.radiology'),
                'severity'           => $severity,
                'priority'           => $this->priorityFor($severity, null),
                'is_critical'        => false,
                'title'              => $this->radiologyTitle($report),
                'body'               => $report->impression,
                'patient_name'       => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
                'patient_health_id'  => $patient?->health_id,
                'reported_at'        => ($report->finalized_at ?? $report->study_date)?->toIso8601String(),
                'read_at'            => null,
                'acknowledged_at'    => null,
                'acknowledged_by'    => null,
                'acknowledged_by_name' => null,
                'action_route'       => 'result',
                'action_id'          => 'rad-' . $report->id,
            ];
        });

        return [$rows, $truncated];
    }

    /**
     * One inbox row by its public id, inside this clinician's scope. Null when
     * it is not there or not theirs — the two are deliberately the same answer.
     *
     * @return array<string,mixed>|null
     */
    private function findRow(Request $request, string $id): ?array
    {
        if (! preg_match('/^(lab|rad)-([0-9a-fA-F-]{36})$/', $id)) {
            return null;
        }

        return $this->buildRows($request)['rows']->firstWhere('id', $id);
    }

    // ──────────────────────────────────────────────────────────────────
    // Severity — the laboratory's own words, never this file's arithmetic
    // ──────────────────────────────────────────────────────────────────

    /**
     * The WORST flag on the order wins. An order with one HH and nine normals
     * is a critical order.
     *
     * @param Collection<int,LabResult> $analytes
     * @param Collection<string,int>    $alerted   lab_result_id => position
     */
    private function labSeverity(Collection $analytes, Collection $alerted): string
    {
        $sawAbnormal = false;
        $sawNormal   = false;

        foreach ($analytes as $analyte) {
            // A bench escalation outranks whatever the flag column says.
            if ($alerted->has($analyte->id)) {
                return 'critical';
            }

            $flag = $this->normaliseFlag($analyte->flag);

            // No flag is NOT a normal flag. Silence is recorded as silence.
            if ($flag === null) {
                continue;
            }

            if (in_array($flag, self::CRITICAL_FLAGS, true) || $flag === 'CRITICAL' || $flag === 'PANIC') {
                return 'critical';
            }

            if (in_array($flag, self::ABNORMAL_FLAGS, true)) {
                // Keep scanning — a later analyte may still be critical.
                $sawAbnormal = true;

                continue;
            }

            if (in_array($flag, self::NORMAL_FLAGS, true)) {
                $sawNormal = true;
            }
        }

        if ($sawAbnormal) {
            return 'warning';
        }

        // "The laboratory said normal" — only when it actually said so.
        return $sawNormal ? 'success' : 'info';
    }

    /** Is this the lab's own abnormal flag? Never a comparison against a range. */
    private function flagIsAbnormal(?string $flag): bool
    {
        $flag = $this->normaliseFlag($flag);

        if ($flag === null) {
            return false;
        }

        return in_array($flag, self::CRITICAL_FLAGS, true)
            || in_array($flag, self::ABNORMAL_FLAGS, true)
            || $flag === 'CRITICAL'
            || $flag === 'PANIC';
    }

    private function normaliseFlag(?string $flag): ?string
    {
        $flag = strtoupper(trim((string) $flag));

        return $flag === '' ? null : $flag;
    }

    /**
     * Severity plus the ordering clinician's own urgency → the row's priority.
     * A stat order that came back clean is still worth reading first.
     */
    private function priorityFor(string $severity, ?string $urgency): string
    {
        if ($severity === 'critical') {
            return 'high';
        }

        if ($severity === 'warning' || in_array(strtolower((string) $urgency), ['stat', 'urgent'], true)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * A one-line summary that COUNTS the abnormal analytes rather than
     * describing them — the detail screen prints the values themselves.
     *
     * @param Collection<int,LabResult> $analytes
     */
    private function labBody(Collection $analytes, string $severity): ?string
    {
        if ($analytes->isEmpty()) {
            return __('doctor_results.body.no_values');
        }

        $total    = $analytes->count();
        $abnormal = $analytes->filter(fn (LabResult $r) => $this->flagIsAbnormal($r->flag))->count();

        if ($abnormal > 0) {
            return trans_choice('doctor_results.body.abnormal', $abnormal, [
                'abnormal' => $abnormal,
                'total'    => $total,
            ]);
        }

        if ($severity === 'info') {
            return trans_choice('doctor_results.body.unflagged', $total, ['total' => $total]);
        }

        return trans_choice('doctor_results.body.normal', $total, ['total' => $total]);
    }

    /** "X-ray — Chest". The modality enum is rendered through the catalogue, never raw. */
    private function radiologyTitle(RadiologyReport $report): string
    {
        $modality = $report->modality
            ? __('doctor_results.modality.' . strtolower($report->modality))
            : null;

        // A modality the catalogue does not carry falls back to the recorded
        // value rather than to a missing-key string.
        if (is_string($modality) && str_starts_with($modality, 'doctor_results.')) {
            $modality = $report->modality;
        }

        $parts = array_filter([$modality, $report->body_part]);

        return $parts === []
            ? __('doctor_results.radiology_report')
            : implode(' — ', $parts);
    }

    // ──────────────────────────────────────────────────────────────────
    // Sorting
    // ──────────────────────────────────────────────────────────────────

    /**
     * Unacknowledged criticals, then unacknowledged abnormals, then everything
     * else — each band newest first.
     *
     * @param  Collection<int,array<string,mixed>> $rows
     * @return Collection<int,array<string,mixed>>
     */
    private function sortRows(Collection $rows): Collection
    {
        return $rows->sort(function (array $a, array $b) {
            $band = $this->band($a) <=> $this->band($b);
            if ($band !== 0) {
                return $band;
            }

            // Newest first inside the band.
            return strcmp((string) $b['reported_at'], (string) $a['reported_at']);
        })->values();
    }

    /** @param array<string,mixed> $row */
    private function band(array $row): int
    {
        $pending = $row['acknowledged_at'] === null;

        if ($pending && $row['is_critical']) {
            return 0;
        }

        if ($pending && $row['severity'] === 'warning') {
            return 1;
        }

        if ($pending) {
            return 2;
        }

        return 3;
    }

    // ──────────────────────────────────────────────────────────────────
    // Acknowledgement ledger (notification_events)
    // ──────────────────────────────────────────────────────────────────

    /**
     * Stamp each row with its acknowledgement, if the CURRENT content is what
     * was acknowledged. A row whose signature has moved on reads as pending
     * again — see the class docblock.
     *
     * @param  Collection<int,array<string,mixed>> $rows
     * @return Collection<int,array<string,mixed>>
     */
    private function attachAcknowledgements(Collection $rows, string $userId): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $events = NotificationEvent::query()
            ->where('event_type', self::ACK_EVENT)
            ->where('recipient_user_id', $userId)
            ->whereIn('related_resource_id', $rows->pluck('id')->all())
            ->orderBy('acknowledged_at')
            ->get()
            ->groupBy('related_resource_id');

        $names = $this->userNames(
            $events->flatten()->pluck('acknowledged_by')->filter()->unique()->all()
        );

        return $rows->map(function (array $row) use ($events, $names) {
            /** @var NotificationEvent|null $match */
            $match = ($events->get($row['id']) ?? collect())
                ->first(fn (NotificationEvent $e) => ($this->payload($e)['signature'] ?? null) === $row['_signature']);

            if ($match === null) {
                return $row;
            }

            return $this->applyAcknowledgement($row, $match, $names);
        });
    }

    /**
     * The ledger row for exactly this content, if there is one.
     *
     * Matching on the signature as well as the resource is what makes a second
     * tap idempotent while keeping an AMENDED result a genuinely new act.
     */
    private function acknowledgementFor(
        string $userId,
        string $resourceType,
        string $resourceId,
        string $signature
    ): ?NotificationEvent {
        return NotificationEvent::query()
            ->where('event_type', self::ACK_EVENT)
            ->where('recipient_user_id', $userId)
            ->where('related_resource_type', $resourceType)
            ->where('related_resource_id', $resourceId)
            ->lockForUpdate()
            ->get()
            ->first(fn (NotificationEvent $e) => ($this->payload($e)['signature'] ?? null) === $signature);
    }

    /**
     * @param  array<string,mixed>  $row
     * @param  array<string,string>|null $names
     * @return array<string,mixed>
     */
    private function applyAcknowledgement(array $row, NotificationEvent $event, ?array $names = null): array
    {
        $by = $event->acknowledged_by ? (string) $event->acknowledged_by : null;

        $name = $by === null
            ? null
            : ($names[$by] ?? $this->userNames([$by])[$by] ?? null);

        // NotificationEvent declares no date casts, so `acknowledged_at` is a
        // Carbon on the row we just created and a bare SQL string on every
        // re-read. Both are normalised to ISO-8601 here: an idempotent second
        // acknowledgement must answer with the SAME signature as the first,
        // and "2026-09-05 00:01:57" is not a date the app's JS engine parses.
        $row['acknowledged_at']      = $this->iso($event->acknowledged_at);
        $row['acknowledged_by']      = $by;
        $row['acknowledged_by_name'] = $name;
        // The inbox has no separate "opened" state: what matters clinically is
        // whether it was acknowledged, and read_at mirrors that rather than
        // inventing a second, weaker signal.
        $row['read_at']              = $row['acknowledged_at'];

        return $row;
    }

    /**
     * Any timestamp shape → ISO-8601, or null. Never throws on junk: a
     * timestamp this cannot read is reported as not recorded rather than as an
     * error that would hide the result behind it.
     */
    private function iso(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string,mixed> */
    private function payload(NotificationEvent $event): array
    {
        $decoded = json_decode((string) $event->payload_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Take a row lock on the source record so two simultaneous acknowledgements
     * of the same result cannot both insert.
     *
     * @param array<string,mixed> $row
     */
    private function lockSource(array $row): void
    {
        if ($row['_kind'] === 'lab') {
            LabOrder::where('id', $row['_source_id'])->lockForUpdate()->first();

            return;
        }

        RadiologyReport::where('id', $row['_source_id'])->lockForUpdate()->first();
    }

    /**
     * A fingerprint of the released content. Any amendment — a new analyte, a
     * corrected value, a raised flag, a re-issued report — changes it.
     *
     * @param Collection<int,LabResult> $analytes
     */
    private function labSignature(LabOrder $order, Collection $analytes): string
    {
        $parts = $analytes
            ->map(fn (LabResult $r) => implode('|', [
                $r->id,
                (string) $r->value,
                (string) $r->flag,
                (string) $r->reference_range,
                $r->resulted_at?->toIso8601String() ?? '',
            ]))
            ->sort()
            ->values()
            ->all();

        return sha1(implode("\n", array_merge([
            $order->id,
            (string) $order->status,
            $order->resulted_at?->toIso8601String() ?? '',
        ], $parts)));
    }

    private function radiologySignature(RadiologyReport $report): string
    {
        return sha1(implode("\n", [
            $report->id,
            (string) $report->status,
            $report->finalized_at?->toIso8601String() ?? '',
            $report->amended_at?->toIso8601String() ?? '',
            (string) $report->findings,
            (string) $report->impression,
            (string) $report->recommendation,
        ]));
    }

    // ──────────────────────────────────────────────────────────────────
    // Detail context
    // ──────────────────────────────────────────────────────────────────

    /**
     * The underlying report for the detail screen, or null when the source
     * record is gone. Nothing here is derived; every field is the record's.
     *
     * @param  array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    private function resultContext(array $row): ?array
    {
        if ($row['_kind'] === 'lab') {
            /** @var LabOrder $order */
            $order = $row['_order'];
            /** @var Collection<int,LabResult> $analytes */
            $analytes = $row['_analytes'];

            $names = $this->userNames($analytes->pluck('verified_by')->filter()->unique()->all());

            return [
                'kind'       => 'lab',
                'order_id'   => $order->id,
                'test_name'  => $order->test_name,
                'ordered_at' => $order->ordered_at?->toIso8601String(),
                'status'     => $order->status,
                'analytes'   => $analytes->map(fn (LabResult $r) => $this->analyte($r, $names, $row['_alerted']))->values(),
            ];
        }

        /** @var RadiologyReport $report */
        $report = $row['_report'];

        $accession = $report->imaging_order_id
            ? ImagingOrder::where('id', $report->imaging_order_id)->value('accession_number')
            : null;

        return [
            'kind'             => 'radiology',
            'result_id'        => $report->id,
            'test_name'        => $this->radiologyTitle($report),
            'report_number'    => $accession,
            'report_status'    => $report->status,
            // radiology_reports records no abnormality flag. Null is "not
            // recorded" — the screen prints that rather than a reassurance.
            'flag'             => null,
            'technique'        => null,
            'findings'         => $report->findings,
            'impression'       => $report->impression,
            'recommendation'   => $report->recommendation,
            'reported_by_name' => $report->reported_by
                ? ($this->userNames([$report->reported_by])[$report->reported_by] ?? null)
                : null,
            'reported_at'      => $report->finalized_at?->toIso8601String(),
            // A report is a narrative, not a panel: there are no analytes, and
            // an empty list says exactly that.
            'analytes'         => [],
        ];
    }

    /**
     * @param  array<string,string>   $names
     * @param  Collection<string,int> $alerted
     * @return array<string,mixed>
     */
    private function analyte(LabResult $result, array $names, Collection $alerted): array
    {
        $flag = $result->flag;

        // A bench-raised alert on an otherwise unflagged row must not read as
        // unremarkable; the escalation itself is the lab's flag.
        if ($alerted->has($result->id) && $this->normaliseFlag($flag) === null) {
            $flag = 'CRITICAL';
        }

        return [
            'id'               => $result->id,
            'analyte'          => $result->parameter_name,
            'value'            => $result->value,
            'unit'             => $result->unit,
            // Verbatim from the laboratory. `ref_low` / `ref_high` are only
            // populated when the lab recorded the interval as a plain "a - b";
            // anything else stays null rather than being parsed into a number
            // this app would then be asserting.
            'reference'        => $result->reference_range,
            'ref_low'          => $this->refBound($result->reference_range, 0),
            'ref_high'         => $this->refBound($result->reference_range, 1),
            'flag'             => $flag,
            'is_abnormal'      => $this->flagIsAbnormal($flag) || $alerted->has($result->id),
            'verified_by_name' => $names[$result->verified_by] ?? null,
            'verified_at'      => $result->resulted_at?->toIso8601String(),
            'reported_at'      => $result->resulted_at?->toIso8601String(),
        ];
    }

    /**
     * Split a "3.5 - 5.1" interval the laboratory wrote. Returns null for any
     * shape this cannot read with certainty — a half-understood range is worse
     * than none.
     */
    private function refBound(?string $reference, int $index): ?string
    {
        if ($reference === null || ! preg_match('/^\s*(-?[\d.]+)\s*[-–]\s*(-?[\d.]+)\s*$/u', $reference, $m)) {
            return null;
        }

        return $m[$index + 1];
    }

    // ──────────────────────────────────────────────────────────────────
    // Serialisation, lookups, audit
    // ──────────────────────────────────────────────────────────────────

    /**
     * The wire shape. Internal keys (`_kind`, the loaded models, the signature)
     * never leave the server.
     *
     * @param  array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function publicRow(array $row): array
    {
        return [
            'id'                   => $row['id'],
            'category'             => $row['category'],
            'category_label'       => $row['category_label'],
            'severity'             => $row['severity'],
            'priority'             => $row['priority'],
            'is_critical'          => $row['is_critical'],
            'title'                => $row['title'],
            'body'                 => $row['body'],
            // The chart endpoints are keyed on the Health ID (see
            // doctorQueries.ts: "patientId is the Health ID"), so that is what
            // the row carries. A patient scoped out by IsDemoRecord yields null
            // — "not resolvable here", not "no patient".
            'patient_id'           => $row['patient_health_id'],
            'patient_name'         => $row['patient_name'],
            'patient_health_id'    => $row['patient_health_id'],
            'reported_at'          => $row['reported_at'],
            'read_at'              => $row['read_at'],
            'acknowledged_at'      => $row['acknowledged_at'],
            'acknowledged_by'      => $row['acknowledged_by'],
            'acknowledged_by_name' => $row['acknowledged_by_name'],
            'action_route'         => $row['action_route'],
            'action_id'            => $row['action_id'],
        ];
    }

    /**
     * @param  array<int,string> $ids
     * @return array<string,Patient>
     */
    private function patients(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        // Patient carries the IsDemoRecord global scope: on a demo deployment
        // this resolves demo patients only, and a name reading null is that
        // isolation working. The scope is deliberately not bypassed.
        return Patient::whereIn('id', $ids)
            ->get(['id', 'health_id', 'first_name', 'last_name'])
            ->keyBy('id')
            ->all();
    }

    /**
     * @param  array<int,string> $ids
     * @return array<string,string>
     */
    private function userNames(array $ids): array
    {
        $ids = array_values(array_filter(array_map('strval', $ids)));

        if ($ids === []) {
            return [];
        }

        return User::whereIn('id', $ids)
            ->pluck('name', 'id')
            ->map(fn ($n) => (string) $n)
            ->all();
    }

    /**
     * Every patient-scoped read of a result is logged, one row per patient,
     * exactly as MedicalRecordExportController logs a patient's own export. A
     * logging failure must never deny a clinician a critical result, so it is
     * caught — but it is recorded loudly in the application log.
     *
     * @param array<int,string> $patientIds
     */
    private function auditPatients(
        Request $request,
        array $patientIds,
        string $purpose,
        string $resourceType,
        ?string $resourceId,
        string $accessType
    ): void {
        $actorId = $this->providerUserId($request);

        if ($actorId === null || $patientIds === []) {
            return;
        }

        foreach (array_unique($patientIds) as $patientId) {
            try {
                AccessLogService::log(
                    patientId: $patientId,
                    actorId: $actorId,
                    actorType: 'provider',
                    organizationId: null,
                    facilityId: $this->providerFacilityId($request),
                    purpose: $purpose,
                    dataCategory: 'diagnostic_results',
                    resourceType: $resourceType,
                    resourceId: $resourceId,
                    accessType: $accessType,
                );
            } catch (\Throwable $e) {
                Log::error('doctor results inbox: access log failed', [
                    'patient_id' => $patientId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
