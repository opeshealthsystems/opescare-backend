<?php

namespace App\Services\Pharmacy;

use App\Models\ControlledSubstanceDispensing;
use App\Models\ControlledSubstanceInventory;
use App\Models\InventoryItem;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The pharmacy's four management reports and the controlled-substance
 * register, as plain tables — the same rows feed the on-screen preview, the
 * CSV and the secure-sheet PDF, so what the manager sees is what they export.
 *
 * ── What is read, and from where ────────────────────────────────────────
 * Every stock figure comes from the facility's OWN ledger (`stock_batches`
 * through PharmacyStockOpsService), with the same usable predicate the
 * finder is told: open lots, not past their date. Nothing here reads the
 * published availability row, nothing reads a seeded fixture, and nothing
 * fills a gap: a pharmacy with no lots has an empty report, which is the
 * correct report.
 *
 * ── What is NOT in the dispensing report ─────────────────────────────────
 * The dispensing-volume report is counts only — lines and scripts per day
 * and per pharmacist. It never carries a patient's name, Health ID or
 * anything that resolves to one; a manager measuring throughput does not
 * need to know who collected what.
 *
 * ── Money ────────────────────────────────────────────────────────────────
 * XAF has no minor unit (App\Services\Billing\Xaf). Prices are the whole
 * francs on `inventory_items.selling_price_xaf`, values are integer
 * products, and an item without a selling price is valued as unknown —
 * shown as such and left out of the total, never priced at zero.
 */
class PharmacyReportService
{
    public const REPORTS = ['expiry', 'valuation', 'dispensing', 'low_stock'];

    public const EXPIRY_WINDOWS = [30, 60, 90];

    /** Rows shown on the reports page before the export is needed. */
    public const PREVIEW_ROWS = 10;

    public function __construct(
        private readonly PharmacyStockOpsService $ops,
        private readonly PharmacyDeskService $desk,
        private readonly ControlledSubstanceService $controlled,
    ) {
    }

    /**
     * One report by key, with the parameters the page passes through.
     *
     * @param  array{days?: int, from?: Carbon, to?: Carbon}  $params
     * @return array{key: string, headers: list<string>, rows: list<list<string|int|null>>, meta: array<string, mixed>}
     */
    public function report(string $report, string $facilityId, array $params = []): array
    {
        return match ($report) {
            'expiry'     => $this->expiring($facilityId, (int) ($params['days'] ?? self::EXPIRY_WINDOWS[0])),
            'valuation'  => $this->valuation($facilityId),
            'dispensing' => $this->dispensing($facilityId, $params['from'] ?? Carbon::today()->subDays(29), $params['to'] ?? Carbon::today()),
            'low_stock'  => $this->lowStock($facilityId),
            default      => throw new InvalidArgumentException("Unknown pharmacy report: {$report}"),
        };
    }

    /** The expiry window actually used: an unlisted value falls back to the first. */
    public static function window(mixed $days): int
    {
        $days = (int) $days;

        return in_array($days, self::EXPIRY_WINDOWS, true) ? $days : self::EXPIRY_WINDOWS[0];
    }

    // ══════════════════════════════════════════════════════════════════════
    // (a) Expiring stock
    // ══════════════════════════════════════════════════════════════════════

    /** Open lots holding packs that expire within $days, soonest first. */
    public function expiring(string $facilityId, int $days): array
    {
        $days  = self::window($days);
        $today = Carbon::today();

        $batches = $this->ops->batchQuery($facilityId)
            ->with('item.medicine')
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $today->copy()->addDays($days))
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->get();

        $rows = $batches->map(fn (StockBatch $b) => [
            $b->item?->medicine?->name ?? $b->item?->name,
            $b->lot_number,
            $b->expiry_date?->toDateString(),
            (int) $today->diffInDays($b->expiry_date, false),
            (int) $b->quantity_available,
        ])->values()->all();

        return [
            'key'     => 'expiry',
            'headers' => [
                __('pharmacy_reports.col_medicine'),
                __('pharmacy_reports.col_lot'),
                __('pharmacy_reports.col_expiry'),
                __('pharmacy_reports.col_days_left'),
                __('pharmacy_reports.col_packs'),
            ],
            'rows'    => $rows,
            'meta'    => [
                'days'  => $days,
                'lots'  => count($rows),
                'packs' => (int) $batches->sum('quantity_available'),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // (b) Stock valuation
    // ══════════════════════════════════════════════════════════════════════

    /** Usable packs × selling price, per item, in whole francs. */
    public function valuation(string $facilityId): array
    {
        $items = $this->ops->itemsWithBatches($facilityId);

        $total    = 0;
        $unpriced = 0;
        $packs    = 0;

        $rows = $items->map(function (InventoryItem $item) use (&$total, &$unpriced, &$packs) {
            $usable = $this->ops->usableQuantity($item);
            $price  = $item->selling_price_xaf;
            $value  = $price === null ? null : $usable * (int) $price;

            $packs += $usable;

            if ($value === null) {
                $unpriced++;
            } else {
                $total += $value;
            }

            return [
                $item->medicine?->name ?? $item->name,
                $usable,
                $price === null ? null : (int) $price,
                $value,
            ];
        })->values()->all();

        return [
            'key'     => 'valuation',
            'headers' => [
                __('pharmacy_reports.col_medicine'),
                __('pharmacy_reports.col_usable_packs'),
                __('pharmacy_reports.col_price_xaf'),
                __('pharmacy_reports.col_value_xaf'),
            ],
            'rows'    => $rows,
            'meta'    => [
                'items'     => count($rows),
                'packs'     => $packs,
                'total_xaf' => $total,
                'unpriced'  => $unpriced,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // (c) Dispensing volume — counts only, never a patient
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Lines and scripts handed over at this counter, by day and by the
     * pharmacist who recorded the hand-over. A script counts for this
     * pharmacy when it is routed to one of its dispensing routes; the day
     * is the line's own `dispensed_at`.
     */
    public function dispensing(string $facilityId, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $routeIds = $this->desk->routeIds($facilityId);

        $base = fn () => DB::table('prescription_items')
            ->join('prescriptions', 'prescriptions.id', '=', 'prescription_items.prescription_id')
            ->whereIn('prescriptions.pharmacy_route_id', $routeIds === [] ? ['00000000-0000-0000-0000-000000000000'] : $routeIds)
            ->where('prescription_items.status', 'dispensed')
            ->whereNotNull('prescription_items.dispensed_at')
            ->whereBetween('prescription_items.dispensed_at', [$from, $to]);

        // PostgreSQL: TO_CHAR, never DATE_FORMAT.
        $byDay = $base()
            ->selectRaw("TO_CHAR(prescription_items.dispensed_at, 'YYYY-MM-DD') AS day")
            ->selectRaw('COUNT(*) AS lines')
            ->selectRaw('COUNT(DISTINCT prescriptions.id) AS scripts')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byPharmacist = $base()
            ->selectRaw('prescriptions.dispensed_by AS pharmacist_id')
            ->selectRaw('COUNT(*) AS lines')
            ->selectRaw('COUNT(DISTINCT prescriptions.id) AS scripts')
            ->groupBy('prescriptions.dispensed_by')
            ->orderByDesc('lines')
            ->get();

        $names = User::query()
            ->whereIn('id', $byPharmacist->pluck('pharmacist_id')->filter()->unique())
            ->pluck('name', 'id');

        $rows = [];

        foreach ($byDay as $d) {
            $rows[] = [__('pharmacy_reports.group_day'), (string) $d->day, (int) $d->scripts, (int) $d->lines];
        }

        foreach ($byPharmacist as $p) {
            $who = $p->pharmacist_id ? ($names[$p->pharmacist_id] ?? __('pharmacy_reports.unknown_pharmacist')) : __('pharmacy_reports.unknown_pharmacist');
            $rows[] = [__('pharmacy_reports.group_pharmacist'), $who, (int) $p->scripts, (int) $p->lines];
        }

        return [
            'key'     => 'dispensing',
            'headers' => [
                __('pharmacy_reports.col_group'),
                __('pharmacy_reports.col_day_or_pharmacist'),
                __('pharmacy_reports.col_scripts'),
                __('pharmacy_reports.col_lines'),
            ],
            'rows'    => $rows,
            'meta'    => [
                'from'    => $from->toDateString(),
                'to'      => $to->toDateString(),
                'lines'   => (int) $byDay->sum('lines'),
                'scripts' => (int) $byDay->sum('scripts'),
                'days'    => $byDay->count(),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // (d) Low and zero stock against reorder rules
    // ══════════════════════════════════════════════════════════════════════

    /** Items under their active minimum, with the rule beside the count. */
    public function lowStock(string $facilityId): array
    {
        $items = $this->ops->belowMinimum($facilityId);

        $rows = $items->map(fn (InventoryItem $item) => [
            $item->medicine?->name ?? $item->name,
            (int) $item->usable_quantity,
            (int) $item->rule_level,
            (int) $item->rule_quantity,
            (int) $item->usable_quantity === 0 ? __('pharmacy_reports.state_out') : __('pharmacy_reports.state_low'),
        ])->values()->all();

        return [
            'key'     => 'low_stock',
            'headers' => [
                __('pharmacy_reports.col_medicine'),
                __('pharmacy_reports.col_usable_packs'),
                __('pharmacy_reports.col_reorder_level'),
                __('pharmacy_reports.col_reorder_qty'),
                __('pharmacy_reports.col_state'),
            ],
            'rows'    => $rows,
            'meta'    => [
                'items' => count($rows),
                'out'   => $items->filter(fn ($i) => (int) $i->usable_quantity === 0)->count(),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // Controlled-substance register, one month
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Every register entry of the month, newest first within each kind:
     * dispense events (with witness state), witness confirmations, and
     * reconciliations recorded on the inventory rows. The patient appears as
     * a Health ID only — a register names who received a controlled drug,
     * but a name next to it is not needed to audit the balance.
     *
     * @return array{month: string, from: string, to: string, headers: list<string>, rows: list<list<string|int|float|null>>, meta: array<string, int>}
     */
    public function controlledRegister(string $facilityId, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to   = $month->copy()->endOfMonth();

        $log = $this->controlled->getDispenseLog($facilityId, $from, $to);

        $rows = [];

        foreach ($log as $e) {
            /** @var ControlledSubstanceDispensing $e */
            $rows[] = [
                $e->dispensed_at?->format('Y-m-d H:i'),
                __('pharmacy_reports.entry_dispense'),
                $e->drug_name,
                $e->schedule,
                $e->quantity_dispensed !== null ? (float) $e->quantity_dispensed : null,
                $e->unit,
                $e->stock_balance_before !== null ? (float) $e->stock_balance_before : null,
                $e->stock_balance_after !== null ? (float) $e->stock_balance_after : null,
                $e->patient?->health_id,
                $e->dispensedBy?->name,
                $e->witness_confirmed_at
                    ? ($e->witness?->name ?? __('pharmacy_reports.witness_confirmed'))
                    : ($e->witness_id ? __('pharmacy_reports.witness_pending') : __('pharmacy_reports.witness_none')),
                $e->lot_number,
            ];
        }

        $witnessed = $log->filter(fn ($e) => $e->witness_confirmed_at !== null && $e->witness_confirmed_at->between($from, $to));

        foreach ($witnessed as $e) {
            $rows[] = [
                $e->witness_confirmed_at?->format('Y-m-d H:i'),
                __('pharmacy_reports.entry_witness'),
                $e->drug_name,
                $e->schedule,
                null,
                $e->unit,
                null,
                null,
                null,
                $e->witness?->name,
                __('pharmacy_reports.witness_confirmed'),
                $e->lot_number,
            ];
        }

        $reconciled = $this->controlled->getInventory($facilityId)
            ->filter(fn (ControlledSubstanceInventory $i) => $i->last_reconciled_at !== null && $i->last_reconciled_at->between($from, $to));

        $reconcilers = User::query()
            ->whereIn('id', $reconciled->pluck('last_reconciled_by')->filter()->unique())
            ->pluck('name', 'id');

        foreach ($reconciled as $i) {
            $rows[] = [
                $i->last_reconciled_at?->format('Y-m-d H:i'),
                __('pharmacy_reports.entry_reconciliation'),
                $i->drug_name,
                $i->schedule,
                null,
                $i->unit,
                null,
                (float) $i->current_balance,
                null,
                $i->last_reconciled_by ? ($reconcilers[$i->last_reconciled_by] ?? null) : null,
                null,
                null,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) $b[0], (string) $a[0]));

        return [
            'month'   => $from->format('Y-m'),
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'headers' => [
                __('pharmacy_reports.col_when'),
                __('pharmacy_reports.col_entry'),
                __('pharmacy_reports.col_drug'),
                __('pharmacy_reports.col_schedule'),
                __('pharmacy_reports.col_quantity'),
                __('pharmacy_reports.col_unit'),
                __('pharmacy_reports.col_balance_before'),
                __('pharmacy_reports.col_balance_after'),
                __('pharmacy_reports.col_health_id'),
                __('pharmacy_reports.col_by'),
                __('pharmacy_reports.col_witness'),
                __('pharmacy_reports.col_lot'),
            ],
            'rows'    => $rows,
            'meta'    => [
                'dispensed'       => $log->count(),
                'witnessed'       => $witnessed->count(),
                'reconciliations' => $reconciled->count(),
                'awaiting'        => $log->filter(fn ($e) => $e->witness_confirmed_at === null && $e->schedule === 'schedule_ii')->count(),
            ],
        ];
    }
}
