<?php

namespace App\Services\Pharmacy;

use App\Http\Controllers\MedicalId\PharmacyPortalController;
use App\Models\DrugInteractionRule;
use App\Models\Facility;
use App\Models\InventoryItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockBatch;
use App\Models\User;
use App\Support\ResultFlagResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * The read side of the web counter: one prescription in full, and the
 * register of what this facility has handed over.
 *
 * Facility scope is PharmacyDeskService's rule, unchanged — a script is
 * visible here when it is routed to this counter (or not routed anywhere),
 * or when it was issued here. Everything else is a 404.
 *
 * Nothing here writes. Dispensing itself goes through
 * PharmacyDeskService::dispense (the same path as the phone counter) so the
 * web desk cannot drift from it.
 */
class PharmacyDispensingService
{
    public function __construct(
        private readonly PharmacyDeskService $desk,
        private readonly PharmacyStockOpsService $ops,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Detail
    // ══════════════════════════════════════════════════════════════════════

    /**
     * One script, scoped to this counter.
     *
     * @return array{
     *   prescription: Prescription,
     *   dispenser: ?User,
     *   ageBand: ?string,
     *   timeline: list<array{key: string, at: ?Carbon, done: bool}>,
     *   lines: list<array{item: PrescriptionItem, controlled: bool, stock: ?InventoryItem, usable: int, batches: Collection}>,
     *   history: list<array{item: PrescriptionItem, at: ?Carbon}>,
     *   interactions: list<array{a: string, b: string, rule: DrugInteractionRule}>,
     * }
     * @throws PharmacyDeskException
     */
    public function detail(Facility $facility, string $id): array
    {
        $rx = $this->desk->find($facility, $id);
        $rx->loadMissing(['items.medicine', 'patient', 'prescriber:id,name', 'facility:id,name']);

        $dispenser = $rx->dispensed_by ? User::query()->find($rx->dispensed_by) : null;

        $dob     = $rx->patient?->date_of_birth;
        $ageBand = $dob ? ResultFlagResolver::ageBand($dob) : null;

        $stockByMedicine = $this->stockByMedicine((string) $facility->id, $rx->items);

        $lines = $rx->items->map(function (PrescriptionItem $item) use ($stockByMedicine) {
            $stock = $item->medicine_id ? ($stockByMedicine[(string) $item->medicine_id] ?? null) : null;

            return [
                'item'       => $item,
                'controlled' => $this->isControlledItem($item),
                'stock'      => $stock,
                'usable'     => $stock ? $this->ops->usableQuantity($stock) : 0,
                'batches'    => $stock ? $this->ops->scopeUsable($stock->batches()->getQuery())
                    ->where('quantity_available', '>', 0)
                    ->orderByRaw('expiry_date ASC NULLS LAST')
                    ->get() : new Collection(),
            ];
        })->values()->all();

        $history = $rx->items
            ->filter(fn (PrescriptionItem $item) => $item->isDispensed())
            ->sortBy(fn (PrescriptionItem $item) => $item->dispensed_at?->getTimestamp() ?? 0)
            ->map(fn (PrescriptionItem $item) => ['item' => $item, 'at' => $item->dispensed_at])
            ->values()
            ->all();

        return [
            'prescription' => $rx,
            'dispenser'    => $dispenser,
            'ageBand'      => $ageBand,
            'timeline'     => $this->timeline($rx),
            'lines'        => $lines,
            'history'      => $history,
            'interactions' => $this->interactions($rx->items),
        ];
    }

    /**
     * issued → routed → accepted → ready → dispensed / partially dispensed /
     * cancelled. Only steps that happened carry a date; the rest are shown
     * as not reached, never invented.
     *
     * @return list<array{key: string, at: ?Carbon, done: bool}>
     */
    public function timeline(Prescription $rx): array
    {
        $routing = (string) ($rx->pharmacy_routing_status ?? '');
        $status  = (string) ($rx->status ?? '');

        $routed   = $rx->pharmacy_route_id !== null;
        $accepted = in_array($routing, [PharmacyDeskService::ROUTING_CONFIRMED, PharmacyDeskService::ROUTING_READY, PharmacyDeskService::ROUTING_DISPENSED], true);
        $ready    = in_array($routing, [PharmacyDeskService::ROUTING_READY, PharmacyDeskService::ROUTING_DISPENSED], true);

        $steps = [
            ['key' => 'issued',   'at' => $rx->prescribed_at ?? $rx->created_at, 'done' => true],
            ['key' => 'routed',   'at' => $rx->pharmacy_routing_sent_at,          'done' => $routed],
            ['key' => 'accepted', 'at' => $rx->pharmacy_confirmed_at,             'done' => $accepted],
            ['key' => 'ready',    'at' => null,                                    'done' => $ready],
        ];

        $steps[] = match ($status) {
            'dispensed'           => ['key' => 'dispensed',           'at' => $rx->dispensed_at, 'done' => true],
            'partially_dispensed' => ['key' => 'partially_dispensed', 'at' => $rx->items->max('dispensed_at'), 'done' => true],
            'cancelled', 'voided', 'entered_in_error', 'amended'
                                  => ['key' => 'cancelled',           'at' => $rx->voided_at,    'done' => true],
            'expired'             => ['key' => 'expired',             'at' => $rx->expires_at,   'done' => true],
            default               => ['key' => 'dispensed',           'at' => null,              'done' => false],
        };

        return $steps;
    }

    /**
     * Known interaction rules between any two lines of the script, matched on
     * the items' drug codes. No code, no match — a rule is never guessed from
     * a name.
     *
     * @param  Collection<int, PrescriptionItem>  $items
     * @return list<array{a: string, b: string, rule: DrugInteractionRule}>
     */
    public function interactions(Collection $items): array
    {
        $coded = $items->filter(fn (PrescriptionItem $i) => filled($i->drug_code))->values();
        $found = [];

        for ($x = 0; $x < $coded->count(); $x++) {
            for ($y = $x + 1; $y < $coded->count(); $y++) {
                $rule = DrugInteractionRule::checkPair((string) $coded[$x]->drug_code, (string) $coded[$y]->drug_code);
                if ($rule) {
                    $found[] = ['a' => $coded[$x]->drug_name, 'b' => $coded[$y]->drug_name, 'rule' => $rule];
                }
            }
        }

        return $found;
    }

    /**
     * Controlled: the catalogue flag, or the keyword net the controlled
     * register already uses for rows the flag was never set on.
     */
    public function isControlledItem(PrescriptionItem $item): bool
    {
        if ($item->relationLoaded('medicine') && $item->medicine?->is_controlled) {
            return true;
        }

        $name = strtolower((string) $item->drug_name);

        foreach (PharmacyPortalController::CONTROLLED_KEYWORDS as $kw) {
            if ($name !== '' && str_contains($name, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, PrescriptionItem>  $items
     * @return array<string, InventoryItem>
     */
    private function stockByMedicine(string $facilityId, Collection $items): array
    {
        $medicineIds = $items->pluck('medicine_id')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();

        if ($medicineIds === []) {
            return [];
        }

        return InventoryItem::query()
            ->where('facility_id', $facilityId)
            ->whereIn('medicine_id', $medicineIds)
            ->get()
            ->keyBy(fn (InventoryItem $i) => (string) $i->medicine_id)
            ->all();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Register
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Scripts this counter has handed over, newest first: the issuing-facility
     * half and the routed half, exactly as the queue scopes them.
     *
     * @param  array{from?: ?string, to?: ?string, pharmacist?: ?string, controlled?: bool}  $filters
     */
    public function registerQuery(string $facilityId, array $filters = []): Builder
    {
        $routeIds = $this->desk->routeIds($facilityId);

        $q = Prescription::query()
            ->with(['patient', 'items.medicine'])
            ->where(function (Builder $w) use ($facilityId, $routeIds) {
                $w->where('facility_id', $facilityId);
                if ($routeIds !== []) {
                    $w->orWhereIn('pharmacy_route_id', $routeIds);
                }
            })
            ->whereIn('status', ['dispensed', 'partially_dispensed'])
            ->whereHas('items', fn (Builder $i) => $i->whereNotNull('dispensed_at'));

        if (! empty($filters['from'])) {
            $q->whereHas('items', fn (Builder $i) => $i->whereDate('dispensed_at', '>=', $filters['from']));
        }
        if (! empty($filters['to'])) {
            $q->whereHas('items', fn (Builder $i) => $i->whereDate('dispensed_at', '<=', $filters['to']));
        }
        if (! empty($filters['pharmacist'])) {
            $q->where('dispensed_by', $filters['pharmacist']);
        }
        if (! empty($filters['controlled'])) {
            $keywords = PharmacyPortalController::CONTROLLED_KEYWORDS;
            $q->whereHas('items', function (Builder $i) use ($keywords) {
                $i->whereNotNull('dispensed_at')->where(function (Builder $c) use ($keywords) {
                    $c->whereHas('medicine', fn (Builder $m) => $m->where('is_controlled', true));
                    foreach ($keywords as $kw) {
                        $c->orWhere('drug_name', 'ilike', "%{$kw}%");
                    }
                });
            });
        }

        return $q->orderByRaw('COALESCE(dispensed_at, updated_at) DESC');
    }

    /** @param array{from?: ?string, to?: ?string, pharmacist?: ?string, controlled?: bool} $filters */
    public function register(string $facilityId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->registerQuery($facilityId, $filters)->paginate($perPage);
    }

    /**
     * The pharmacists who have dispensed at this counter — the filter's options.
     *
     * @return Collection<int, User>
     */
    public function dispensers(string $facilityId): Collection
    {
        $ids = $this->registerQuery($facilityId)->reorder()->whereNotNull('dispensed_by')->distinct()->pluck('dispensed_by');

        return User::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * One row per dispensed line, for the CSV.
     *
     * @return \Generator<int, array<int, string|int>>
     */
    public function registerRows(string $facilityId, array $filters = []): \Generator
    {
        foreach ($this->registerQuery($facilityId, $filters)->lazy(200) as $rx) {
            $dispenser = $rx->dispensed_by ? User::query()->find($rx->dispensed_by, ['id', 'name']) : null;

            foreach ($rx->items as $item) {
                if (! $item->isDispensed()) {
                    continue;
                }

                yield [
                    $item->dispensed_at?->toDateTimeString() ?? '',
                    (string) $rx->reference,
                    self::maskName($rx->patient?->first_name, $rx->patient?->last_name),
                    (string) ($rx->patient?->health_id ?? ''),
                    (string) $item->drug_name,
                    (string) ($item->dose ?? ''),
                    (string) ($item->quantity ?? ''),
                    (string) ($dispenser?->name ?? ''),
                    $this->isControlledItem($item) ? 'yes' : 'no',
                    (string) ($item->dispense_notes ?? ''),
                ];
            }
        }
    }

    /** "Ngozi S." — the register is a log, not a chart; the Health ID is the key. */
    public static function maskName(?string $first, ?string $last): string
    {
        $first = trim((string) $first);
        $last  = trim((string) $last);

        if ($first === '' && $last === '') {
            return '—';
        }

        return trim($first . ' ' . ($last !== '' ? mb_substr($last, 0, 1) . '.' : ''));
    }
}
