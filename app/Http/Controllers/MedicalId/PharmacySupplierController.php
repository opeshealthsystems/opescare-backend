<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Services\Pharmacy\PharmacyStockDetailService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The supplier register: who delivers to this pharmacy, what they delivered
 * and what is still on order. Creation stays on
 * PharmacyStockOpsController::storeSupplier; this controller lists, shows
 * and edits.
 *
 * The facility is read from PortalContextService only. A supplier of another
 * pharmacy does not exist for this one (404).
 */
class PharmacySupplierController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyStockDetailService $detail,
    ) {
    }

    public function index(): View
    {
        $facilityId = $this->facilityId();

        return view('portals.pharmacy.stock_ops.suppliers', [
            'suppliers' => $this->detail->suppliersIndex($facilityId),
        ]);
    }

    public function show(string $id): View
    {
        $facilityId = $this->facilityId();

        $supplier = $this->detail->supplierQuery($facilityId)->findOrFail($id);

        return view('portals.pharmacy.stock_ops.supplier_show', [
            'supplier'   => $supplier,
            'deliveries' => $this->detail->deliveriesFrom($supplier),
            'items'      => $this->detail->itemsFrom($supplier),
            'openOrders' => $this->detail->openOrdersFor($supplier),
            'statuses'   => PharmacyStockDetailService::SUPPLIER_STATUSES,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $supplier = $this->detail->supplierQuery($facilityId)->findOrFail($id);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:100'],
            'address'        => ['nullable', 'string', 'max:500'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'status'         => ['required', Rule::in(PharmacyStockDetailService::SUPPLIER_STATUSES)],
        ]);

        $this->detail->updateSupplier($supplier, $data);

        return redirect()->route('portals.pharmacy.stock.suppliers.show', $supplier->id)
            ->with('success', __('pharmacy_stock_desk.flash_supplier_saved', ['name' => $supplier->name]));
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_ops.no_facility_context'));
        }

        return $facilityId;
    }
}
