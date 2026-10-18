<?php

namespace App\Services\Imaging;

use App\Enums\ImagingOrderStatus;
use App\Models\ImagingOrder;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The imaging desk — every rule behind /facility/imaging.
 *
 * SCOPE. Every read and write takes the facility id the controller got from
 * PortalContextService. An order at another facility is not refused, it does
 * not exist: find() returns null and the controller 404s.
 *
 * STATUS. Transitions are the enum's (App\Enums\ImagingOrderStatus::next()).
 * A move the enum does not allow is refused with a 422 the controller turns
 * into a flash — never silently ignored, never forced.
 *
 * ACCESSION NUMBERS. `IMG-YYYYMM-NNNN`, numbered per facility per month,
 * assigned at intake so the order is identifiable from the moment it exists.
 * The column is globally unique, so the prefix carries a short facility tag.
 *
 * NOTHING HERE READS BILLING. Care is never gated on payment.
 */
class ImagingDeskService
{
    public const MODALITIES = ['xray', 'ultrasound', 'ct', 'mri', 'mammography', 'other'];
    public const URGENCIES  = ['routine', 'urgent', 'stat'];

    /** Worklist tabs: the enum's five, plus "awaiting report" (completed, no final report). */
    public const TABS = ['pending', 'scheduled', 'in_progress', 'awaiting_report', 'reported', 'cancelled'];

    /** radiology_reports.status values that count as "reported" (a signed-off report exists). */
    public const REPORTED_STATUSES = ['final', 'amended', 'corrected'];

    private const URGENCY_RANK_SQL = "CASE urgency WHEN 'stat' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END";

    // ── Reads ────────────────────────────────────────────────────────────────

    /**
     * @param array{tab?:string, modality?:string, urgency?:string, date?:string, health_id?:string} $filters
     */
    public function worklist(string $facilityId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->base($facilityId);

        $this->whereTab($query, $filters['tab'] ?? 'pending');

        if (! empty($filters['modality'])) {
            $query->where('modality', $filters['modality']);
        }
        if (! empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }
        if (! empty($filters['date'])) {
            $query->whereDate('ordered_at', $filters['date']);
        }
        if (! empty($filters['health_id'])) {
            $patient = Patient::resolveHealthId($filters['health_id']);
            // No patient: an empty list, not a scan of everyone else.
            $query->where('patient_id', $patient?->id ?? Str::uuid()->toString());
        }

        return $query
            ->orderByRaw(self::URGENCY_RANK_SQL)
            ->orderBy('ordered_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return array<string,int> tab => count */
    public function counts(string $facilityId): array
    {
        $out = [];
        foreach (self::TABS as $tab) {
            $q = $this->base($facilityId);
            $this->whereTab($q, $tab);
            $out[$tab] = $q->count();
        }

        return $out;
    }

    /** The order in THIS facility's scope, or null — whether it exists elsewhere or not at all. */
    public function find(string $facilityId, string $id): ?ImagingOrder
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return ImagingOrder::query()
            ->with(['patient', 'orderedBy', 'report'])
            ->where('facility_id', $facilityId)
            ->find($id);
    }

    // ── Intake ───────────────────────────────────────────────────────────────

    /**
     * @param array{
     *   patient_id:string, modality:string, body_part?:?string, clinical_indication?:?string,
     *   urgency?:string, referring_physician?:?string, notes?:?string, scheduled_at?:?string
     * } $data
     */
    public function create(string $facilityId, User $actor, array $data): ImagingOrder
    {
        $patient = Patient::resolveId($data['patient_id']);

        if ($patient === null) {
            $this->refuse(422, 'patient_not_found', __('imaging_desk.err.patient_not_found'));
        }

        return DB::transaction(function () use ($facilityId, $actor, $data, $patient) {
            $scheduledAt = ! empty($data['scheduled_at']) ? $data['scheduled_at'] : null;

            return ImagingOrder::create([
                'patient_id'          => $patient->id,
                'facility_id'         => $facilityId,
                'ordered_by'          => $actor->id,
                'modality'            => $data['modality'],
                'body_part'           => $data['body_part'] ?? null,
                'clinical_indication' => $data['clinical_indication'] ?? null,
                'urgency'             => $data['urgency'] ?? 'routine',
                'referring_physician' => $data['referring_physician'] ?? null,
                'notes'               => $data['notes'] ?? null,
                // A slot given at intake books the order straight away.
                'status'              => $scheduledAt ? ImagingOrderStatus::Scheduled : ImagingOrderStatus::Pending,
                'scheduled_at'        => $scheduledAt,
                'ordered_at'          => now(),
                'accession_number'    => $this->nextAccession($facilityId),
            ]);
        });
    }

    /**
     * IMG-YYYYMM-NNNN, counted per facility per month. The column is unique
     * across facilities, so a short facility tag sits between the month and
     * the counter: IMG-202610-A1B2-0001.
     */
    public function nextAccession(string $facilityId): string
    {
        $month  = now()->format('Ym');
        $tag    = strtoupper(substr(str_replace('-', '', $facilityId), 0, 4));
        $prefix = "IMG-{$month}-{$tag}-";

        $last = ImagingOrder::query()
            ->where('facility_id', $facilityId)
            ->where('accession_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('accession_number')
            ->value('accession_number');

        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    // ── Transitions ──────────────────────────────────────────────────────────

    public function schedule(ImagingOrder $order, string $scheduledAt): ImagingOrder
    {
        $this->move($order, ImagingOrderStatus::Scheduled, ['scheduled_at' => $scheduledAt]);

        return $order;
    }

    public function start(ImagingOrder $order): ImagingOrder
    {
        $this->move($order, ImagingOrderStatus::InProgress, ['started_at' => now()]);

        return $order;
    }

    public function complete(ImagingOrder $order): ImagingOrder
    {
        $this->move($order, ImagingOrderStatus::Completed, ['completed_at' => now()]);

        return $order;
    }

    public function cancel(ImagingOrder $order, string $reason): ImagingOrder
    {
        if (trim($reason) === '') {
            $this->refuse(422, 'reason_required', __('imaging_desk.err.reason_required'));
        }

        $this->move($order, ImagingOrderStatus::Cancelled, [
            'cancelled_at'  => now(),
            'cancel_reason' => trim($reason),
        ]);

        return $order;
    }

    /** Timeline rows for the detail page: [key, at] in order, only those that happened. */
    public function timeline(ImagingOrder $order): array
    {
        $rows = [
            ['key' => 'ordered',   'at' => $order->ordered_at],
            ['key' => 'scheduled', 'at' => $order->scheduled_at],
            ['key' => 'started',   'at' => $order->started_at],
            ['key' => 'completed', 'at' => $order->completed_at],
            ['key' => 'cancelled', 'at' => $order->cancelled_at],
        ];

        return array_values(array_filter($rows, fn ($r) => $r['at'] !== null));
    }

    // ── internals ────────────────────────────────────────────────────────────

    private function move(ImagingOrder $order, ImagingOrderStatus $to, array $extra): void
    {
        $from = $order->status instanceof ImagingOrderStatus
            ? $order->status
            : ImagingOrderStatus::from((string) $order->status);

        if (! $from->canTransitionTo($to)) {
            $this->refuse(422, 'invalid_transition', __('imaging_desk.err.invalid_transition', [
                'from' => __($from->labelKey()),
                'to'   => __($to->labelKey()),
            ]));
        }

        $order->fill($extra + ['status' => $to])->save();
    }

    private function base(string $facilityId): Builder
    {
        return ImagingOrder::query()
            ->with(['patient', 'orderedBy', 'report'])
            ->where('facility_id', $facilityId);
    }

    private function whereTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'awaiting_report' => $query->where('status', ImagingOrderStatus::Completed->value)
                ->whereDoesntHave('report', fn ($q) => $q->whereIn('status', self::REPORTED_STATUSES)),
            'reported' => $query->where('status', ImagingOrderStatus::Completed->value)
                ->whereHas('report', fn ($q) => $q->whereIn('status', self::REPORTED_STATUSES)),
            default => $query->where('status', ImagingOrderStatus::tryFrom($tab)?->value ?? ImagingOrderStatus::Pending->value),
        };
    }

    private function refuse(int $status, string $code, string $message): never
    {
        throw new HttpResponseException(response()->json([
            'message'    => $message,
            'error_code' => $code,
        ], $status));
    }
}
