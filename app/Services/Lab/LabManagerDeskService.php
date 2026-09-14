<?php

namespace App\Services\Lab;

use App\Models\CriticalValueAlert;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The laboratory MANAGER'S desk (/facility/lab/dashboard, /facility/lab/requests,
 * sample rejection, the bench's printed report).
 *
 * The bench itself is LabDeskWebService; this class does not fork it. It adds
 * the four things docs/LAB_DASHBOARD_AUDIT.md found missing for the person
 * who runs the laboratory rather than works one order:
 *
 *  1. FIGURES. Open work per column, what was released and validated today,
 *     what is waiting for a second signature, critical values nobody has
 *     acknowledged, and turnaround — collected_at → resulted_at over the last
 *     thirty days, computed in SQL. Every number is read from the live tables;
 *     where there is nothing to compute from (no released order with both
 *     stamps) the figure is null and the page says "not enough data", never 0.
 *
 *  2. PATIENT REQUESTS. Delegated whole to LabDeskService::approveRequest /
 *     declineRequest so the web and the app decide the same way and the
 *     patient is told the same way.
 *
 *  3. SAMPLE REJECTION. The one transition the bench lacked. `rejected` is
 *     already one of LabOrder::TERMINAL_STATUSES (the model refuses every
 *     later move), so a rejection is final by construction. It is allowed
 *     only while no result exists — a panel with numbers on it is retracted
 *     (voided / entered-in-error), not rejected. The reason is required and
 *     appended to `notes`, the order's one free-text column; the order itself
 *     is otherwise untouched.
 *
 * Scope: every read and write is narrowed to the facility id the controller
 * hands in (PortalContextService); a row elsewhere is not found, never
 * forbidden. NOTHING HERE READS BILLING.
 */
class LabManagerDeskService
{
    /** How many days of released orders the turnaround figure looks back over. */
    public const TURNAROUND_WINDOW_DAYS = 30;

    /** How a rejection names itself inside `notes`. */
    public const REJECTED_TAG = '[rejected]';

    public function __construct(
        private readonly LabDeskService $desk,
        private readonly LabDeskWebService $web,
    ) {
    }

    // ──────────────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────────────

    /**
     * Everything the landing page shows.
     *
     * @return array{
     *     counts: array<string, int>,
     *     today: array{released: int, validated: int, collected: int, rejected: int},
     *     awaiting_validation: int,
     *     requests_pending: int,
     *     critical_open: int,
     *     critical: EloquentCollection<int, CriticalValueAlert>,
     *     turnaround: array{released_orders: int, mean_minutes: int|null, median_minutes: int|null, window_days: int},
     * }
     */
    public function dashboard(string $facilityId): array
    {
        $today = now()->startOfDay();

        $released = fn (): Builder => LabOrder::query()
            ->where('lab_orders.facility_id', $facilityId)
            ->whereIn(DB::raw('lower(lab_orders.status)'), LabOrder::RELEASED_STATUSES);

        return [
            'counts'              => $this->web->counts($facilityId),
            'today'               => [
                'released'  => (clone $released())->where('resulted_at', '>=', $today)->count(),
                'validated' => LabOrder::query()->where('facility_id', $facilityId)
                    ->whereRaw('lower(status) = ?', [LabDeskWebService::VALIDATED_STATUS])
                    ->where('updated_at', '>=', $today)->count(),
                'collected' => LabOrder::query()->where('facility_id', $facilityId)
                    ->where('collected_at', '>=', $today)->count(),
                'rejected'  => LabOrder::query()->where('facility_id', $facilityId)
                    ->whereRaw('lower(status) = ?', ['rejected'])
                    ->where('updated_at', '>=', $today)->count(),
            ],
            'awaiting_validation' => $this->web->counts($facilityId)['resulted'] ?? 0,
            'requests_pending'    => LabTestRequest::query()
                ->where('facility_id', $facilityId)->where('status', 'submitted')->count(),
            'critical_open'       => $this->criticalOpen($facilityId)->count(),
            'critical'            => $this->criticalOpen($facilityId)
                ->with(['labResult.labOrder.patient'])
                ->orderBy('critical_value_alerts.created_at')
                ->limit(20)
                ->get(),
            'turnaround'          => $this->turnaround($facilityId),
        ];
    }

    /**
     * Critical values raised on THIS facility's orders that no clinician has
     * acknowledged. `critical_value_alerts` carries no facility column: the
     * facility is reached through the result's order, which is the only honest
     * route.
     */
    private function criticalOpen(string $facilityId): Builder
    {
        return CriticalValueAlert::query()
            ->where('critical_value_alerts.acknowledged', false)
            ->whereIn('critical_value_alerts.lab_result_id', function ($q) use ($facilityId) {
                $q->select('lab_results.id')
                    ->from('lab_results')
                    ->join('lab_orders', 'lab_orders.id', '=', 'lab_results.lab_order_id')
                    ->where('lab_orders.facility_id', $facilityId);
            });
    }

    /**
     * Turnaround over the window: sample in (collected_at) to release
     * (resulted_at), released orders only, both stamps present. Mean and
     * median in whole minutes, or null when there is nothing to compute from.
     *
     * @return array{released_orders: int, mean_minutes: int|null, median_minutes: int|null, window_days: int}
     */
    public function turnaround(string $facilityId): array
    {
        $empty = [
            'released_orders' => 0,
            'mean_minutes'    => null,
            'median_minutes'  => null,
            'window_days'     => self::TURNAROUND_WINDOW_DAYS,
        ];

        try {
            $row = LabOrder::query()
                ->where('facility_id', $facilityId)
                ->whereIn(DB::raw('lower(status)'), LabOrder::RELEASED_STATUSES)
                ->whereNotNull('collected_at')
                ->whereNotNull('resulted_at')
                ->whereColumn('resulted_at', '>=', 'collected_at')
                ->where('resulted_at', '>=', now()->subDays(self::TURNAROUND_WINDOW_DAYS))
                ->selectRaw('count(*) as n')
                ->selectRaw('avg(extract(epoch from (resulted_at - collected_at))) as mean_s')
                ->selectRaw('percentile_cont(0.5) within group (order by extract(epoch from (resulted_at - collected_at))) as median_s')
                ->first();
        } catch (\Throwable $e) {
            Log::debug('lab manager desk: turnaround query failed', ['error' => $e->getMessage()]);

            return $empty;
        }

        $n = (int) ($row?->n ?? 0);

        if ($n === 0) {
            return $empty;
        }

        return [
            'released_orders' => $n,
            'mean_minutes'    => (int) round(((float) $row->mean_s) / 60),
            'median_minutes'  => (int) round(((float) $row->median_s) / 60),
            'window_days'     => self::TURNAROUND_WINDOW_DAYS,
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // Patient requests — delegated to the bench's own rules
    // ──────────────────────────────────────────────────────────────────

    /** @return EloquentCollection<int, LabTestRequest> */
    public function requests(string $facilityId, string $status = 'submitted'): EloquentCollection
    {
        return $this->desk->requests($facilityId, $status);
    }

    /** @return array<string, int> */
    public function requestCounts(string $facilityId): array
    {
        $counts = [];

        foreach (['submitted', 'approved', 'declined', 'cancelled'] as $status) {
            $counts[$status] = LabTestRequest::query()
                ->where('facility_id', $facilityId)->where('status', $status)->count();
        }

        return $counts;
    }

    public function findRequest(string $facilityId, string $id): ?LabTestRequest
    {
        return $this->desk->findRequest($facilityId, $id);
    }

    public function approveRequest(LabTestRequest $request, string $facilityId, string $userId): LabTestRequest
    {
        return $this->desk->approveRequest($request, $facilityId, $userId);
    }

    public function declineRequest(LabTestRequest $request, string $userId, string $reason): LabTestRequest
    {
        return $this->desk->declineRequest($request, $userId, $reason);
    }

    // ──────────────────────────────────────────────────────────────────
    // Sample rejection
    // ──────────────────────────────────────────────────────────────────

    /**
     * Refuse the sample. Final: `rejected` is terminal on the model. Only
     * while nothing has been entered — once a result exists the order is
     * retracted through the clinical path, not rejected here.
     */
    public function reject(LabOrder $order, string $reason, string $userId): LabOrder
    {
        $reason = trim($reason);

        if ($reason === '') {
            $this->refuse('reason_required', 422, __('lab_manager.err.reason_required'));
        }

        if ($order->isTerminal()) {
            $this->refuse('order_closed', 409, __('lab_desk.err.order_closed', ['status' => (string) $order->status]));
        }

        if ($order->isReleased()) {
            $this->refuse('already_released', 409, __('lab_manager.err.reject_released'));
        }

        DB::transaction(function () use ($order, $reason, $userId) {
            /** @var LabOrder $locked */
            $locked = LabOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal() || $locked->isReleased()) {
                $this->refuse('already_released', 409, __('lab_manager.err.reject_released'));
            }

            if (LabResult::query()->where('lab_order_id', $locked->id)->exists()) {
                $this->refuse('has_results', 409, __('lab_manager.err.reject_has_results'));
            }

            $locked->forceFill([
                'status' => 'rejected',
                'notes'  => trim(implode("\n", array_filter([
                    $locked->notes,
                    self::REJECTED_TAG . ' ' . $reason . ' (' . now()->toDateTimeString() . ')',
                ]))),
            ])->save();
        });

        $rejected = $order->refresh();

        $this->desk->notifySampleRejected($rejected, $reason, $userId);

        return $rejected;
    }

    /** The reason a rejected order carries, or null. */
    public function rejectionReason(LabOrder $order): ?string
    {
        if (preg_match('/\[rejected\]\s*(.+?)\s*\(\d{4}-\d{2}-\d{2} [\d:]+\)\s*$/m', (string) $order->notes, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** Same shape as LabDeskWebService::refuse(), so one controller catch translates both. @return never */
    private function refuse(string $errorCode, int $status, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'message'    => $message,
            'error_code' => $errorCode,
        ], $status));
    }
}
