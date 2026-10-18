<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Pharmacy\PharmacyStockOpsService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * The pharmacy back office: batches & expiry, the movement log, reorder
 * rules and goods receipts. Read the service docblock first — the rules
 * (expired stock never reaches the finder; the ledger is append-only) live
 * there, and this controller only resolves who is writing and validates
 * what they sent.
 *
 * ── SCOPE ──────────────────────────────────────────────────────────────────
 * The facility is read from PortalContextService only — never a header,
 * never the body, never a "first facility in the table" fallback. Every
 * table here carries facility_id, so a batch, receipt, supplier or order
 * belonging to another pharmacy is not refused: it does not exist (404).
 * The role gate is `capability:pharmacy_stock` on the routes (403
 * `role_forbidden`).
 *
 * No patient appears on any of these screens. The ledger knows lots and
 * packs; who a pack was dispensed to is the prescription's business.
 */
class PharmacyStockOpsController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyStockOpsService $ops,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Batches & expiry
    // ══════════════════════════════════════════════════════════════════════

    public function batches(): View
    {
        $facilityId = $this->facilityId();

        $items = $this->ops->itemsWithBatches($facilityId);

        return view('portals.pharmacy.stock_ops.batches', [
            'items'      => $items,
            'usable'     => $items->mapWithKeys(fn ($i) => [$i->id => $this->ops->usableQuantity($i)]),
            'expiring'   => $this->ops->expiringSoon($facilityId),
            'expired'    => $this->ops->expired($facilityId),
            'undated'    => $this->ops->expiryUnknown($facilityId),
            'atc'        => $this->ops->atcLabels($items->map(fn ($i) => $i->medicine?->atc_code)),
            'windowDays' => PharmacyStockOpsService::EXPIRING_WITHIN_DAYS,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Movement log
    // ══════════════════════════════════════════════════════════════════════

    public function adjustments(Request $request): View
    {
        $facilityId = $this->facilityId();

        $q = StockMovement::query()
            ->where('facility_id', $facilityId)
            ->with(['item.medicine', 'batch']);

        $reason = (string) $request->input('reason');

        if ($reason !== '' && isset(PharmacyStockOpsService::REASONS[$reason])) {
            // The code is the first token of `reason`; see encodeReason().
            $q->where(function ($w) use ($reason) {
                $w->where('reason', $reason)->orWhere('reason', 'like', $reason . ' | %');
            });
        }

        $movements = $q->orderByDesc('performed_at')->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        // Lots that can still move: open and holding packs. Closed (expired)
        // lots are offered too so a stray pack found later can still be
        // written off against the right lot — the service refuses a negative.
        $batches = $this->ops->batchQuery($facilityId)
            ->with('item.medicine')
            ->orderBy('created_at', 'desc')
            ->get()
            ->sortBy(fn (StockBatch $b) => ($b->item?->medicine?->name ?? '') . ' ' . ($b->expiry_date?->toDateString() ?? '9999'))
            ->values();

        $actors = \App\Models\User::query()
            ->whereIn('id', $movements->getCollection()->pluck('performed_by')->filter()->unique())
            ->pluck('name', 'id');

        return view('portals.pharmacy.stock_ops.adjustments', [
            'movements' => $movements,
            'batches'   => $batches,
            'reasons'   => array_keys(PharmacyStockOpsService::REASONS),
            'filter'    => $reason,
            'actors'    => $actors,
        ]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'batch_id' => ['required', 'uuid'],
            'reason'   => ['required', Rule::in(array_keys(PharmacyStockOpsService::REASONS))],
            // Signed only for a count correction; the service applies the sign
            // for every other reason, so "-3 dispensed" is still 3 out.
            'quantity' => ['required', 'integer', 'min:-1000000', 'max:1000000', 'not_in:0'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        // Another pharmacy's batch does not exist for this one.
        $batch = $this->ops->batchQuery($facilityId)->findOrFail($data['batch_id']);

        try {
            $this->ops->adjust($batch, $data['reason'], (int) $data['quantity'], $data['note'] ?? null, $this->context->actorId());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => __('pharmacy_ops.err_negative', [
                'have' => $batch->availableQty(),
            ])]);
        }

        return redirect()->route('portals.pharmacy.stock.adjustments')
            ->with('success', __('pharmacy_ops.flash_movement_recorded'));
    }

    // ══════════════════════════════════════════════════════════════════════
    // Reorder rules
    // ══════════════════════════════════════════════════════════════════════

    public function reorder(): View
    {
        $facilityId = $this->facilityId();

        $items = $this->ops->itemsWithBatches($facilityId);

        return view('portals.pharmacy.stock_ops.reorder', [
            'items'    => $items,
            'usable'   => $items->mapWithKeys(fn ($i) => [$i->id => $this->ops->usableQuantity($i)]),
            'below'    => $this->ops->belowMinimum($facilityId),
            'catalog'  => $this->catalogFor($facilityId),
        ]);
    }

    public function storeReorderRule(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'medicine_id'      => ['required', 'uuid'],
            'reorder_level'    => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $medicine = Medicine::query()->active()->find($data['medicine_id']);

        if (! $medicine) {
            return back()->withInput()->withErrors(['medicine_id' => __('pharmacy_ops.err_medicine')]);
        }

        $this->ops->setReorderRule(
            $facilityId,
            $medicine,
            (int) $data['reorder_level'],
            (int) ($data['reorder_quantity'] ?? 0),
            $this->context->actorId(),
        );

        return redirect()->route('portals.pharmacy.stock.reorder')
            ->with('success', __('pharmacy_ops.flash_rule_saved', ['medicine' => $medicine->name]));
    }

    // ══════════════════════════════════════════════════════════════════════
    // Goods receipts
    // ══════════════════════════════════════════════════════════════════════

    public function receipts(): View
    {
        $facilityId = $this->facilityId();

        $receipts = GoodsReceipt::query()
            ->where('facility_id', $facilityId)
            ->with('supplier')
            ->withCount('items')
            ->orderByDesc('received_date')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('portals.pharmacy.stock_ops.receipts', [
            'receipts' => $receipts,
        ]);
    }

    public function createReceipt(Request $request): View
    {
        $facilityId = $this->facilityId();

        // `?medicine_id=` from the item page prefills the first line; an id
        // that is not in the catalogue is simply ignored.
        $prefill = (string) $request->query('medicine_id');

        return view('portals.pharmacy.stock_ops.receipt_new', [
            'prefillMedicineId' => \Illuminate\Support\Str::isUuid($prefill) && Medicine::query()->active()->whereKey($prefill)->exists() ? $prefill : null,
            'suppliers' => Supplier::query()->where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get(),
            'orders'    => PurchaseOrder::query()->where('facility_id', $facilityId)
                ->whereIn('status', ['approved', 'sent', 'partial'])
                ->orderByDesc('order_date')->get(),
            'catalog'   => $this->catalogFor($facilityId),
            'today'     => now()->toDateString(),
        ]);
    }

    public function storeReceipt(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'supplier_id'          => ['nullable', 'uuid'],
            'purchase_order_id'    => ['nullable', 'uuid'],
            'received_date'        => ['required', 'date', 'before_or_equal:today'],
            'receipt_number'       => ['nullable', 'string', 'max:50'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'lines'                => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.medicine_id'  => ['required', 'uuid'],
            'lines.*.quantity'     => ['required', 'integer', 'min:1', 'max:1000000'],
            'lines.*.lot_number'   => ['nullable', 'string', 'max:80'],
            // An unknown expiry is left blank — never guessed.
            'lines.*.expiry_date'  => ['nullable', 'date'],
            // Whole francs. XAF has no minor unit.
            'lines.*.unit_cost'    => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        // Blank rows the form ships (an unused line template) are dropped
        // before the service sees them; a partly-filled row is a validation
        // error above, not a silently skipped batch.
        $lines = array_values(array_filter($data['lines'], fn ($l) => filled($l['medicine_id'] ?? null)));

        if ($lines === []) {
            return back()->withInput()->withErrors(['lines' => __('pharmacy_ops.err_no_lines')]);
        }

        try {
            $receipt = $this->ops->receiveDelivery($facilityId, [
                'supplier_id'       => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'received_date'     => $data['received_date'],
                'receipt_number'    => $data['receipt_number'] ?? null,
                'notes'             => $data['notes'] ?? null,
            ], $lines, $this->context->actorId());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => __('pharmacy_ops.err_medicine')]);
        }

        return redirect()->route('portals.pharmacy.stock.receipts.show', $receipt->id)
            ->with('success', __('pharmacy_ops.flash_receipt_recorded', ['number' => $receipt->receipt_number]));
    }

    public function showReceipt(string $id): View
    {
        $facilityId = $this->facilityId();

        $receipt = GoodsReceipt::query()
            ->where('facility_id', $facilityId)
            ->with(['supplier', 'purchaseOrder', 'items.item.medicine'])
            ->findOrFail($id);

        $batches = StockBatch::query()
            ->whereIn('id', StockMovement::query()
                ->where('reference_type', PharmacyStockOpsService::REFERENCE_RECEIPT)
                ->where('reference_id', $receipt->id)
                ->select('batch_id'))
            ->get()
            ->keyBy('inventory_item_id');

        return view('portals.pharmacy.stock_ops.receipt_show', [
            'receipt' => $receipt,
            'batches' => $batches,
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:100'],
        ]);

        $supplier = $this->ops->createSupplier($facilityId, $data, $this->context->actorId());

        // Added from the receipt form: back to the receipt. Added from the
        // register: straight to the new supplier's card.
        $target = $request->input('return') === 'receipt'
            ? route('portals.pharmacy.stock.receipts.create')
            : route('portals.pharmacy.stock.suppliers.show', $supplier->id);

        return redirect()->to($target)
            ->with('success', __('pharmacy_ops.flash_supplier_added', ['name' => $supplier->name]));
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════════

    /** Active catalogue medicines, capped so the picker cannot become unbounded. */
    private function catalogFor(string $facilityId): \Illuminate\Support\Collection
    {
        return Medicine::query()->active()->orderBy('name')->limit(600)
            ->get(['id', 'name', 'strength', 'form', 'atc_code']);
    }

    /**
     * The facility this desk is working for. PortalContextService only —
     * never a header, never the body, never the first facility in the table.
     */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_ops.no_facility_context'));
        }

        return $facilityId;
    }
}
