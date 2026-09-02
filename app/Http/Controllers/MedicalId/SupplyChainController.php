<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Modules\Inventory\Services\SupplyChainService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * SupplyChainController — one facility's stores: items, suppliers, stock,
 * purchase orders, goods receipts.
 *
 * This surface is frozen out of V1 (`inventory_ops` covers
 * `portals/staff/supply*`, so EnforceFeatureFlag 404s every route here). It is
 * hardened anyway: a freeze is a kill switch, not a fix, and the day it is
 * lifted the routes must not come back carrying a cross-facility write.
 *
 * The facility is resolved from the session, never from `Facility::value('id')`
 * — whichever row Postgres returns first — and every id that arrives from
 * outside is re-fetched scoped to it. `SupplyChainService` already scopes
 * `receiveStock()` and `adjustStock()`; the ids it does NOT check (a purchase
 * order's supplier and line items, a goods receipt's purchase order) are
 * checked here before they are handed over, since a PO or GR is otherwise a
 * way to bind another facility's records into ours.
 */
class SupplyChainController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * The single-facility fallback is honoured only when there is exactly one
     * facility. Production has 345; with no resolved context there is no safe
     * guess, so this 409s rather than picking a store room at random.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'whose stores this is. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /**
     * An id submitted in a form body must name a row of THIS facility.
     *
     * A null/blank id is left alone — those columns are genuinely optional and
     * the service already treats them as such. Anything present must resolve
     * inside the facility or the request 404s.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function assertBelongsToFacility(string $model, ?string $id, string $facilityId): void
    {
        if ($id === null || $id === '') {
            return;
        }

        abort_unless(
            $model::where('id', $this->assertUuid($id))->where('facility_id', $facilityId)->exists(),
            404
        );
    }

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    // ── Dashboard ─────────────────────────────────────────────

    public function index(SupplyChainService $svc)
    {
        $facilityId = $this->facilityId();
        $stats      = $svc->dashboardStats($facilityId);
        $lowStock   = $svc->getLowStockItems($facilityId)->take(10);
        $expiring   = $svc->getExpiringSoonBatches($facilityId, 30)->take(10);
        $expired    = $svc->getExpiredBatches($facilityId)->take(5);
        $recentMovements = StockMovement::where('facility_id', $facilityId)
            ->with('item')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('portals.staff.supply_chain.index',
            compact('stats', 'lowStock', 'expiring', 'expired', 'recentMovements'));
    }

    // ── Inventory Items ───────────────────────────────────────

    public function items(Request $request)
    {
        $facilityId = $this->facilityId();
        $q = InventoryItem::where('facility_id', $facilityId);

        if ($request->filled('category')) $q->where('category', $request->category);
        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('search'))   $q->where(fn($sq) =>
            $sq->where('name', 'like', "%{$request->search}%")
               ->orWhere('code', 'like', "%{$request->search}%")
        );

        $items = $q->orderBy('name')->paginate(25)->withQueryString();
        $categories = InventoryItem::categories();

        return view('portals.staff.supply_chain.items', compact('items', 'categories'));
    }

    public function itemStore(Request $request, SupplyChainService $svc)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'code'          => 'nullable|string|max:50',
            'category'      => 'required|string',
            'unit'          => 'required|string|max:30',
            'reorder_level' => 'nullable|integer|min:0',
            'unit_cost'     => 'nullable|numeric|min:0',
        ]);

        try {
            $svc->createItem($this->facilityId(), $request->validated(), $this->demoActorId());
            return redirect()->route('portals.staff.supply.items')
                ->with('success', __('flash.supply_item_created'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Suppliers ─────────────────────────────────────────────

    public function suppliers(Request $request)
    {
        $facilityId = $this->facilityId();
        $suppliers  = Supplier::where('facility_id', $facilityId)
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('portals.staff.supply_chain.suppliers', compact('suppliers'));
    }

    public function supplierStore(Request $request, SupplyChainService $svc)
    {
        $request->validate([
            'name'           => 'required|string|max:150',
            'code'           => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:500',
        ]);

        try {
            $svc->createSupplier($this->facilityId(), $request->validated(), $this->demoActorId());
            return redirect()->route('portals.staff.supply.suppliers')
                ->with('success', __('flash.supplier_added'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Stock (batches/receive) ───────────────────────────────

    public function stock(Request $request)
    {
        $facilityId = $this->facilityId();
        $q = StockBatch::where('facility_id', $facilityId)->with(['item', 'location']);

        if ($request->filled('item')) $q->where('inventory_item_id', $request->item);
        if ($request->filled('status')) $q->where('status', $request->status);

        $batches    = $q->orderByDesc('created_at')->paginate(25)->withQueryString();
        $items      = InventoryItem::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();
        $locations  = StockLocation::where('facility_id', $facilityId)->where('is_active', true)->get();
        $suppliers  = Supplier::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();

        return view('portals.staff.supply_chain.stock',
            compact('batches', 'items', 'locations', 'suppliers'));
    }

    public function stockReceive(Request $request, SupplyChainService $svc)
    {
        $request->validate([
            'inventory_item_id' => 'required|uuid',
            'quantity'          => 'required|integer|min:1',
            'location_id'       => 'nullable|uuid',
            'batch_number'      => 'nullable|string|max:80',
            'expiry_date'       => 'nullable|date',
            'unit_cost'         => 'nullable|numeric|min:0',
            'supplier_id'       => 'nullable|uuid',
        ]);

        $facilityId = $this->facilityId();

        // receiveStock() checks the item, but not the shelf it lands on or the
        // supplier it is credited to. Both are ids off the form.
        $this->assertBelongsToFacility(StockLocation::class, $request->input('location_id'), $facilityId);
        $this->assertBelongsToFacility(Supplier::class, $request->input('supplier_id'), $facilityId);

        try {
            $svc->receiveStock($facilityId, $request->validated(), $this->demoActorId());
            return redirect()->route('portals.staff.supply.stock')
                ->with('success', __('flash.stock_received'));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function stockAdjust(Request $request, string $batchId, SupplyChainService $svc)
    {
        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason'       => 'required|string|max:500',
        ]);

        $facilityId = $this->facilityId();
        $batchId    = $this->assertUuid($batchId);

        try {
            $svc->adjustStock($facilityId, $batchId, $request->validated(), $this->demoActorId());
            return redirect()->route('portals.staff.supply.stock')
                ->with('success', __('flash.stock_adjusted'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Purchase Orders ───────────────────────────────────────

    public function purchaseOrders(Request $request)
    {
        $facilityId = $this->facilityId();
        $q = PurchaseOrder::where('facility_id', $facilityId)->with('supplier');

        if ($request->filled('status')) $q->where('status', $request->status);

        $purchaseOrders = $q->with('items')->orderByDesc('created_at')->paginate(20)->withQueryString();
        $suppliers = Supplier::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();
        $items     = InventoryItem::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();

        return view('portals.staff.supply_chain.purchase_orders',
            compact('purchaseOrders', 'suppliers', 'items'));
    }

    public function purchaseOrderStore(Request $request, SupplyChainService $svc)
    {
        $request->validate([
            'supplier_id'            => 'nullable|uuid',
            'expected_delivery_date' => 'nullable|date',
            'notes'                  => 'nullable|string',
            'items'                          => 'required|array|min:1',
            'items.*.inventory_item_id'      => 'required|uuid',
            'items.*.quantity_ordered'       => 'required|integer|min:1',
            'items.*.unit_price'             => 'nullable|numeric|min:0',
        ]);

        $facilityId = $this->facilityId();

        // createPurchaseOrder() writes supplier_id and every line's
        // inventory_item_id straight through. Unchecked, a PO here could name a
        // neighbouring facility's supplier and order against their catalogue.
        $this->assertBelongsToFacility(Supplier::class, $request->input('supplier_id'), $facilityId);

        foreach ((array) $request->input('items', []) as $line) {
            $this->assertBelongsToFacility(InventoryItem::class, $line['inventory_item_id'] ?? null, $facilityId);
        }

        try {
            $po = $svc->createPurchaseOrder($facilityId, $request->validated(), $this->demoActorId());
            return redirect()->route('portals.staff.supply.purchase_orders')
                ->with('success', __('flash.purchase_order_created', ['number' => $po->po_number]));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function purchaseOrderApprove(string $id, SupplyChainService $svc)
    {
        $facilityId = $this->facilityId();
        $po = PurchaseOrder::where('id', $this->assertUuid($id))->where('facility_id', $facilityId)->firstOrFail();

        try {
            $svc->approvePurchaseOrder($po, $this->demoActorId());
            return redirect()->route('portals.staff.supply.purchase_orders')
                ->with('success', __('flash.purchase_order_approved', ['number' => $po->po_number]));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Goods Receipts ────────────────────────────────────────

    public function goodsReceipts(Request $request)
    {
        $facilityId = $this->facilityId();
        $goodsReceipts = GoodsReceipt::where('facility_id', $facilityId)
            ->with(['purchaseOrder.supplier', 'items'])
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();
        $suppliers  = Supplier::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();
        $openPOs    = PurchaseOrder::where('facility_id', $facilityId)
            ->whereIn('status', ['approved', 'sent', 'partial'])
            ->orderByDesc('created_at')
            ->get();
        $items      = InventoryItem::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();
        $locations  = StockLocation::where('facility_id', $facilityId)->where('is_active', true)->get();

        return view('portals.staff.supply_chain.goods_receipts',
            compact('goodsReceipts', 'suppliers', 'openPOs', 'items', 'locations'));
    }

    public function goodsReceiptsStore(Request $request, SupplyChainService $svc)
    {
        $request->validate([
            'purchase_order_id' => 'required|uuid',
            'received_date'     => 'required|date',
            'receipt_number'    => 'nullable|string|max:50',
            'received_by'       => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'lines'             => 'required|array|min:1',
            'lines.*.inventory_item_id' => 'required|uuid',
            'lines.*.quantity_received' => 'required|integer|min:1',
            'lines.*.batch_number'      => 'nullable|string|max:80',
            'lines.*.expiry_date'       => 'nullable|date',
        ]);

        $facilityId = $this->facilityId();

        // receiveGoodsReceipt() stores purchase_order_id, supplier_id and
        // location_id verbatim and only the per-line item is re-checked (inside
        // receiveStock). Receiving goods against another facility's purchase
        // order would silently close out their order line.
        $this->assertBelongsToFacility(PurchaseOrder::class, $request->input('purchase_order_id'), $facilityId);
        $this->assertBelongsToFacility(Supplier::class, $request->input('supplier_id'), $facilityId);
        $this->assertBelongsToFacility(StockLocation::class, $request->input('location_id'), $facilityId);

        try {
            $payload = $request->validated();
            // Service expects 'items' with 'quantity' key; view sends 'lines' with 'quantity_received'
            $payload['items'] = array_map(function ($line) {
                $line['quantity'] = $line['quantity_received'];
                unset($line['quantity_received']);
                return $line;
            }, $payload['lines']);
            unset($payload['lines']);

            $gr = $svc->receiveGoodsReceipt($facilityId, $payload, $this->demoActorId());
            return redirect()->route('portals.staff.supply.goods_receipts')
                ->with('success', __('flash.goods_receipt_posted', ['number' => ($gr->receipt_number ?: '')]));
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ── Stock Movements ───────────────────────────────────────

    public function movements(Request $request)
    {
        $facilityId = $this->facilityId();
        $q = StockMovement::where('facility_id', $facilityId)->with('item');

        if ($request->filled('type')) $q->where('movement_type', $request->type);
        if ($request->filled('item')) $q->where('inventory_item_id', $request->item);
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))   $q->whereDate('created_at', '<=', $request->to);

        $movements = $q->with(['item', 'batch'])
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();
        $items = InventoryItem::where('facility_id', $facilityId)->where('status', 'active')->orderBy('name')->get();

        return view('portals.staff.supply_chain.movements', compact('movements', 'items'));
    }
}
